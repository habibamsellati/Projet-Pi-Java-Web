# 📚 DOCUMENTATION COMPLÈTE - PROJET SYMFONY GESTION D'ARTICLES

## 🎯 PRÉSENTATION DU PROJET

### Vue d'ensemble
Ce projet Symfony est une plateforme de gestion d'articles artisanaux avec un système complet de:
- **CRUD** (Create, Read, Update, Delete) pour les articles
- **Système de commentaires** avec réponses hiérarchiques
- **Réactions** (like/dislike) sur les commentaires
- **Panier d'achat** et gestion des commandes
- **Email automatique personnalisé par IA** lors de la création d'une commande
- **Filtrage de mots inappropriés** dans les commentaires
- **Pagination** des articles
- **Génération de PDF** pour les statistiques
- **Traduction automatique** des commentaires

### Rôles utilisateurs
1. **CLIENT**: Peut consulter, commenter, liker les articles, passer des commandes
2. **ARTISAN**: Peut créer/modifier/supprimer ses articles, répondre aux commentaires
3. **ADMIN**: Accès complet à toutes les fonctionnalités

---

## 📁 STRUCTURE DU PROJET

```
src/
├── Entity/              # Entités Doctrine (modèles de données)
│   ├── Article.php
│   ├── Commentaire.php
│   ├── CommentaireReaction.php
│   ├── Commande.php
│   └── User.php
├── Controller/          # Contrôleurs (logique métier)
│   ├── ArticleController.php
│   ├── PanierController.php
│   └── BackController.php
├── Repository/          # Repositories (requêtes base de données)
│   ├── ArticleRepository.php
│   ├── CommentaireReactionRepository.php
│   └── CommandeRepository.php
├── Form/                # Formulaires Symfony
│   ├── Article1Type.php
│   ├── CommentaireType.php
│   └── CommandeValidationType.php
└── Service/             # Services métier
    ├── PersonalizedMessageService.php  # IA pour emails
    └── BadWordFilterService.php        # Filtrage mots inappropriés
```

---


## 🗄️ PARTIE 1: LES ENTITÉS (MODÈLES DE DONNÉES)

### 1.1 Entity/Article.php

#### Annotations/Attributs Doctrine ORM

```php
#[ORM\Entity(repositoryClass: ArticleRepository::class)]
```
- **Rôle**: Déclare que cette classe est une entité Doctrine mappée à une table `article` en base de données
- **repositoryClass**: Spécifie la classe Repository personnalisée pour les requêtes complexes

#### Attributs de l'entité

```php
#[ORM\Id]
#[ORM\GeneratedValue]
#[ORM\Column]
private ?int $id = null;
```
- `#[ORM\Id]`: Définit la clé primaire
- `#[ORM\GeneratedValue]`: Auto-incrémentation de l'ID
- `#[ORM\Column]`: Crée une colonne en base de données

```php
#[ORM\Column(length: 255)]
#[Assert\NotBlank(message: 'Ce champ doit être rempli.')]
#[Assert\Length(min: 3, max: 255, ...)]
private ?string $titre = null;
```
- `#[ORM\Column(length: 255)]`: Colonne VARCHAR(255)
- `#[Assert\NotBlank]`: Validation Symfony - champ obligatoire
- `#[Assert\Length]`: Validation de la longueur (min 3, max 255 caractères)

```php
#[ORM\Column(type: Types::TEXT)]
private ?string $contenu = null;
```
- `Types::TEXT`: Type de colonne TEXT (pour contenu long)

```php
#[ORM\Column(type: Types::DATE_MUTABLE)]
private ?\DateTime $date = null;
```
- `Types::DATE_MUTABLE`: Stocke une date (objet DateTime PHP)
- `MUTABLE`: L'objet peut être modifié

```php
#[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
#[Assert\GreaterThanOrEqual(0, message: 'Le prix doit être positif ou nul.')]
private ?string $prix = null;
```
- `DECIMAL(10,2)`: Nombre décimal avec 10 chiffres dont 2 après la virgule
- `nullable: true`: Le champ peut être NULL
- `#[Assert\GreaterThanOrEqual(0)]`: Le prix doit être ≥ 0

#### Relations entre entités

```php
#[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'article', cascade: ['remove'])]
private Collection $commentaires;
```
- **OneToMany**: Un article peut avoir plusieurs commentaires
- **targetEntity**: L'entité cible (Commentaire)
- **mappedBy**: Le champ dans Commentaire qui fait référence à Article
- **cascade: ['remove']**: Si on supprime l'article, tous ses commentaires sont supprimés

```php
#[ORM\ManyToMany(targetEntity: Commande::class, mappedBy: 'articles')]
private Collection $commandes;
```
- **ManyToMany**: Un article peut être dans plusieurs commandes, une commande peut contenir plusieurs articles
- Crée une table de liaison `commande_article`

```php
#[ORM\ManyToOne(inversedBy: 'articles')]
#[ORM\JoinColumn(nullable: false)]
private ?User $artisan = null;
```
- **ManyToOne**: Plusieurs articles peuvent appartenir à un artisan
- **inversedBy**: Le champ dans User qui contient la collection d'articles
- **JoinColumn(nullable: false)**: La clé étrangère ne peut pas être NULL

```php
#[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'likedArticles')]
#[ORM\JoinTable(name: 'article_like')]
private Collection $likedBy;
```
- **ManyToMany**: Système de likes - plusieurs users peuvent liker plusieurs articles
- **JoinTable**: Nom personnalisé de la table de liaison

#### Méthodes métier importantes

```php
public function isLikedBy(User $user): bool
{
    return $this->likedBy->contains($user);
}
```
- Vérifie si un utilisateur a liké l'article
- Utilise la méthode `contains()` de Doctrine Collection

---

### 1.2 Entity/Commentaire.php

#### Structure hiérarchique (commentaires et réponses)

```php
#[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'replies')]
#[ORM\JoinColumn(onDelete: 'CASCADE', nullable: true)]
private ?self $parent = null;
```
- **self::class**: Relation avec la même entité (auto-référence)
- **parent**: Le commentaire parent (NULL si c'est un commentaire principal)
- **onDelete: 'CASCADE'**: Si le parent est supprimé, les réponses sont supprimées

```php
#[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class, orphanRemoval: true)]
private Collection $replies;
```
- **replies**: Collection des réponses à ce commentaire
- **orphanRemoval: true**: Si une réponse est retirée de la collection, elle est supprimée de la BDD

#### Relations

```php
#[ORM\ManyToOne(inversedBy: 'commentaires')]
#[ORM\JoinColumn(nullable: false)]
private ?Article $article = null;
```
- Chaque commentaire appartient à un article

```php
#[ORM\OneToMany(mappedBy: 'commentaire', targetEntity: CommentaireReaction::class, orphanRemoval: true)]
private Collection $reactions;
```
- Un commentaire peut avoir plusieurs réactions (likes/dislikes)

---

### 1.3 Entity/CommentaireReaction.php

#### Constantes pour les types de réactions

```php
public const TYPE_LIKE = 1;
public const TYPE_DISLIKE = -1;
```
- Utilisation de constantes pour éviter les "magic numbers"
- 1 = like, -1 = dislike

#### Contrainte d'unicité

```php
#[ORM\UniqueConstraint(name: 'uniq_commentaire_user_reaction', columns: ['commentaire_id', 'user_id'])]
```
- **UniqueConstraint**: Un utilisateur ne peut avoir qu'UNE réaction par commentaire
- Empêche de liker ET disliker en même temps
- Crée un index unique en base de données

#### Validation du type

```php
public function setType(int $type): static
{
    if (!in_array($type, [self::TYPE_LIKE, self::TYPE_DISLIKE], true)) {
        throw new \InvalidArgumentException('Type de reaction invalide.');
    }
    $this->type = $type;
    return $this;
}
```
- Validation métier: seuls TYPE_LIKE et TYPE_DISLIKE sont acceptés
- Lance une exception si le type est invalide

---

### 1.4 Entity/Commande.php

#### Attributs spécifiques

```php
#[ORM\Column(length: 20, nullable: true)]
private ?string $numero = null;
```
- Numéro de commande unique (ex: CMD-20240221-001)

```php
#[ORM\Column(type: 'datetime')]
private ?\DateTime $datecommande = null;
```
- Date et heure de la commande

```php
#[ORM\Column(length: 255)]
private ?string $statut = null;
```
- Statut: 'en_attente', 'validee', 'expediee', 'livree', 'annulee'

#### Relation avec Livraison

```php
#[ORM\OneToOne(mappedBy: 'commande', cascade: ['persist', 'remove'])]
private ?Livraison $livraison = null;
```
- **OneToOne**: Une commande a une seule livraison
- **cascade: ['persist', 'remove']**: Si on sauvegarde/supprime la commande, la livraison suit

---


## 🎮 PARTIE 2: LES CONTRÔLEURS (LOGIQUE MÉTIER)

### 2.1 ArticleController.php

#### Route principale: Liste des articles avec filtres

```php
#[Route(name: 'app_article_index', methods: ['GET'])]
public function index(Request $request, ArticleRepository $articleRepository, PaginatorInterface $paginator): Response
```

**Paramètres injectés**:
- `Request $request`: Objet contenant les données de la requête HTTP (GET, POST, cookies, etc.)
- `ArticleRepository $articleRepository`: Repository personnalisé pour requêtes complexes
- `PaginatorInterface $paginator`: Bundle KnpPaginator pour la pagination

**Logique**:
1. Récupère l'utilisateur connecté et détermine son rôle
2. Extrait les paramètres de filtrage (recherche, catégorie, auteur, tri)
3. Crée un QueryBuilder avec les filtres
4. Pagine les résultats (6 articles par page)
5. Retourne la vue avec les articles et les filtres

**Filtres disponibles**:
- `recherche`: Recherche dans titre et contenu
- `categorie`: Filtrer par catégorie
- `auteur`: Filtrer par artisan
- `tri`: date_desc, date_asc, titre_asc, titre_desc, prix_asc, prix_desc

---

#### Route: Créer un article

```php
#[Route('/new', name: 'app_article_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, UserRepository $userRepository): Response
```

**Paramètres injectés**:
- `SluggerInterface $slugger`: Service Symfony pour "slugifier" les noms de fichiers (ex: "Mon Image.jpg" → "mon-image.jpg")
- `EntityManagerInterface $entityManager`: Gestionnaire d'entités Doctrine pour persister en BDD

**Logique**:
1. Vérifie que l'utilisateur n'est pas un CLIENT (seuls ARTISAN et ADMIN peuvent créer)
2. Crée un nouvel Article avec la date du jour
3. Crée et traite le formulaire
4. Si valide:
   - Associe l'artisan (utilisateur connecté ou premier artisan trouvé)
   - Gère l'upload d'image:
     - Slugifie le nom du fichier
     - Ajoute un ID unique
     - Déplace dans `/public/image/`
   - Persiste en base de données
   - Redirige vers la liste

**Upload d'image**:
```php
$imageFile = $form->get('image')->getData();
$originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
$safeFilename = $slugger->slug($originalFilename);
$newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
$imageFile->move($this->getParameter('kernel.project_dir') . '/public/image', $newFilename);
```

---

#### Route: Afficher un article avec commentaires

```php
#[Route('/{id}', name: 'app_article_show', methods: ['GET', 'POST'])]
public function show(Article $article, Request $request, EntityManagerInterface $entityManager, ArticleRepository $articleRepository, BadWordFilterService $badWordFilter): Response
```

**ParamConverter Doctrine**:
- `Article $article`: Symfony récupère automatiquement l'article depuis l'ID dans l'URL
- Si l'article n'existe pas → erreur 404 automatique

**Logique**:
1. Crée un formulaire pour ajouter un commentaire
2. Vérifie le rôle de l'utilisateur (CLIENT, ARTISAN, ADMIN)
3. Si formulaire soumis:
   - Vérifie que c'est un CLIENT
   - **Filtre les mots inappropriés** avec `BadWordFilterService`
   - Si mots inappropriés détectés → message d'erreur
   - Sinon → sauvegarde le commentaire
4. Prépare les données pour la vue:
   - Peut-il liker l'article?
   - A-t-il déjà liké?
   - Nombre de likes
   - Peut-il répondre aux commentaires?
   - Articles similaires (même catégorie)

**Filtrage de mots inappropriés**:
```php
$contenu = $commentaire->getContenu();
$badWordCheck = $badWordFilter->checkBadWords($contenu);

if ($badWordCheck['hasBadWords']) {
    $this->addFlash('error', '⚠️ Votre commentaire contient des mots inappropriés.');
    return $this->redirectToRoute('app_article_show', ['id' => $article->getId()]);
}
```

---

#### Route: Réagir à un commentaire (Like/Dislike)

```php
#[Route('/commentaire/{commentaire}/reaction/{type}', name: 'app_commentaire_reaction', requirements: ['commentaire' => '\d+', 'type' => 'like|dislike'], methods: ['POST'])]
public function reactToComment(Commentaire $commentaire, string $type, Request $request, EntityManagerInterface $entityManager): Response
```

**Requirements**:
- `'commentaire' => '\d+'`: L'ID doit être un nombre
- `'type' => 'like|dislike'`: Le type doit être "like" OU "dislike"

**Protection CSRF**:
```php
$csrfToken = $request->request->get('_token');
if (!$this->isCsrfTokenValid('commentaire_reaction_' . $commentaire->getId() . '_' . $type, $csrfToken)) {
    $this->addFlash('error', 'Action non autorisee.');
    return $this->redirectToRoute('app_article_show', ['id' => $commentaire->getArticle()->getId()]);
}
```
- Empêche les attaques CSRF (Cross-Site Request Forgery)
- Le token doit être généré dans le formulaire Twig

**Logique de réaction**:
```php
if ($type === 'like') {
    if ($commentaire->isLikedByUser($user)) {
        // Retirer le like
        $commentaire->removeLikedByUser($user);
    } else {
        // Ajouter le like et retirer le dislike si existe
        if ($commentaire->isDislikedByUser($user)) {
            $commentaire->removeDislikedByUser($user);
        }
        $commentaire->addLikedByUser($user);
    }
}
```
- Un utilisateur ne peut pas liker ET disliker en même temps
- Cliquer à nouveau retire la réaction

---

#### Route: Répondre à un commentaire

```php
#[Route('/commentaire/{commentaire}/repondre', name: 'app_commentaire_repondre', requirements: ['commentaire' => '\d+'], methods: ['POST'])]
public function repondreCommentaire(Commentaire $commentaire, Request $request, EntityManagerInterface $entityManager, BadWordFilterService $badWordFilter): Response
```

**Permissions**:
- Seul l'ARTISAN propriétaire de l'article peut répondre
- Ou un ADMIN

**Validation**:
```php
$contenu = trim((string) $request->request->get('contenu', ''));
$contentLength = function_exists('mb_strlen') ? mb_strlen($contenu) : strlen($contenu);
if ($contentLength < 5 || $contentLength > 255) {
    $this->addFlash('error', 'La reponse doit contenir entre 5 et 255 caracteres.');
    return $this->redirectToRoute('app_article_show', ['id' => $article?->getId()]);
}
```
- Utilise `mb_strlen` pour gérer les caractères UTF-8 (accents, emojis)

**Création de la réponse**:
```php
$reponse = (new Commentaire())
    ->setContenu($contenu)
    ->setDatepub(new \DateTime())
    ->setArticle($article)
    ->setUser($user)
    ->setParent($commentaire);  // ← Lien hiérarchique
```

---

#### Route: Liker un article

```php
#[Route('/{id}/like', name: 'app_article_like', methods: ['POST'])]
public function like(Article $article, Request $request, EntityManagerInterface $entityManager): Response
```

**Logique**:
```php
if ($article->isLikedBy($user)) {
    $article->removeLikedBy($user);
    $this->addFlash('success', 'Like retire.');
} else {
    $article->addLikedBy($user);
    $this->addFlash('success', 'Vous avez aime cet article.');
}
$entityManager->flush();
```
- Toggle: cliquer une fois = like, cliquer à nouveau = retire le like

---

#### Méthodes privées (helpers)

```php
private function canUserLikeArticle(mixed $user, ?Article $article): bool
{
    if (!$user instanceof User || !$article) {
        return false;
    }
    return $user->getRole() === User::ROLE_CLIENT
        && (!$article->getArtisan() || $article->getArtisan()->getId() !== $user->getId());
}
```
- Un CLIENT peut liker
- Mais pas son propre article (si c'est aussi un artisan)

```php
private function canUserReplyToComments(mixed $user, ?Article $article): bool
{
    if (!$user instanceof User || !$article) {
        return false;
    }
    if ($user->getRole() === User::ROLE_ADMIN) {
        return true;
    }
    return $user->getRole() === User::ROLE_ARTISAN
        && $article->getArtisan()
        && $article->getArtisan()->getId() === $user->getId();
}
```
- ADMIN peut toujours répondre
- ARTISAN peut répondre uniquement sur ses articles

---


### 2.2 PanierController.php

#### Route: Afficher le panier

```php
#[Route(name: 'app_panier_index', methods: ['GET'])]
public function index(SessionInterface $session, ArticleRepository $articleRepository): Response
```

**SessionInterface**:
- Service Symfony pour gérer les sessions PHP
- Stocke le panier côté serveur (pas dans les cookies)
- Persiste entre les requêtes

**Logique**:
```php
$panierIds = $session->get(self::SESSION_PANIER_KEY, []);
// SESSION_PANIER_KEY = 'panier_articles'

$articles = [];
$total = 0.0;
foreach ($panierIds as $id) {
    $article = $articleRepository->find($id);
    if ($article) {
        $articles[] = $article;
        $prix = $article->getPrix();
        $total += $prix !== null && $prix !== '' ? (float) $prix : 0.0;
    }
}
```
- Récupère les IDs depuis la session
- Charge les articles depuis la BDD
- Calcule le total

---

#### Route: Valider la commande (avec IA)

```php
#[Route('/valider', name: 'app_panier_valider', methods: ['GET', 'POST'])]
public function valider(
    SessionInterface $session, 
    ArticleRepository $articleRepository, 
    EntityManagerInterface $em, 
    Request $request,
    MailerInterface $mailer,
    PersonalizedMessageService $messageService
): Response
```

**Services injectés**:
- `MailerInterface $mailer`: Service Symfony pour envoyer des emails
- `PersonalizedMessageService $messageService`: **Service IA personnalisé** pour générer des messages

**Étapes**:

1. **Vérification du panier**:
```php
if (empty($articles)) {
    $this->addFlash('error', 'Votre panier est vide.');
    return $this->redirectToRoute('app_panier_index');
}
```

2. **Création du formulaire pré-rempli**:
```php
$form = $this->createForm(CommandeValidationType::class, [
    'nom' => $user->getNom(),
    'prenom' => $user->getPrenom(),
]);
```

3. **Création de la commande**:
```php
$commande = new Commande();
$commande->setNumero($data['numero']);
$commande->setDatecommande(new \DateTime());
$commande->setStatut('en_attente');
$commande->setTotal($total);
$commande->setAdresselivraison($data['adresselivraison']);
$commande->setModepaiement($data['modepaiement']);
$commande->setClient($user);
foreach ($articles as $article) {
    $commande->addArticle($article);
}
$em->persist($commande);
$em->flush();
```

4. **Génération du message personnalisé par IA**:
```php
$customerName = $user->getPrenom() . ' ' . $user->getNom();
$personalizedMessage = $messageService->generateOrderConfirmationMessage(
    $customerName,
    $articles,
    $total,
    $data['numero']
);
```
- Appelle l'API Hugging Face (Mistral-7B)
- Génère un message unique pour chaque commande
- Fallback si l'API échoue

5. **Envoi de l'email**:
```php
$email = (new TemplatedEmail())
    ->from($_ENV['MAIL_FROM'])
    ->to($user->getEmail())
    ->subject('Confirmation de votre commande n°' . $data['numero'])
    ->htmlTemplate('emails/order_confirmation.html.twig')
    ->context([
        'customerName' => $customerName,
        'orderNumber' => $data['numero'],
        'articles' => $articles,
        'total' => $total,
        'personalizedMessage' => $personalizedMessage,
        'deliveryAddress' => $data['adresselivraison'],
        'paymentMethod' => $data['modepaiement'],
    ]);

$mailer->send($email);
```

6. **Vidage du panier**:
```php
$session->set(self::SESSION_PANIER_KEY, []);
```

---

#### Route: Ajouter au panier

```php
#[Route('/ajouter/{id}', name: 'app_panier_ajouter', requirements: ['id' => '\d+'], methods: ['POST'])]
public function ajouter(int $id, SessionInterface $session, ArticleRepository $articleRepository, Request $request): Response
```

**Protection CSRF**:
```php
$token = $request->request->get('_token');
if (!$token || !$this->isCsrfTokenValid('panier_ajouter' . $id, $token)) {
    $this->addFlash('error', 'Token invalide.');
    return $this->redirectToRoute('app_article_index');
}
```

**Ajout à la session**:
```php
$panier = $session->get(self::SESSION_PANIER_KEY, []);
$panier[] = $id;
$session->set(self::SESSION_PANIER_KEY, $panier);
```

**Redirection intelligente**:
```php
$referer = $request->headers->get('Referer');
if ($referer) {
    return $this->redirect($referer);  // Retour à la page précédente
}
return $this->redirectToRoute('app_article_index');
```

---

#### Route: Retirer du panier

```php
#[Route('/retirer/{id}', name: 'app_panier_retirer', requirements: ['id' => '\d+'], methods: ['POST'])]
public function retirer(int $id, SessionInterface $session, Request $request): Response
```

**Suppression de l'ID**:
```php
$panier = $session->get(self::SESSION_PANIER_KEY, []);
$panier = array_values(array_filter($panier, fn ($i) => (int) $i !== $id));
$session->set(self::SESSION_PANIER_KEY, $panier);
```
- `array_filter`: Retire l'ID
- `array_values`: Réindexe le tableau (0, 1, 2...)

---

#### Route: Historique des commandes

```php
#[Route('/historique', name: 'app_panier_historique', methods: ['GET'])]
public function historique(CommandeRepository $commandeRepository): Response
```

**Requête personnalisée**:
```php
$commandes = $commandeRepository->findByClient($user);
```
- Méthode dans `CommandeRepository`
- Trie par date décroissante

---


## 🤖 PARTIE 3: LES SERVICES (INTELLIGENCE ARTIFICIELLE)

### 3.1 PersonalizedMessageService.php

#### Rôle
Génère des messages de confirmation de commande personnalisés en utilisant l'IA (Hugging Face API).

#### Injection de dépendances

```php
private HttpClientInterface $httpClient;

public function __construct(HttpClientInterface $httpClient)
{
    $this->httpClient = $httpClient;
}
```
- `HttpClientInterface`: Service Symfony pour faire des requêtes HTTP
- Injecté automatiquement par le conteneur de services

---

#### Méthode principale: generateOrderConfirmationMessage()

```php
public function generateOrderConfirmationMessage(
    string $customerName,
    array $articles,
    float $total,
    string $orderNumber
): string
```

**Étape 1: Construction de la liste d'articles**
```php
$articleList = [];
foreach ($articles as $article) {
    $articleList[] = $article->getTitre();
}
$articlesText = implode(', ', $articleList);
```

**Étape 2: Création du prompt pour l'IA**
```php
$prompt = sprintf(
    "Écris un message de confirmation de commande personnalisé et chaleureux en français pour %s. " .
    "La commande numéro %s contient: %s. Le montant total est de %.2f€. " .
    "Le message doit être court (3-4 phrases), professionnel mais amical, et remercier le client. " .
    "Ne pas inclure de signature.",
    $customerName,
    $orderNumber,
    $articlesText,
    $total
);
```

**Étape 3: Appel à l'API Hugging Face**
```php
$response = $this->httpClient->request('POST', 'https://api-inference.huggingface.co/models/mistralai/Mistral-7B-Instruct-v0.2', [
    'json' => [
        'inputs' => $prompt,
        'parameters' => [
            'max_new_tokens' => 150,      // Limite de tokens générés
            'temperature' => 0.7,          // Créativité (0 = déterministe, 1 = très créatif)
            'top_p' => 0.9,                // Nucleus sampling
        ]
    ],
    'timeout' => 10,  // Timeout de 10 secondes
]);
```

**Paramètres de génération**:
- `max_new_tokens`: Nombre maximum de tokens (mots) générés
- `temperature`: Contrôle la créativité
  - 0.0 = Réponses très prévisibles
  - 1.0 = Réponses très créatives/aléatoires
  - 0.7 = Bon équilibre
- `top_p`: Nucleus sampling - considère les tokens les plus probables jusqu'à atteindre p

**Étape 4: Extraction du texte généré**
```php
$data = $response->toArray();

if (isset($data[0]['generated_text'])) {
    $generatedText = $data[0]['generated_text'];
    // Retirer le prompt de la réponse
    $message = str_replace($prompt, '', $generatedText);
    $message = trim($message);
    
    if (!empty($message)) {
        return $message;
    }
}
```

**Étape 5: Fallback si l'API échoue**
```php
catch (\Exception $e) {
    // Si API fails, use fallback
}
return $this->generateFallbackMessage($customerName, $articles, $total, $orderNumber);
```

---

#### Méthode fallback: generateFallbackMessage()

```php
private function generateFallbackMessage(
    string $customerName,
    array $articles,
    float $total,
    string $orderNumber
): string
{
    $templates = [
        "Bonjour %s,\n\nNous avons bien reçu votre commande n°%s d'un montant de %.2f€. " .
        "Votre sélection de %d article(s) sera traitée avec soin par notre équipe. " .
        "Merci de votre confiance et à très bientôt !",
        
        "Cher(e) %s,\n\nVotre commande n°%s est confirmée ! " .
        "Nous préparons avec attention vos %d article(s) pour un montant total de %.2f€. " .
        "Merci pour votre achat et à bientôt sur notre plateforme !",
        
        "Merci %s !\n\nVotre commande n°%s (%.2f€) a été enregistrée avec succès. " .
        "Nos équipes s'occupent dès maintenant de préparer vos %d article(s). " .
        "Nous apprécions votre confiance !",
    ];

    $template = $templates[array_rand($templates)];  // Choix aléatoire
    
    return sprintf(
        $template,
        $customerName,
        $orderNumber,
        $total,
        count($articles)
    );
}
```

**Avantages du fallback**:
- Garantit toujours un message personnalisé
- Pas de dépendance critique à l'API externe
- Variété grâce aux templates multiples

---

### 3.2 BadWordFilterService.php

#### Rôle
Filtre les mots inappropriés dans les commentaires en utilisant:
1. Une liste personnalisée de mots interdits
2. L'API PurgoMalum (gratuite)

#### Configuration

```php
private HttpClientInterface $httpClient;
private array $customBadWords;

public function __construct(HttpClientInterface $httpClient, array $customBadWords = [])
{
    $this->httpClient = $httpClient;
    $this->customBadWords = $customBadWords;
}
```

**Injection des mots personnalisés**:
Dans `config/services.yaml`:
```yaml
services:
    App\Service\BadWordFilterService:
        arguments:
            $customBadWords: '%bad_words%'
```

Dans `config/bad_words.yaml`:
```yaml
parameters:
    bad_words:
        - 'mot1'
        - 'mot2'
        - 'lele'
```

---

#### Méthode principale: checkBadWords()

```php
public function checkBadWords(string $text): array
{
    if (empty(trim($text))) {
        return ['hasBadWords' => false, 'filteredText' => $text, 'source' => 'none'];
    }

    // Vérification liste personnalisée
    $customCheck = $this->checkCustomBadWords($text);
    if ($customCheck['hasBadWords']) {
        return $customCheck;
    }

    // Vérification API
    try {
        $response = $this->httpClient->request('GET', 'https://www.purgomalum.com/service/containsprofanity', [
            'query' => [
                'text' => $text,
            ],
            'timeout' => 5,
        ]);

        $containsProfanity = $response->getContent();
        $hasBadWords = strtolower(trim($containsProfanity)) === 'true';

        return [
            'hasBadWords' => $hasBadWords,
            'filteredText' => $text,
            'source' => $hasBadWords ? 'api' : 'none',
        ];
    } catch (\Exception $e) {
        // Fail open: si l'API échoue, on autorise le contenu
        return ['hasBadWords' => false, 'filteredText' => $text, 'source' => 'error'];
    }
}
```

**Retour**:
```php
[
    'hasBadWords' => bool,      // true si mots inappropriés détectés
    'filteredText' => string,   // Texte (peut être filtré)
    'source' => string          // 'custom', 'api', 'none', 'error'
]
```

---

#### Méthode: checkCustomBadWords()

```php
private function checkCustomBadWords(string $text): array
{
    if (empty($this->customBadWords)) {
        return ['hasBadWords' => false, 'filteredText' => $text, 'source' => 'none'];
    }

    $lowerText = mb_strtolower($text);
    
    foreach ($this->customBadWords as $badWord) {
        $lowerBadWord = mb_strtolower(trim($badWord));
        if (empty($lowerBadWord)) {
            continue;
        }
        
        // Détection partielle: "lele" dans "leleee", "lele123", etc.
        if (strpos($lowerText, $lowerBadWord) !== false) {
            return [
                'hasBadWords' => true,
                'filteredText' => $text,
                'source' => 'custom',
            ];
        }
    }

    return ['hasBadWords' => false, 'filteredText' => $text, 'source' => 'none'];
}
```

**Utilise `mb_strtolower`**:
- Gère correctement les caractères UTF-8 (accents, emojis)
- `strtolower` ne fonctionne que pour l'ASCII

---

#### Méthode: getFilteredText()

```php
public function getFilteredText(string $text): string
{
    if (empty(trim($text))) {
        return $text;
    }

    // Filtre les mots personnalisés
    $filteredText = $this->filterCustomBadWords($text);

    // Filtre avec l'API
    try {
        $response = $this->httpClient->request('GET', 'https://www.purgomalum.com/service/plain', [
            'query' => [
                'text' => $filteredText,
            ],
            'timeout' => 5,
        ]);

        return $response->getContent();
    } catch (\Exception $e) {
        return $filteredText;
    }
}
```

**Exemple**:
```php
Input:  "Ce produit est de la merde"
Output: "Ce produit est de la ****"
```

---

#### Méthode: filterCustomBadWords()

```php
private function filterCustomBadWords(string $text): string
{
    if (empty($this->customBadWords)) {
        return $text;
    }

    $filteredText = $text;
    
    foreach ($this->customBadWords as $badWord) {
        $badWord = trim($badWord);
        if (empty($badWord)) {
            continue;
        }
        
        // Remplace par des astérisques
        $replacement = str_repeat('*', mb_strlen($badWord));
        $filteredText = str_ireplace($badWord, $replacement, $filteredText);
    }

    return $filteredText;
}
```

**`str_ireplace`**:
- Remplacement insensible à la casse
- "LELE", "lele", "LeLe" → tous remplacés

---

#### Méthodes utilitaires

```php
public function addCustomBadWord(string $word): void
{
    $word = trim($word);
    if (!empty($word) && !in_array($word, $this->customBadWords, true)) {
        $this->customBadWords[] = $word;
    }
}

public function getCustomBadWords(): array
{
    return $this->customBadWords;
}
```

---

