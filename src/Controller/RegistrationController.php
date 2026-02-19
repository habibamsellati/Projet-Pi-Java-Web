<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\AvatarGenerator;
use App\Service\TurnstileVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/register')]
class RegistrationController extends AbstractController
{
    private const ROLES_FRONT = ['CLIENT', 'ARTISANT', 'ADMIN', 'LIVREUR'];
    private const SEXES = ['HOMME', 'FEMME', 'AUTRE'];

    #[Route('', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        AvatarGenerator $avatarGenerator,
        TurnstileVerifier $turnstileVerifier
    ): Response
    {
        $lastEnteredData = [];
        $errors = [];

        if ($request->isMethod('POST')) {

            $role = strtoupper($request->request->get('role'));

            //  Sécurité : rôles autorisés uniquement
            if (!in_array($role, self::ROLES_FRONT)) {
                $this->addFlash('error', 'Rôle non autorisé');
                return $this->redirectToRoute('app_register');
            }

            $nom = (string) $request->request->get('nom', '');
            $prenom = (string) $request->request->get('prenom', '');
            $email = (string) $request->request->get('email', '');
            $motdepasse = (string) $request->request->get('motdepasse', '');
            $sexe = strtoupper((string) $request->request->get('sexe', ''));
            
            // Keep data for repopulating form
            $lastEnteredData = [
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'role' => $role,
                'sexe' => $sexe,
                // Do not send back password
            ];

            if (!in_array($sexe, self::SEXES, true)) {
                $errors['sexe'] = 'Sexe invalide';
            }
            if (!$turnstileVerifier->verifyRequest($request)) {
                $errors['captcha'] = 'Verification CAPTCHA invalide.';
            }

            $user = new User();
            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setEmail($email);
            $user->setSexe($sexe);

            $user->setMotdepasse($motdepasse); 
            $user->setRole($role);
            $user->setStatut('inactif');
            $user->setDatecreation(new \DateTime());
            
            // Validate the entity
            $violations = $validator->validate($user);
            
            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $errors[$violation->getPropertyPath()] = $violation->getMessage();
                }
            }
            
            // Manual uniqueness check (could be a UniqueEntity constraint but keeping logic here as requested/planned sort of)
            $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                // If email duplication isn't already caught (it wouldn't be by standard constraints unless UniqueEntity is used)
                if (!isset($errors['email'])) {
                    $errors['email'] = 'Cet email existe déjà';
                }
            }

            if (empty($errors)) {
                // Hash password now that validation passed
                $user->setMotdepasse(
                    password_hash($motdepasse, PASSWORD_BCRYPT)
                );
                $user->setAvatar($avatarGenerator->generate($nom, $prenom, $sexe));
                
                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Compte créé avec succès');
                return $this->redirectToRoute('app_login');
            }
        }
        return $this->render('front/register.html.twig', [
            'errors' => $errors,
            'last_entered_data' => $lastEnteredData,
            'turnstile_site_key' => $turnstileVerifier->getSiteKey(),
        ]);
    }
}
