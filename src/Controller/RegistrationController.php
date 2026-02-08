<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/register')]
class RegistrationController extends AbstractController
{
    private const ROLES_FRONT = ['CLIENT', 'ARTISANT', 'ADMIN', 'LIVREUR'];

    #[Route('', name: 'app_register')]
    public function register(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {

            $role = strtoupper($request->request->get('role'));

            //  Sécurité : rôles autorisés uniquement
            if (!in_array($role, self::ROLES_FRONT)) {
                $this->addFlash('error', 'Rôle non autorisé');
                return $this->redirectToRoute('app_register');
            }

            $user = new User();
            $nom = (string) $request->request->get('nom', '');
            $prenom = (string) $request->request->get('prenom', '');
            $email = (string) $request->request->get('email', '');

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'Email invalide (il faut un @)');
                return $this->redirectToRoute('app_register');
            }

            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setEmail($email);
            $user->setMotdepasse(
                password_hash($request->request->get('motdepasse'), PASSWORD_BCRYPT)
            );
            $user->setRole($role);
            $user->setStatut('inactif');
            $user->setDatecreation(new \DateTime());

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Compte créé avec succès');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('front/register.html.twig');
    }
}
