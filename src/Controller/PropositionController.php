<?php

namespace App\Controller;

use App\Entity\Proposition;
use App\Form\PropositionType;
use App\Repository\PropositionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Notifier\TexterInterface;
use App\Service\SmsService;

#[Route('/proposition')]
final class PropositionController extends AbstractController
{
    #[Route('/{id}/accepter', name: 'app_proposition_accepter', methods: ['POST'])]
    public function accepter(Request $request, Proposition $proposition, EntityManagerInterface $entityManager, TexterInterface $texter, SmsService $smsService): Response
    {
        $this->assertCanManageProposition($proposition);
        
        $artisan = $this->getUser();
        
        // Verifier quota 2 propositions par jour pour cet artisan
        $today = new \DateTimeImmutable('today');
        $acceptedCount = $entityManager->getRepository(Proposition::class)
            ->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->join('p.produit', 'prod')
            ->where('prod.addedBy = :artisan')
            ->andWhere('p.statut = :statut')
            ->andWhere('p.date >= :today')
            ->setParameter('artisan', $artisan)
            ->setParameter('statut', 'acceptee')
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();

        if ($acceptedCount >= 2) {
            $this->addFlash('error', 'Vous ne pouvez accepter plus de 2 propositions par jour.');
            return $this->redirectToRoute('app_proposition_index');
        }

        $proposition->setStatut('acceptee');
        $entityManager->flush();

        $this->envoyerSmsClient($proposition, $texter, $smsService);
        
        $this->addFlash('success', 'Proposition acceptée.');
        return $this->redirectToRoute('app_proposition_index');
    }

    #[Route('/{id}/refuser', name: 'app_proposition_refuser', methods: ['POST'])]
    public function refuser(Request $request, Proposition $proposition, EntityManagerInterface $entityManager, TexterInterface $texter, SmsService $smsService): Response
    {
        $this->assertCanManageProposition($proposition);

        $proposition->setStatut('refusee');
        $entityManager->flush();

        $this->envoyerSmsClient($proposition, $texter, $smsService);

        $this->addFlash('success', 'Proposition refusée.');
        return $this->redirectToRoute('app_proposition_index');
    }

    #[Route('/{id}/terminer', name: 'app_proposition_terminer', methods: ['POST'])]
    public function terminer(Request $request, Proposition $proposition, EntityManagerInterface $entityManager, TexterInterface $texter, SmsService $smsService): Response
    {
        $this->assertCanManageProposition($proposition);

        $proposition->setStatut('terminee');
        $entityManager->flush();

        $this->envoyerSmsClient($proposition, $texter, $smsService);

        $this->addFlash('success', 'Proposition terminée.');
        return $this->redirectToRoute('app_proposition_index');
    }

    private function envoyerSmsClient(Proposition $proposition, TexterInterface $texter, SmsService $smsService): void
    {
        $rawPhone = $proposition->getClientPhone();

        if (!$rawPhone) {
            $this->addFlash('warning', 'Aucun numéro de téléphone renseigné — SMS non envoyé.');
            return;
        }

        $clientPhone = $this->normalizePhoneNumber($rawPhone);

        if (!$clientPhone) {
            $this->addFlash('warning', 'Numéro client invalide — SMS non envoyé.');
            return;
        }

        $produitNom  = $proposition->getProduit()?->getNomproduit() ?? 'votre produit';
        $prix        = $proposition->getPrixPropose();
        $prixStr     = $prix ? number_format($prix, 2, '.', ' ') . ' TND' : '';

        switch ($proposition->getStatut()) {
            case 'acceptee':
                $messageText = "AfkArt : Votre proposition pour \"{$produitNom}\" a ete ACCEPTEE par l'artisan."
                    . ($prixStr ? " Prix convenu : {$prixStr}." : '')
                    . " Vous serez informe quand le travail sera termine.";
                break;
            case 'refusee':
                $messageText = "AfkArt : Votre proposition pour \"{$produitNom}\" a ete REFUSEE par l'artisan. Vous pouvez soumettre une nouvelle proposition.";
                break;
            case 'terminee':
                $messageText = "AfkArt : Bonne nouvelle ! Votre tableau realise a partir de \"{$produitNom}\" est TERMINE. Contactez l'artisan pour la recuperation. Merci de faire confiance a AfkArt !";
                break;
            default:
                return;
        }

        $sent = $smsService->sendSms($clientPhone, $messageText);

        if ($sent) {
            $this->addFlash('success', "✅ SMS envoyé au {$clientPhone}.");
        } else {
            $this->addFlash('warning', '⚠️ Statut mis à jour, mais échec envoi SMS. Vérifiez TWILIO_DSN dans .env.');
        }
    }

    private function normalizePhoneNumber(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $phone = trim($phone);
        if ($phone === '') {
            return null;
        }

        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '216')) {
            return '+' . $digits;
        }

        if (strlen($digits) === 8) {
            return '+216' . $digits;
        }

        return '+' . $digits;
    }

    #[Route(name: 'app_proposition_index', methods: ['GET'])]
    public function index(PropositionRepository $propositionRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $role = strtoupper((string) $user->getRole());
        $isClient = ($role === 'CLIENT' || $role === 'ROLE_CLIENT');
        
        $propositions = $isClient
            ? $propositionRepository->findForClient($user)
            : $propositionRepository->findAll();
        return $this->render('proposition/index.html.twig', [
            'propositions' => $propositions,
            'can_manage' => $this->canManageProposition(),
        ]);
    }

    #[Route('/new', name: 'app_proposition_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        $proposition = new Proposition();
        $proposition->setUser($this->getUser());
        $form = $this->createForm(PropositionType::class, $proposition);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$proposition->getUser()) {
                $proposition->setUser($this->getUser());
            }
            $entityManager->persist($proposition);
            $entityManager->flush();
            $this->addFlash('success', 'La proposition a été ajoutée avec succès !');
            return $this->redirectToRoute('app_proposition_index', [], Response::HTTP_SEE_OTHER);
        }
        if ($form->isSubmitted()) {
            $this->addFlash('error', 'Il y a des erreurs dans le formulaire.');
        }

        return $this->render('proposition/new.html.twig', [
            'proposition' => $proposition,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_proposition_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Proposition $proposition): Response
    {
        $this->assertCanViewProposition($proposition);
        return $this->render('proposition/show.html.twig', [
            'proposition' => $proposition,
            'can_manage' => $this->canManageProposition($proposition),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_proposition_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Proposition $proposition, EntityManagerInterface $entityManager): Response
    {
        $this->assertCanManageProposition($proposition);
        $form = $this->createForm(PropositionType::class, $proposition);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Proposition mise à jour.');
            return $this->redirectToRoute('app_proposition_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($form->isSubmitted()) {
            $this->addFlash('error', 'Il y a des erreurs dans le formulaire de modification.');
        }

        return $this->render('proposition/edit.html.twig', [
            'proposition' => $proposition,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_proposition_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Proposition $proposition, EntityManagerInterface $entityManager): Response
    {
        $this->assertCanManageProposition($proposition);
        if ($this->isCsrfTokenValid('delete' . $proposition->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($proposition);
            $entityManager->flush();
            $this->addFlash('success', 'Proposition supprimée.');
        }
        return $this->redirectToRoute('app_proposition_index', [], Response::HTTP_SEE_OTHER);
    }

    private function canManageProposition(?Proposition $proposition = null): bool
    {
        $user = $this->getUser();
        if (!$user) {
            return false;
        }
        
        $role = strtoupper((string) $user->getRole());
        // Standardize role strings
        $isAdmin = ($role === 'ADMIN' || $role === 'ROLE_ADMIN');
        $isArtisan = ($role === 'ARTISANT' || $role === 'ROLE_ARTISANT' || $role === 'ARTISAN' || $role === 'ROLE_ARTISAN');
        $isClient = ($role === 'CLIENT' || $role === 'ROLE_CLIENT');

        if ($isAdmin || $isArtisan) {
            return true;
        }

        if ($isClient) {
            // A client can only manage (edit/delete) their own propositions
            if ($proposition === null) {
                return true;
            }
            return $proposition->getUser() && $proposition->getUser()->getId() === $user->getId();
        }

        return false;
    }

    private function assertCanViewProposition(Proposition $proposition): void
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }
        
        $role = strtoupper((string) $user->getRole());
        $isAdmin = ($role === 'ADMIN' || $role === 'ROLE_ADMIN');
        $isArtisan = ($role === 'ARTISANT' || $role === 'ROLE_ARTISANT' || $role === 'ARTISAN' || $role === 'ROLE_ARTISAN');

        if ($isAdmin || $isArtisan) {
            return;
        }

        // For CLIENT role
        if ($proposition->getUser() && $proposition->getUser()->getId() === $user->getId()) {
            return;
        }
        $produit = $proposition->getProduit();
        if ($produit && $produit->getAddedBy() && $produit->getAddedBy()->getId() === $user->getId()) {
            return;
        }

        throw $this->createAccessDeniedException('Vous ne pouvez voir que vos propositions ou celles liées à vos produits.');
    }

    private function assertCanManageProposition(Proposition $proposition): void
    {
        if (!$this->canManageProposition($proposition)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas les droits pour modifier ou supprimer cette proposition.');
        }
    }
}
