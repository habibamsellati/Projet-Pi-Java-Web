  # Explication complete du projet Symfony - Gestion d'articles

## 1) Vue d'ensemble du projet

Ton projet est une application Symfony 6.4 organisee en architecture MVC + services.

- Modele metier: `Entity` + `Repository` (Doctrine ORM)
- Controle applicatif: `Controller`
- Saisie utilisateur: `Form`
- Presentation: `Twig` (`templates/`)
- Services techniques/metier: `src/Service/`
- Configuration: `config/`
- Evolution BDD: `migrations/`

Les 3 entites coeur du module sont:
- `Article`
- `Commentaire`
- `Commande`

Fonctionnalites couvertes:
- CRUD Article
- Commentaires + reponses
- Like/Dislike sur commentaires (avec emojis cote UI)
- Like article
- Ajout au panier + validation commande
- Historique commandes client
- Pagination (KnpPaginatorBundle)
- Export PDF (Dompdf)
- Statistiques back-office
- Traduction automatique des commentaires (API MyMemory cote front)
- Filtrage de mots interdits (PurgoMalum + liste custom)
- Email de confirmation commande avec message personnalise par IA

## 2) Bundles et dependances utilises

### 2.1 Bundles charges (`config/bundles.php`)

- `FrameworkBundle`: coeur Symfony
- `DoctrineBundle`: integration ORM Doctrine
- `DoctrineMigrationsBundle`: migrations SQL versionnees
- `TwigBundle`: rendu HTML via Twig
- `SecurityBundle`: authentification/autorisation
- `KnpPaginatorBundle`: pagination des listes
- `MakerBundle` (dev): generation de code (entites, controllers, forms, etc.)

### 2.2 Librairies metier importantes (`composer.json`)

- `dompdf/dompdf`: generation PDF
- `knplabs/knp-paginator-bundle`: pagination
- `symfony/http-client`: appels API externes (IA/profanity)
- `symfony/mailer`: envoi d'emails
- `symfony/form`, `symfony/validator`, `symfony/security-bundle`, etc.

## 3) Configuration globale

### 3.1 Routage

`config/routes.yaml`:
- charge tous les controllers en mode `type: attribute`
- donc les routes sont definies directement dans les classes via `#[Route(...)]`

### 3.2 Doctrine

`config/packages/doctrine.yaml`:
- connexion DB via `DATABASE_URL`
- mapping par attributs PHP (`type: attribute`) sur `src/Entity`

### 3.3 Services

`config/services.yaml`:
- `autowire: true` et `autoconfigure: true`
- toutes les classes de `src/` (sauf `Entity`, `Kernel`, etc.) deviennent des services injectables

### 3.4 Mailer

`config/packages/mailer.yaml`:
- DSN SMTP via `MAILER_DSN`

Important securite:
- ton `.env` contient des cles/identifiants sensibles (SMTP, API). En pratique, il faut les sortir du depot Git et les stocker dans des variables d'environnement/secrets Symfony.

## 4) Entites metier (lecture pedagogique)

## 4.1 `src/Entity/Article.php`

Role:
- represente un article publie (titre, contenu, image, prix, categorie, auteur artisan).

Attributs Doctrine importants:
- `#[ORM\Entity(repositoryClass: ArticleRepository::class)]`: entite rattachee a `ArticleRepository`
- `#[ORM\Id]`, `#[ORM\GeneratedValue]`, `#[ORM\Column]`: cle primaire auto-incrementee
- `#[ORM\Column(type: Types::TEXT)]`: contenu long
- `#[ORM\ManyToOne(inversedBy: 'articles')]` vers `User $artisan`: auteur artisan
- `#[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'article', cascade: ['remove'])]`: suppression article supprime ses commentaires
- `#[ORM\ManyToMany(targetEntity: Commande::class, mappedBy: 'articles')]`: relation N-N article<->commande
- `#[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'likedArticles')]` + `JoinTable(article_like)`: likes d'articles

Validations:
- `#[Assert\NotBlank]`, `#[Assert\Length]` sur titre/contenu
- `#[Assert\GreaterThanOrEqual(0)]` sur prix

Methodes cle:
- getters/setters classiques
- `addCommentaire/removeCommentaire`: maintiennent la coherence bi-directionnelle
- `addCommande/removeCommande`: idem avec `Commande`
- `isLikedBy(User $user)`: verifie si un user a deja like
- `addLikedBy/removeLikedBy`: synchronisent avec `User::addLikedArticle/removeLikedArticle`

## 4.2 `src/Entity/Commentaire.php`

Role:
- commentaire poste par un client sur un article
- supporte les reponses (thread parent/enfant)
- supporte les reactions like/dislike

Relations:
- `ManyToOne` vers `Article`
- `ManyToOne` vers `User` (auteur)
- auto-relation:
  - `parent` (commentaire parent)
  - `replies` (reponses)
- `OneToMany` vers `CommentaireReaction`

Logique:
- `parent_id` nullable permet commentaire racine ou reponse
- `onDelete: CASCADE` sur parent/reactions evite les orphelins

## 4.3 `src/Entity/Commande.php`

Role:
- commande client finalisee depuis le panier.

Champs metier:
- `numero` (utilise ici comme numero tel dans le formulaire)
- `datecommande`
- `statut` (`en_attente`, `valide`, `invalide`)
- `total`
- `adresselivraison`
- `modepaiement`

Relations:
- `ManyToMany` vers `Article` (une commande contient plusieurs articles)
- `ManyToOne` vers `User $client`
- `OneToOne` vers `Livraison` (module livraison)

Methodes:
- `addArticle/removeArticle` pour manipuler le panier persiste
- `setLivraison` maintient coherence inverse

## 4.4 `src/Entity/CommentaireReaction.php`

Role:
- stocke une reaction utilisateur sur un commentaire.

Points cle:
- constantes:
  - `TYPE_LIKE = 1`
  - `TYPE_DISLIKE = -1`
- contrainte unique DB:
  - `uniq_commentaire_user_reaction(commentaire_id, user_id)`
  - donc un user ne peut avoir qu'une reaction par commentaire
- `setType(int $type)` valide strictement le type et leve exception sinon
- `createdAt` initialise en `DateTimeImmutable` au constructeur

## 4.5 `src/Entity/User.php` (partie utile au module)

Ce module depend surtout de:
- `role` pour les autorisations (`CLIENT`, `ARTISANT`, `ADMIN`, `LIVREUR`)
- collections:
  - `commentaires`
  - `commentaireReactions`
  - `articles` (artisan)
  - `likedArticles`
- interface security Symfony:
  - `getRoles()`, `getPassword()`, `getUserIdentifier()`

## 5) Repositories

## 5.1 `src/Repository/ArticleRepository.php`

Methodes metier:
- `createSearchQueryBuilder(...)`:
  - filtre `search` sur titre/contenu/nom artisan
  - filtre categorie
  - filtre artisan
  - tri multi-criteres (date/titre/prix asc/desc)
- `searchWithFilters(...)`: execute le QueryBuilder
- `getArtisansWithArticles()`:
  - renvoie auteurs distincts pour alimenter le filtre UI
- `getStatsByCategorie()`:
  - aggregation `COUNT` par categorie
- `getTopArtisans($limit)`:
  - top auteurs par nombre d'articles
- `getSimilarArticles($article, $limit)`:
  - priorite meme categorie
  - fallback recents si pas assez de resultats

## 5.2 `src/Repository/CommandeRepository.php`

Methodes:
- `searchWithFilters($search, $statut)`:
  - back-office commandes avec recherche client/numero + filtre statut
- `findByClient(User $client)`:
  - historique commande du client connecte
  - precharge les articles (`leftJoin + addSelect`)
- `getStatsByStatut()`:
  - agregat pour dashboard admin
- `getTotalRevenue()`:
  - somme `total` pour commandes `valide`

## 5.3 `src/Repository/CommentaireReactionRepository.php`

Methodes:
- `getStatsForArticle($articleId)`:
  - calcule likes/dislikes par commentaire via `SUM(CASE WHEN ...)`
- `getUserReactionsForArticle($articleId, $userId)`:
  - map `commentId => type` pour afficher l'etat actif (bouton like/dislike)

## 5.4 `src/Repository/CommentaireRepository.php`

- repository standard genere par Maker
- pas de logique custom actuellement

## 6) Forms

## 6.1 `src/Form/Article1Type.php`

Construit formulaire Article:
- `titre`, `contenu`, `categorie`, `prix`, `image`
- `image` est `mapped: false`:
  - le champ fichier n'est pas mappe automatiquement vers l'entite
  - upload gere manuellement dans controller

## 6.2 `src/Form/CommentaireType.php`

- un seul champ `contenu` (textarea)
- contraintes UX (`minlength`, `maxlength`) cote front
- validation metier principale reste sur l'entite + controle serveur

## 6.3 `src/Form/CommandeValidationType.php`

- formulaire de validation panier
- `nom` + `prenom` affiches en lecture seule (`disabled: true`)
- saisie `numero`, `adresselivraison`, `modepaiement`
- `data_class: null` car formulaire mappe un tableau, pas une entite directe

## 7) Services metier/API

## 7.1 `src/Service/BadWordFilterService.php`

Responsabilite:
- detecter et filtrer les mots inappropries.

Pipeline:
1. si texte vide -> pas d'alerte
2. check liste custom (`config/bad_words.yaml`)
3. si clean, appel API PurgoMalum (`containsprofanity`)
4. en erreur API -> mode "fail open" (autorise le texte pour ne pas bloquer la feature)

Methodes:
- `checkBadWords(string $text): array`
- `getFilteredText(string $text): string` (remplacement via API `/plain`)
- utilitaires pour liste custom

## 7.2 `src/Service/PersonalizedMessageService.php`

Responsabilite:
- generer le message personnalise de confirmation commande.

Pipeline:
1. construit prompt (client, articles, total, numero)
2. appel HTTP POST Hugging Face Inference API (Mistral)
3. si succes: extrait `generated_text`
4. sinon fallback local par templates

Methodes:
- `generateOrderConfirmationMessage(...)`
- `generateFallbackMessage(...)`

## 8) Controllers (coeur fonctionnel)

## 8.1 `src/Controller/ArticleController.php`

Route de base:
- `#[Route('/article')]`

Constante:
- `ARTICLE_CATEGORIES`: categories proposees UI

### Methode `index(...)`

Flux:
1. lit user connecte
2. calcule drapeaux role (`isClient`, `isArtisan`, `isAdmin`)
3. recupere filtres query string (`recherche`, `categorie`, `auteur`, `tri`)
4. construit query via `ArticleRepository::createSearchQueryBuilder`
5. pagine avec `KnpPaginatorInterface` (6 articles/page)
6. charge categories + liste artisans
7. render `templates/article/index.html.twig`

Remarque code:
- la ligne d'affectation `$user = $user instanceof User ? $user : null;` est dupliquee plusieurs fois dans `index` (redundant, sans effet fonctionnel)

### Methode `new(...)`

Flux:
1. interdit creation pour role client
2. instancie Article, initialise date
3. cree formulaire `Article1Type`
4. si submit valide:
  - choisit artisan proprietaire
  - gere upload image (`SluggerInterface`, `move()` vers `public/image`)
  - persist + flush
5. redirect index

### Methode `show(...)`

Flux:
1. prepare form commentaire
2. controle role: seuls clients commentent
3. si submit:
  - passe filtre anti-insultes (`BadWordFilterService`)
  - associe article + user + date
  - persist + flush
4. charge stats reactions commentaires
5. charge reaction perso user
6. calcule droits like article / reponse commentaires
7. charge articles similaires
8. render `article/show.html.twig`

### Methode `reactToComment(...)`

Flux:
1. autorise seulement client connecte
2. verifie CSRF token
3. convertit `type` route (`like|dislike`) en constante
4. cherche reaction existante user+commentaire
5. logique toggle:
  - meme type: supprime reaction
  - type different: met a jour
  - aucune: cree nouvelle reaction
6. flush + redirect page article

### Methode `repondreCommentaire(...)`

Flux:
1. autorise artisan proprietaire de l'article (ou admin)
2. verifie CSRF
3. valide longueur texte (5..255)
4. filtre mots interdits
5. cree nouveau `Commentaire` avec `parent`
6. persist + flush

### Methode `like(...)` (like article)

Flux:
1. verifie CSRF
2. controle droits (client non auteur)
3. toggle like article (`addLikedBy/removeLikedBy`)
4. flush

### Methode `favorite(...)`

- actuellement: ajoute juste un flash + redirection
- pas de persistance de favoris distincte du like

### Methode `edit(...)`

Flux:
1. auth obligatoire
2. client interdit
3. artisan ne peut editer que ses articles
4. formulaire + upload image
5. suppression ancienne image locale si remplacee
6. flush

### Methodes commentaire edit/suppression

- `modifierCommentaire(...)`: seulement client auteur du commentaire
- `supprimerCommentaire(...)`: idem + CSRF

### Methode `delete(...)` article

- controle role + propriete
- CSRF
- remove + flush

### Helpers prives

- `canUserLikeArticle(...)`
- `canUserReplyToComments(...)`

Ces methodes centralisent la logique d'autorisation metier.

## 8.2 `src/Controller/PanierController.php`

Route de base:
- `#[Route('/panier')]`

Constante:
- `SESSION_PANIER_KEY = 'panier_articles'`

### Methode `index(...)`

1. autorise role client
2. lit IDs article du panier depuis session
3. charge articles depuis repo
4. calcule total
5. render `panier/index.html.twig`

### Methode `valider(...)`

1. autorise client
2. reconstitue panier + total
3. si panier vide -> erreur
4. affiche formulaire `CommandeValidationType`
5. si valide:
  - cree `Commande`
  - set statut `en_attente`
  - associe client + articles
  - persist + flush
6. envoi email confirmation:
  - construit message personnalise via `PersonalizedMessageService`
  - envoie `TemplatedEmail` avec `templates/emails/order_confirmation.html.twig`
7. en erreur mail: n'annule pas commande
8. vide panier session
9. redirect historique

### Methode `ajouter(...)`

1. autorise client
2. verifie CSRF
3. ajoute ID article en session
4. flash succes

### Methode `retirer(...)`

1. verifie CSRF
2. retire ID article du tableau session
3. flash succes

### Methode `historique(...)`

1. autorise client
2. charge commandes client via `findByClient`
3. render `panier/historique.html.twig`

## 8.3 `src/Controller/BackController.php` (parties articles/commandes/stats/pdf)

### `statistiques(...)`

- agrege:
  - counts globaux (articles, commandes, reclamations, commentaires)
  - stats articles par categorie
  - top artisans
  - stats commandes par statut + CA valide
  - stats reclamations par statut
- render `admin/statistiques.html.twig`

### `articles(...)`

- filtre/recherche/sort des articles pour back-office
- render `admin/articles_back.html.twig`

### `deleteArticle(...)` / `deleteComment(...)`

- actions admin protegees par CSRF

### `commandes(...)`

- liste commandes avec filtres recherche/statut
- render `admin/commandes_back.html.twig`

### `listeValidee(...)`

- liste commandes deja validees

### `validerCommande(...)` / `invaliderCommande(...)`

- change statut commande apres CSRF

### `commandesPdf(...)`

1. charge donnees filtrees
2. render html Twig `admin/commandes_pdf.html.twig`
3. conversion PDF via `Dompdf`
4. retourne flux PDF HTTP

## 9) Templates et logique front

## 9.1 `templates/article/index.html.twig`

Fonctions:
- filtres recherche/categorie/auteur/tri
- affichage pagine
- boutons selon role:
  - client: ajout panier
  - artisan/admin: ajout, edition, suppression
- rendu pagination via `{{ knp_pagination_render(articles) }}` (KnpPaginatorBundle)

## 9.2 `templates/article/show.html.twig`

Fonctions:
- affichage article detaille
- commentaires + reponses
- like/dislike commentaires
- emojis dans saisie commentaire/reponse
- traduction commentaire cote navigateur via API MyMemory
- prefiltrage client-side profanity (PurgoMalum)

Note metier:
- la verite metier reste cote serveur (`ArticleController` + service), le JS est une couche UX supplementaire

## 9.3 `templates/panier/*.twig`

- `index`: recap panier + retrait + lien validation
- `valider`: recap + formulaire commande
- `historique`: liste commandes utilisateur

## 9.4 `templates/emails/order_confirmation.html.twig`

- email HTML avec:
  - message personnalise IA
  - details commande
  - liste articles
  - total

## 9.5 Back templates

- `admin/commandes_back.html.twig`: table de gestion, filtres, actions valider/invalider, export PDF
- `admin/commandes_pdf.html.twig`: version printable PDF
- `admin/statistiques.html.twig`: dashboard stat avec barres
- `admin/articles_back.html.twig`: moderation articles/commentaires

## 10) Migrations SQL (structure generee)

Fichiers cle:
- `migrations/Version20260217235900.php`
  - cree table `commentaire_reaction`
  - FK vers `commentaire` et `user`
  - contrainte unique user+commentaire
- `migrations/Version20260218001000.php`
  - ajoute `parent_id` a `commentaire` pour reponses
- `migrations/Version20260218120000.php`
  - cree table pivot `article_like`
- `migrations/Version20260219214623.php`
  - ajustements index/FK et alignements schema

## 11) Commandes Symfony utilisees (API, metier, bundles)

Ces commandes sont celles typiquement utilisees dans ton projet (et visibles dans ta doc technique):

- `composer require ...`
  - ajoute dependances (bundles/librairies)
- `php bin/console cache:clear`
  - regenere cache container/routes
- `php bin/console doctrine:migrations:migrate`
  - applique migrations SQL
- `php bin/console doctrine:schema:validate`
  - verifie coherence mapping Doctrine <-> BDD
- `php bin/console debug:router`
  - liste routes actives
- `php bin/console debug:container <service>`
  - inspecte service/bundle dans container
- `php bin/console app:check-pending-reclamations`
  - commande metier custom (module reclamations)

Role de generation de fichiers (MakerBundle):
- `make:entity` -> genere `src/Entity/*` (+ update migrations)
- `make:controller` -> genere `src/Controller/*`
- `make:form` -> genere `src/Form/*`
- `make:migration` -> genere `migrations/Version*.php`

## 12) Logique metier globale (scenario bout en bout)

Scenario principal commande:
1. client parcourt articles pagines
2. ajoute des articles au panier (session)
3. valide panier via formulaire
4. controller cree `Commande` en BDD
5. service IA genere message personnalise
6. mailer envoie email HTML de confirmation
7. client consulte historique des commandes
8. admin valide/invalide commandes et peut exporter en PDF

Scenario commentaires:
1. client poste commentaire
2. filtre anti-insultes cote client puis serveur
3. artisan/admin peut repondre
4. clients peuvent like/dislike commentaires
5. commentaires peuvent etre traduits cote front (MyMemory)

## 13) Points de vigilance techniques

- Encodage: plusieurs caracteres accentues apparaissent mal dans certains fichiers (probleme UTF-8 probable).
- `ArticleController::index`: affectation `$user` repetee inutilement.
- `favorite()` ne persiste pas de vrai favori (seulement flash message).
- Secrets dans `.env` exposes: a securiser immediatement.
- Traduction MyMemory est uniquement front-end et depend du navigateur/API externe.

## 14) Arborescence utile du module

- `src/Entity/Article.php`
- `src/Entity/Commentaire.php`
- `src/Entity/Commande.php`
- `src/Entity/CommentaireReaction.php`
- `src/Controller/ArticleController.php`
- `src/Controller/PanierController.php`
- `src/Controller/BackController.php`
- `src/Repository/ArticleRepository.php`
- `src/Repository/CommandeRepository.php`
- `src/Repository/CommentaireReactionRepository.php`
- `src/Form/Article1Type.php`
- `src/Form/CommentaireType.php`
- `src/Form/CommandeValidationType.php`
- `src/Service/BadWordFilterService.php`
- `src/Service/PersonalizedMessageService.php`
- `templates/article/*`
- `templates/panier/*`
- `templates/admin/commandes_back.html.twig`
- `templates/admin/commandes_pdf.html.twig`
- `templates/admin/statistiques.html.twig`
- `templates/emails/order_confirmation.html.twig`
- `config/bundles.php`
- `config/services.yaml`
- `config/packages/doctrine.yaml`
- `config/packages/mailer.yaml`
- `config/routes.yaml`
- `migrations/Version20260217235900.php`
- `migrations/Version20260218001000.php`
- `migrations/Version20260218120000.php`

## 15) Conclusion

Ton module Symfony est globalement bien structure et couvre un cycle metier complet article->commentaire->commande avec des extensions modernes (IA, APIs externes, PDF, pagination, stats).

La separation des couches est bonne:
- Entities: modele de donnees et relations
- Repositories: requetes metier
- Controllers: orchestration des flux
- Services: logique transversale (IA, filtrage)
- Twig: presentation role-aware

Les ameliorations prioritaires seraient:
- securiser les secrets
- corriger l'encodage UTF-8
- supprimer redondances mineures controller
- ajouter vraie persistance pour "favoris" si besoin metier
