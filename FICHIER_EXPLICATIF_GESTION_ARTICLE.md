# Fichier explicatif - API + IA (Gestion Article)

## 1) Objet du module
Ce module Symfony 6.4 gere la partie **gestion d'articles** avec 3 entites principales:
- `Article`
- `Commentaire`
- `Commande`

Il inclut aussi des endpoints **API/IA** pour automatiser certaines actions (generation d'image, detection de categorie, estimation, chatbot).

## 2) Entites principales

### `Article` (`src/Entity/Article.php`)
Champs importants:
- `id`, `titre`, `contenu`, `date`, `image`, `prix`, `categorie`
- relations: `commentaires`, `commandes`, `artisan`, `user`, `likedBy`

Fonctionnalites liees:
- CRUD article
- like/unlike article
- ajout image locale (`public/image`)
- recherche, tri, pagination

### `Commentaire` (`src/Entity/Commentaire.php`)
Champs importants:
- `id`, `contenu`, `datepub`, `likes`, `dislikes`
- relations: `article`, `user`, `parent`, `replies`

Fonctionnalites liees:
- ajout commentaire client
- reponse artisan/admin
- modification/suppression par l'auteur
- reactions like/dislike
- filtrage des mots interdits

### `Commande` (`src/Entity/Commande.php`)
Champs importants:
- `id`, `numero`, `datecommande`, `statut`, `total`
- `adresselivraison`, `modepaiement`
- relations: `articles`, `livraison`, `client`

Fonctionnalites liees:
- creation/validation commande
- liaison articles <-> commande
- suivi du statut et de la livraison

## 3) Controllers importants
- `src/Controller/ArticleController.php`  
  CRUD article + commentaires + likes + reactions + droits par role.

- `src/Controller/AIController.php` (`/api/ai/...`)  
  Endpoints IA:
  - `POST /api/ai/generate-image`
  - `POST /api/ai/detect-category`
  - `POST /api/ai/estimate-price`
  - `POST /api/ai/recommend-artisans`

- `src/Controller/AIChatController.php`  
  Endpoints:
  - `POST /api/ai/chat`
  - `POST /api/ai/copilot`

- `src/Controller/Api/ApiDocsController.php`  
  Page de documentation: `GET /api/docs`

## 4) Service IA principal
Fichier: `src/Service/AIService.php`

Logique:
1. Essaye OpenAI (si `OPENAI_API_KEY` existe)
2. Sinon HuggingFace (si `HUGGINGFACE_API_KEY` existe)
3. Sinon Pollinations (sans cle)
4. Sinon fallback local (SVG)

Images enregistrees dans:
- `public/uploads/ai_images`

## 5) Bundles installes dans le projet
Fichier de reference: `config/bundles.php`

- `Symfony\Bundle\FrameworkBundle\FrameworkBundle`
- `Doctrine\Bundle\DoctrineBundle\DoctrineBundle`
- `Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle`
- `Symfony\Bundle\MakerBundle\MakerBundle` (dev)
- `Symfony\Bundle\TwigBundle\TwigBundle`
- `Symfony\Bundle\SecurityBundle\SecurityBundle`
- `Knp\Bundle\PaginatorBundle\KnpPaginatorBundle`

## 6) Packages principaux (`composer.json`)
- Doctrine ORM + Migrations
- Twig, Security, Validator, Form, Mailer, Notifier, Translation
- `knplabs/knp-paginator-bundle` (pagination)
- `endroid/qr-code` (QR)
- `dompdf/dompdf` (PDF)
- `symfony/http-client` (appels API externes)
- `symfony/twilio-notifier`

## 7) Commandes Symfony/Doctrine a presenter (important prof)

### A. Installation et environnement
```bash
composer install
php bin/console about
php bin/console debug:router
php bin/console debug:container
```

### B. Creation d'entites (si besoin de re-generer)
```bash
php bin/console make:entity Article
php bin/console make:entity Commentaire
php bin/console make:entity Commande
```

### C. Migration base de donnees
```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:migrations:status
```

### D. Verification Doctrine
```bash
php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:list
```

### E. Verification des routes API/IA
```bash
php bin/console debug:router | findstr api
```

### F. Verification bundle pagination (KNP)
```bash
php bin/console debug:container knp_paginator
```

### G. Lancer le serveur
```bash
symfony server:start
```
ou
```bash
php -S 127.0.0.1:8000 -t public
```

## 8) Commandes Composer (bundles) a citer en soutenance

Exemples d'installation (historique technique):
```bash
composer require doctrine/doctrine-bundle
composer require doctrine/doctrine-migrations-bundle
composer require --dev symfony/maker-bundle
composer require knplabs/knp-paginator-bundle
composer require symfony/security-bundle
composer require symfony/twig-bundle
composer require symfony/http-client
composer require dompdf/dompdf
composer require endroid/qr-code
```

## 9) Variables d'environnement IA (si demo API IA)
Configurer dans `.env.local`:
```dotenv
OPENAI_API_KEY=...
OPENAI_IMAGE_MODEL=dall-e-3
HUGGINGFACE_API_KEY=...
```

## 10) Resume pour presentation
- Le module "Gestion Article" est base sur 3 entites metier: `Article`, `Commentaire`, `Commande`.
- Il combine MVC Symfony + Doctrine + validation + securite par roles.
- La partie IA expose des endpoints REST internes (`/api/ai/...`) pour aider la creation/classification.
- Les bundles et commandes ci-dessus permettent de prouver l'implementation technique complete devant le professeur.
