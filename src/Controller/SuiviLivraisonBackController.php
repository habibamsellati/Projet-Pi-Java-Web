<?php
// src/Controller/SuiviLivraisonBackController.php
namespace App\Controller;

use App\Entity\SuiviLivraison;
use App\Form\SuiviLivraisonType;
use App\Repository\SuiviLivraisonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

<<<<<<< HEAD
=======

>>>>>>> master
#[Route('suivi_livraison')]
class SuiviLivraisonBackController extends AbstractController
{
    #[Route('/', name: 'back_suivi_livraison_index')]
public function index(Request $request, SuiviLivraisonRepository $repository): Response
{
<<<<<<< HEAD
    // Récupère le paramètre 'order' (?order=ASC ou DESC)
=======
    
>>>>>>> master
    $order = strtoupper($request->query->get('order', 'ASC'));
    if (!in_array($order, ['ASC', 'DESC'])) {
        $order = 'ASC';
    }

<<<<<<< HEAD
    // Tri des suivis par date de suivi
=======
   
>>>>>>> master
    $suivis = $repository->findBy([], ['datesuivi' => $order]);

    return $this->render('suivi_livraison/indexback.html.twig', [
        'suivis' => $suivis,
<<<<<<< HEAD
        'current_order' => $order, // important pour Twig
=======
        'current_order' => $order, 
>>>>>>> master
    ]);
}


    #[Route('/new', name: 'back_suivi_livraison_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $suivi = new SuiviLivraison();
        $form = $this->createForm(SuiviLivraisonType::class, $suivi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($suivi);
            $em->flush();
            $this->addFlash('success', 'Suivi ajouté avec succès');
            return $this->redirectToRoute('back_suivi_livraison_index');
        }

        return $this->render('suivi_livraison/newback.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/show', name: 'back_suivi_livraison_show', methods: ['GET'])]
    public function show(SuiviLivraison $suivi): Response
    {
        return $this->render('suivi_livraison/showback.html.twig', [
            'suivi' => $suivi,
        ]);
    }

    #[Route('/{id}/edit', name: 'back_suivi_livraison_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SuiviLivraison $suivi, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(SuiviLivraisonType::class, $suivi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Suivi modifié avec succès');
            return $this->redirectToRoute('back_suivi_livraison_index');
        }

        return $this->render('suivi_livraison/editback.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'back_suivi_livraison_delete', methods: ['POST'])]
    public function delete(Request $request, SuiviLivraison $suivi, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$suivi->getId(), $request->request->get('_token'))) {
            $em->remove($suivi);
            $em->flush();
            $this->addFlash('success', 'Suivi supprimé avec succès');
        }

        return $this->redirectToRoute('back_suivi_livraison_index');
    }
    #[Route('/stats', name: 'back_suivi_livraison_stats', methods: ['GET'])]
public function stats(SuiviLivraisonRepository $repo): Response
{
    $results = $repo->createQueryBuilder('s')
        ->select('s.etat AS etat, COUNT(s.id) AS total')
        ->groupBy('s.etat')
        ->getQuery()
        ->getResult();

    // Séparer les labels et les données pour Chart.js
    $labels = [];
    $data = [];

    foreach ($results as $row) {
        $labels[] = $row['etat'];
        $data[] = (int) $row['total'];
    }

   return $this->render('suivi_livraison/statistiquessuivilivraison.html.twig', [
    'labels' => $labels,
    'data' => $data,
]);
}
#[Route('/pdf', name: 'back_suivi_livraison_pdf')]
public function exportPdf(SuiviLivraisonRepository $suiviLivraisonRepository): Response
{
<<<<<<< HEAD
    // Récupération des suivis de livraison
    $suivisLivraison = $suiviLivraisonRepository->findAll();

    // Options Dompdf
    $options = new Options();
    $options->set('defaultFont', 'DejaVu Sans'); // accents OK

    $dompdf = new Dompdf($options);

    // Génération du HTML via Twig
=======
   
    $suivisLivraison = $suiviLivraisonRepository->findAll();

   
    $options = new Options();
    $options->set('defaultFont', 'DejaVu Sans'); 

    $dompdf = new Dompdf($options);

    
>>>>>>> master
    $html = $this->renderView('suivi_livraison/pdf.html.twig', [
        'suivisLivraison' => $suivisLivraison,
    ]);

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    return new Response(
        $dompdf->output(),
        200,
        [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="suivi_livraisons.pdf"',
        ]
    );
}

   
}


?>
