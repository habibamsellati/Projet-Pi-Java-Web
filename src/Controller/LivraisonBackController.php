<?php

namespace App\Controller;

use App\Entity\Livraison;
use App\Form\LivraisonType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\LivraisonRepository;


#[Route('/back/livraison', name: 'back_livraison_')]
class LivraisonBackController extends AbstractController
{
   
    #[Route('/', name: 'index')]
    public function index(Request $request, LivraisonRepository $livraisonRepository): Response
    {
<<<<<<< HEAD
        // Récupère le paramètre 'order' dans l'URL (?order=ASC ou DESC)
=======
       
>>>>>>> master
        $order = strtoupper($request->query->get('order', 'ASC'));
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'ASC';
        }
<<<<<<< HEAD
        

        // Trie les livraisons par date de livraison
=======

       
>>>>>>> master
        $livraisons = $livraisonRepository->findBy([], ['datelivraison' => $order]);

        return $this->render('livraison/indexback.html.twig', [
            'livraisons' => $livraisons,
<<<<<<< HEAD
            'current_order' => $order, // <-- obligatoire pour Twig
=======
            'current_order' => $order, 
>>>>>>> master
        ]);
    }


    #[Route('/new', name: 'new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $livraison = new Livraison();
        $form = $this->createForm(LivraisonType::class, $livraison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($livraison);
            $em->flush();
            return $this->redirectToRoute('back_livraison_index');
        }

        return $this->render('livraison/newback.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/{id}', name: 'edit')]
    public function edit(Livraison $livraison, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(LivraisonType::class, $livraison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('back_livraison_index');
        }

        return $this->render('livraison/editback.html.twig', [
            'form' => $form->createView(),
            'livraison' => $livraison,
        ]);
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['GET', 'POST'])]
    public function delete(Livraison $livraison, EntityManagerInterface $em): Response
    {
        $em->remove($livraison);
        $em->flush();
        return $this->redirectToRoute('back_livraison_index');
    }

    #[Route('/show/{id}', name: 'show')]
    public function show(Livraison $livraison): Response
    {
        return $this->render('livraison/showback.html.twig', [
            'livraison' => $livraison,
        ]);
    }
    #[Route('/statistiques', name: 'back_livraison_stats')]
public function stats(LivraisonRepository $livraisonRepository): Response
{
    return $this->render('livraison/statistiques.html.twig', [
        'stats' => $livraisonRepository->countByAdresse(),
    ]);
}
#[Route('/trier', name: 'trier')]
public function trier(Request $request, LivraisonRepository $livraisonRepository): Response
{
<<<<<<< HEAD
    // On récupère le paramètre 'order' de l'URL (asc ou desc), défaut ASC
=======
    
>>>>>>> master
    $order = strtoupper($request->query->get('order', 'ASC'));
    if (!in_array($order, ['ASC', 'DESC'])) {
        $order = 'ASC';
    }

<<<<<<< HEAD
    // On trie par date de livraison
=======
   
>>>>>>> master
    $livraisons = $livraisonRepository->findBy([], ['datelivraison' => $order]);

    return $this->render('livraison/indexback.html.twig', [
        'livraisons' => $livraisons,
        'current_order' => $order,
    ]);
}




}
