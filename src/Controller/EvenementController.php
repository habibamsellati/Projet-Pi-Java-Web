<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\EvenementImage;
use App\Entity\User;
use App\Form\EvenementType;
use App\Repository\EvenementRepository;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;

use App\Service\SmartFillService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/evenement')]
final class EvenementController extends AbstractController
{
    #[Route('/artisan/dashboard', name: 'app_artisan_dashboard', methods: ['GET'])]
    public function artisanDashboard(): Response
    {
        return $this->redirectToRoute('app_artisan_events');
    }

    #[Route('/artisan/mes-evenements', name: 'app_artisan_events', methods: ['GET'])]
    public function artisanEvents(EvenementRepository $evenementRepository, SmartFillService $smartFillService): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$this->isArtisan($user)) {
            $this->addFlash('error', 'Accès réservé aux artisans.');
            return $this->redirectToRoute('app_evenement_index');
        }

        $evenements = $evenementRepository->findBy(['artisan' => $this->getArtisanIdentifier($user)], ['createdAt' => 'DESC']);

        $smartFillData = [];
        foreach ($evenements as $evenement) {
            $prediction = $smartFillService->predictFillRate($evenement);
            $smartFillData[$evenement->getId()] = [
                'prediction' => $prediction,
                'insights' => $smartFillService->getInsights($prediction),
                'elasticity' => $smartFillService->calculatePriceElasticity($prediction, (float)$evenement->getPrix()),
                'successScore' => $smartFillService->calculateSuccessScore($evenement, $prediction),
                'brief' => $smartFillService->generateStrategicBrief($evenement, $prediction)
            ];
        }

        return $this->render('evenement/mes_evenements.html.twig', [
            'evenements' => $evenements,
            'smartFillData' => $smartFillData,
        ]);
    }

    #[Route('/', name: 'app_evenement_index', methods: ['GET'])]
    public function index(EvenementRepository $evenementRepository, UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        if ($user instanceof User && $this->isArtisan($user)) {
            return $this->redirectToRoute('app_artisan_events');
        }

        // Get all identifiers of current valid artisans
        $artisanNames = [];
        foreach ($userRepository->findAll() as $u) {
            if ($this->isArtisan($u)) {
                $artisanNames[] = $this->getArtisanIdentifier($u);
            }
        }

        if (empty($artisanNames)) {
            $artisanNames = ['__NONE__'];
        }

        return $this->render('evenement/index.html.twig', [
            'evenements' => $evenementRepository->createQueryBuilder('e')
                ->where('e.artisan IN (:artisans)')
                ->setParameter('artisans', array_unique($artisanNames))
                ->orderBy('e.createdAt', 'DESC')
                ->getQuery()
                ->getResult(),
        ]);
    }

    #[Route('/new', name: 'app_evenement_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User || !$this->isArtisan($user)) {
            $this->addFlash('error', 'Seuls les artisans peuvent créer un événement.');
            return $this->redirectToRoute('app_evenement_index');
        }

        $evenement = new Evenement();
        $evenement->setArtisan($this->getArtisanIdentifier($user));
        $evenement->setStatut('brouillon');

        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleCoverImageUpload($form->get('coverImage')->getData(), $evenement, $slugger);

            // Handle Multiple Image Upload
            $multipleFiles = $form->get('additionalImages')->getData();
            foreach ($multipleFiles as $file) {
                $this->handleGalleryImageUpload($file, $evenement, $slugger, $entityManager);
            }

            $entityManager->persist($evenement);
            $entityManager->flush();

            $entityManager->flush();
            $this->addFlash('success', 'Événement créé avec succès.');
            return $this->redirectToRoute('app_artisan_events');
        }

        return $this->render('evenement/new.html.twig', [
            'evenement' => $evenement,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/ai-strategy', name: 'app_evenement_ai_strategy', methods: ['GET'])]
    public function aiStrategy(Evenement $evenement, SmartFillService $smartFillService): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $evenement->getArtisan() !== $this->getArtisanIdentifier($user)) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à cette stratégie.');
        }

        $prediction = $smartFillService->predictFillRate($evenement);
        $analysis = $smartFillService->calculateStrategicAnalysis($evenement, $prediction);
        
        return $this->render('evenement/ai_strategy.html.twig', [
            'evenement' => $evenement,
            'prediction' => $prediction,
            'analysis' => $analysis,
            'checklist' => $smartFillService->getStrategicChecklist($evenement, $analysis),
            'successScore' => $smartFillService->calculateSuccessScore($evenement, $prediction),
            'elasticity' => $smartFillService->calculatePriceElasticity($prediction, (float)$evenement->getPrix()),
        ]);
    }

    #[Route('/{id}', name: 'app_evenement_show', methods: ['GET'])]
    public function show(Evenement $evenement, ReservationRepository $reservationRepository, SmartFillService $smartFillService): Response
    {
        $user = $this->getUser();
        $isOwnerArtisan = $user instanceof User
            && $this->isArtisan($user)
            && $evenement->getArtisan() === $this->getArtisanIdentifier($user);

        $prediction = null;
        $aiData = null;
        if ($isOwnerArtisan) {
            $prediction = $smartFillService->predictFillRate($evenement);
            $aiData = [
                'prediction' => $prediction,
                'insights' => $smartFillService->getInsights($prediction),
                'elasticity' => $smartFillService->calculatePriceElasticity($prediction, (float)$evenement->getPrix()),
                'successScore' => $smartFillService->calculateSuccessScore($evenement, $prediction),
                'brief' => $smartFillService->generateStrategicBrief($evenement, $prediction)
            ];
        }

        return $this->render('evenement/show.html.twig', [
            'evenement' => $evenement,
            'reservations' => $isOwnerArtisan ? $reservationRepository->findBy(['evenement' => $evenement], ['createdAt' => 'DESC']) : [],
            'canReserve' => $user instanceof User && !$this->isArtisan($user),
            'canManage' => $isOwnerArtisan,
            'smartFillPrediction' => $prediction,
            'smartFillInsights' => $aiData['insights'] ?? null,
            'aiData' => $aiData,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_evenement_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Evenement $evenement,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $user = $this->getUser();
        if (
            !$user instanceof User
            || !$this->isArtisan($user)
            || $evenement->getArtisan() !== $this->getArtisanIdentifier($user)
        ) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à modifier cet événement.');
            return $this->redirectToRoute('app_artisan_dashboard');
        }

        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleCoverImageUpload($form->get('coverImage')->getData(), $evenement, $slugger);

            // Handle Multiple Image Upload
            $multipleFiles = $form->get('additionalImages')->getData();
            foreach ($multipleFiles as $file) {
                $this->handleGalleryImageUpload($file, $evenement, $slugger, $entityManager);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Événement modifié avec succès.');
            return $this->redirectToRoute('app_artisan_events');
        }

        return $this->render('evenement/edit.html.twig', [
            'evenement' => $evenement,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_evenement_delete', methods: ['POST'])]
    public function delete(Request $request, Evenement $evenement, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (
            !$user instanceof User
            || !$this->isArtisan($user)
            || $evenement->getArtisan() !== $this->getArtisanIdentifier($user)
        ) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à supprimer cet événement.');
            return $this->redirectToRoute('app_artisan_dashboard');
        }

        if ($this->isCsrfTokenValid('delete' . $evenement->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($evenement);
            $entityManager->flush();
            $this->addFlash('success', 'Événement supprimé avec succès.');
        }

        return $this->redirectToRoute('app_artisan_dashboard');
    }



    private function isArtisan(User $user): bool
    {
        $role = strtoupper((string) $user->getRole());
        return in_array($role, ['ARTISANT', 'ROLE_ARTISAN', 'ARTISAN'], true);
    }

    private function getArtisanIdentifier(User $user): string
    {
        $fullName = trim(sprintf('%s %s', (string) $user->getPrenom(), (string) $user->getNom()));
        return $fullName !== '' ? $fullName : $user->getUserIdentifier();
    }

    private function handleCoverImageUpload(mixed $coverFile, Evenement $evenement, SluggerInterface $slugger): void
    {
        if (!$coverFile) {
            return;
        }
        $originalFilename = pathinfo($coverFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $extension = $coverFile->guessExtension() ?: 'jpg';
        $newFilename = $safeFilename . '-' . uniqid('', true) . '.' . $extension;

        $originalFilename = pathinfo($coverFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $extension = $coverFile->guessExtension() ?: $coverFile->getClientOriginalExtension();
        $newFilename = $safeFilename . '-' . uniqid('', true) . '.' . $extension;

        try {
            $destination = $this->getParameter('kernel.project_dir') . '/public/uploads/events/covers';
            if (!is_dir($destination)) {
                mkdir($destination, 0777, true);
            }
            $coverFile->move($destination, $newFilename);
            $evenement->setImage('/uploads/events/covers/' . $newFilename);
        } catch (FileException) {
            $this->addFlash('warning', 'Le fichier de couverture n\'a pas pu être enregistré.');
        }
    }

    private function handleGalleryImageUpload(mixed $file, Evenement $evenement, SluggerInterface $slugger, EntityManagerInterface $entityManager): void
    {
        if (!$file) {
            return;
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension();
        $newFilename = $safeFilename . '-' . uniqid('', true) . '.' . $extension;

        try {
            $destination = $this->getParameter('kernel.project_dir') . '/public/uploads/events/gallery';
            if (!is_dir($destination)) {
                mkdir($destination, 0777, true);
            }
            $file->move($destination, $newFilename);

            $eventImage = new EvenementImage();
            $eventImage->setUrl('/uploads/events/gallery/' . $newFilename);
            $eventImage->setEvenement($evenement);
            $entityManager->persist($eventImage);
        } catch (FileException) {
            $this->addFlash('warning', 'Un fichier de la galerie n\'a pas pu être enregistré.');
        }
    }
    #[Route('/artisan/generate-api-key', name: 'app_artisan_generate_api_key', methods: ['POST'])]
    public function generateApiKey(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $user->generateApiKey();
        $em->flush();

        $this->addFlash('success', 'Votre nouvelle clé API a été générée avec succès !');
        return $this->redirectToRoute('app_api_docs');
    }
}
