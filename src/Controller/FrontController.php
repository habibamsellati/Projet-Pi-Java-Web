<?php
// src/Controller/FrontController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(): Response
    {
        // Affiche directement le template home/index.html.twig
        return $this->render('home/index.html.twig');
    }
    #[Route('/admin', name: 'admin_index')]
public function index(): Response
{
    return $this->render('admin/index.html.twig');
}
}
