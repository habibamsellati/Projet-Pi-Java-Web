<?php

namespace App\Controller;

use App\Entity\Livraison;
use App\Form\LivraisonType;
use App\Repository\LivraisonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpClient\HttpClient;
use Tattali\CalendarBundle\Model\Calendar;
use Tattali\CalendarBundle\Model\Event;

#[Route('/livraison')]
class LivraisonController extends AbstractController
{
    #[Route('/', name: 'app_livraison_index', methods: ['GET'])]
    public function index(LivraisonRepository $livraisonRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $role = strtoupper((string) $user->getRole());
        $livraisons = ($role === 'ADMIN' || $role === 'LIVREUR')
            ? $livraisonRepository->findAllOrderByDateAsc()
            : $livraisonRepository->findByClient($user);

        // --- LOGIQUE IA : PRÉDICTION DE RETARD ---
        $latDepot = 36.8065; // Position de l'entrepôt (Tunis)
        $lngDepot = 10.1815;
        $vitesseIA = 0.0005; // Simulation vitesse (degrés par minute)

        foreach ($livraisons as $livraison) {
            if ($livraison->getLat() && $livraison->getLng()) {
                // 1. Calcul de la distance euclidienne
                $dist = sqrt(pow($livraison->getLat() - $latDepot, 2) + pow($livraison->getLng() - $lngDepot, 2));
                $tempsMinutes = $dist / $vitesseIA;

                // 2. Prédiction de l'heure d'arrivée
                $maintenant = new \DateTime();
                $arriveePredite = clone $maintenant;
                $arriveePredite->modify("+" . round($tempsMinutes) . " minutes");

                // 3. Comparaison avec la deadline (Le verdict de l'IA)
                if ($livraison->getDatelivraison() && $arriveePredite > $livraison->getDatelivraison()) {
                    $livraison->predictionStatus = "Retard Prévu";
                    $livraison->couleurIA = "red";
                } else {
                    $livraison->predictionStatus = "À l'heure";
                    $livraison->couleurIA = "green";
                }
            } else {
                $livraison->predictionStatus = "Pas de coordonnées";
                $livraison->couleurIA = "gray";
            }
        }

        return $this->render('livraison/index.html.twig', [
            'livraisons' => $livraisons,
        ]);
    }

    #[Route('/new', name: 'app_livraison_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $em, \App\Repository\LivraisonRepository $livraisonRepo): Response
{
    if (!$this->getUser()) {
        return $this->redirectToRoute('app_login');
    }

    $livraison = new Livraison();
    $livraison->setStatutlivraison('en_attente');

    $form = $this->createForm(LivraisonType::class, $livraison);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        
        $livreurChoisi = $livraison->getLivreur();
        $dateChoisie = $livraison->getDatelivraison();

        if ($livreurChoisi && $dateChoisie) {
            // --- CETTE LIGNE ETAIT MANQUANTE OU MAL PLACÉE ---
            $nbLivraisons = $livraisonRepo->createQueryBuilder('l')
                ->select('count(l.id)')
                ->where('l.livreur = :livreur')
                ->andWhere('l.datelivraison >= :start')
                ->andWhere('l.datelivraison <= :end')
                ->setParameter('livreur', $livreurChoisi)
                ->setParameter('start', $dateChoisie->format('Y-m-d 00:00:00'))
                ->setParameter('end', $dateChoisie->format('Y-m-d 23:59:59'))
                ->getQuery()
                ->getSingleScalarResult();

            if ($nbLivraisons >= 3) {
                // On attache l'erreur au champ 'livreur' pour qu'elle s'affiche enfin !
                $form->get('livreur')->addError(new \Symfony\Component\Form\FormError("Ce livreur a déjà 3 livraisons pour cette date."));
                
                // On réaffiche le formulaire avec l'erreur
                return $this->render('livraison/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
        }

        // --- SI TOUT EST OK, ON CONTINUE L'API NOMINATIM ---
        $adresse = $livraison->getAddresslivraison();
        $client = \Symfony\Component\HttpClient\HttpClient::create();
        try {
            $response = $client->request('GET', 'https://nominatim.openstreetmap.org/search', [
                'query' => [
                    'q' => $adresse,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'tn'
                ],
                'headers' => ['User-Agent' => 'MonApplicationSymfony/1.0']
            ]);
            
            $data = $response->toArray();
            if (!empty($data)) {
                $livraison->setLat((float)$data[0]['lat']);
                $livraison->setLng((float)$data[0]['lon']);
            }
        } catch (\Exception $e) {
            $livraison->setLat(36.8065);
            $livraison->setLng(10.1815);
        }

        $em->persist($livraison);
        $em->flush();
        
        $this->addFlash('success', 'Livraison créée avec succès !');
        return $this->redirectToRoute('app_livraison_index');
    }

    return $this->render('livraison/new.html.twig', [
        'form' => $form->createView(),
    ]);
}
    #[Route('/{id}', name: 'app_livraison_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Livraison $livraison): Response
    {
        $this->assertCanAccessLivraison($livraison);
        return $this->render('livraison/show.html.twig', [
            'livraison' => $livraison,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_livraison_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Livraison $livraison, EntityManagerInterface $em): Response
    {
        $this->assertCanAccessLivraison($livraison);
        $form = $this->createForm(LivraisonType::class, $livraison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Mise à jour effectuée.');
            return $this->redirectToRoute('app_livraison_index');
        }
        $adresse = $livraison->getAdresse();
$url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($adresse) . "&format=json&limit=1";

// Configuration du contexte pour l'API (nécessaire pour Nominatim)
$opts = ["http" => ["header" => "User-Agent: MyApp/1.0\r\n"]];
$context = stream_context_create($opts);

$response = file_get_contents($url, false, $context);
$data = json_decode($response, true);

if (!empty($data)) {
    // On met à jour automatiquement la Latitude et la Longitude
    $livraison->setLat($data[0]['lat']);
    $livraison->setLng($data[0]['lon']);
}

        return $this->render('livraison/edit.html.twig', [
            'livraison' => $livraison,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_livraison_delete', methods: ['POST'])]
    public function delete(Request $request, Livraison $livraison, EntityManagerInterface $em): Response
    {
        $this->assertCanAccessLivraison($livraison);
        if ($this->isCsrfTokenValid('delete' . $livraison->getId(), $request->request->get('_token'))) {
            $em->remove($livraison);
            $em->flush();
            $this->addFlash('success', 'Livraison supprimée.');
        }
        return $this->redirectToRoute('app_livraison_index');
    }

    #[Route('/{id}/noter', name: 'app_suivi_livraison_note', methods: ['GET', 'POST'])]
    public function noter(Request $request, Livraison $livraison, EntityManagerInterface $entityManager): Response
    {
        $this->assertCanAccessLivraison($livraison);
        if ($request->isMethod('POST')) {
            $note = $request->request->get('note');
            if ($note !== null) {
                $livraison->setNoteLivreur((int) $note);
                $entityManager->flush();
                $this->addFlash('success', 'Note enregistrée.');
            }
            return $this->redirectToRoute('app_livraison_show', ['id' => $livraison->getId()]);
        }
        return $this->render('livraison/noter.html.twig', ['livraison' => $livraison]);
    }

    private function assertCanAccessLivraison(Livraison $livraison): void
    {
        $user = $this->getUser();
        if (!$user) throw $this->createAccessDeniedException('Connectez-vous.');
        
        $role = strtoupper((string) $user->getRole());
        if ($role === 'ADMIN' || $role === 'LIVREUR') return;
        
        $commande = $livraison->getCommande();
        if (!$commande || $commande->getClient()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }
    }
    // --- AJOUTER CES DEUX ROUTES DANS LivraisonController.php ---

#[Route('/map', name: 'app_livraison_map', methods: ['GET'])]
public function map(Request $request, LivraisonRepository $livraisonRepository): Response
{
    $currentLat = (float) $request->query->get('lat', 36.8065);
    $currentLng = (float) $request->query->get('lng', 10.1815);

    $livraisonsData = $livraisonRepository->findAll();
    $livraisonsPourLaVue = [];

    foreach ($livraisonsData as $livraison) {
        if (!$livraison->getLat() || !$livraison->getLng()) continue;

        $distance = $this->haversineDistance($currentLat, $currentLng, $livraison->getLat(), $livraison->getLng());
        
        // --- LOGIQUE IA DÉCLENCHÉE PAR LE BOUTON ---
        $eta = null;
        $statusIA = "En attente de traitement";
        $couleur = "#95a5a6"; // Gris par défaut

        if ($livraison->getStatutlivraison() === 'en_cours') {
            // L'IA s'active ici car le livreur a cliqué sur Démarrer
            $vitesseMoyenne = 40; 
            $heure = (int) date('H');
            $isHeurePointe = (($heure >= 7 && $heure <= 9) || ($heure >= 17 && $heure <= 19));
            if ($isHeurePointe) $vitesseMoyenne *= 0.6;

            $eta = round(($distance / $vitesseMoyenne) * 60) + 5;
            $statusIA = $isHeurePointe ? "Trafic dense - Livraison en cours" : "Trafic fluide - Livraison en cours";
            $couleur = $isHeurePointe ? "#e67e22" : "#2ecc71";
        } elseif ($livraison->getStatutlivraison() === 'livre') {
            $statusIA = "Colis livré";
            $couleur = "#2980b9";
        }

        $livraisonsPourLaVue[] = [
            'id' => $livraison->getId(),
            'lat' => $livraison->getLat(),
            'lng' => $livraison->getLng(),
            'distance' => round($distance, 2),
            'eta' => $eta,
            'status' => $statusIA,
            'couleur' => $couleur,
            'statutActuel' => $livraison->getStatutlivraison(),
            'adresse' => $livraison->getAddresslivraison()
        ];
    }

    return $this->render('livraison/map.html.twig', [
        'livraisons' => $livraisonsPourLaVue,
        'currentLat' => $currentLat,
        'currentLng' => $currentLng
    ]);
}

#[Route('/update-status-map/{id}', name: 'app_livraison_update_status_map', methods: ['POST'])]
public function updateStatusMap(Livraison $livraison, Request $request, EntityManagerInterface $em): Response 
{
    // Vérification CSRF
    if (!$this->isCsrfTokenValid('livreur_update', (string) $request->request->get('_token'))) {
        $this->addFlash('error', 'Jeton de sécurité invalide.');
        return $this->redirectToRoute('app_livraison_map');
    }

    $currentStatus = $livraison->getStatutlivraison();

    if ($currentStatus === 'en_attente') {
        $livraison->setStatutlivraison('en_cours');
        $message = 'Livraison démarrée ! 🚀';
    } else {
        $livraison->setStatutlivraison('livré');
        $message = 'Mission accomplie ! ✨';
    }

    $em->flush();
    $this->addFlash('success', $message);

    return $this->redirectToRoute('app_livraison_map');
}
// Ajoute cette fonction à la fin de ta classe LivraisonController
    private function haversineDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371; // Rayon de la Terre en km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
} // <--- C'est la toute dernière accolade de ton fichier
