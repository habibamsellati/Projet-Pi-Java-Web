# 🎨 AfkArt — Backend Symfony

## 📖 Description

AfkArt est une plateforme intelligente dédiée à l’économie circulaire, au recyclage et à l’art durable.  
Le projet permet aux utilisateurs de publier, acheter et gérer des produits recyclables tout en participant à des événements artistiques et écologiques.

Cette partie correspond au backend développé avec Symfony.

---

# 🚀 Fonctionnalités Principales

# 👤 Gestion des utilisateurs

## Fonctionnalités
- Inscription et authentification sécurisée
- Gestion des rôles :
  - Admin
  - Client
  - Artisan
- Validation des comptes par email
- Réinitialisation du mot de passe oublié
- Connexion avec Google OAuth
- Protection CAPTCHA contre les robots
- Génération automatique d’avatars IA selon le sexe
- Historique des utilisateurs supprimés
- Restauration des utilisateurs supprimés
- Suppression définitive des utilisateurs

## Fonctionnalités avancées
- Génération PDF de la liste des utilisateurs
- Calcul automatique des statistiques utilisateurs selon les rôles
- Dashboard administratif

## APIs utilisées
- Gmail SMTP API
- Google OAuth API
- CAPTCHA API
- PDF Export Service
- Avatar AI Generator

---

# ♻️ Gestion des produits recyclables

## Fonctionnalités
- Ajout des produits recyclables
- Modification et suppression des produits
- Gestion des catégories
- Upload des images
- Recherche et filtrage

## Fonctionnalités avancées
- Génération d’images par Intelligence Artificielle
- Estimation intelligente des prix
- Recommandation automatique des artisans
- Détection intelligente des formulaires
- Gestion du calendrier
- Mailing automatique

## APIs et IA utilisées
- Artificial Intelligence Image Generator
- NLP Detection
- Mailing API

---

# 🚚 Gestion des livraisons

## Fonctionnalités
- Création des livraisons
- Suivi des livraisons
- Gestion des états

## Contraintes métiers
- Maximum 3 livraisons actives
- Livraison non modifiable
- Livraison non supprimable

## APIs utilisées
- Google Maps API
- Route Calculation API

---

# 🛒 Gestion des commandes

## Fonctionnalités
- Création des commandes
- Gestion du panier
- Historique des commandes
- Validation des achats

## Fonctionnalités avancées
- Like / Dislike
- Gestion des commentaires
- Réactions Emoji
- Traduction automatique
- Détection des mots interdits
- Messages personnalisés

## APIs utilisées
- Translation API
- Bad Words Filter API
- Messaging API

---

# 🖼️ Gestion des articles

## Fonctionnalités
- Publication des articles
- Modification et suppression
- Gestion des commentaires
- Recherche dynamique

## Fonctionnalités avancées
- Like / Dislike
- Réactions Emoji
- Traduction multilingue
- Détection automatique des contenus toxiques

---

# 📩 Gestion des réclamations

## Fonctionnalités
- Création des réclamations
- Traitement des réclamations
- Gestion des statuts
- Réponses administratives
- Historique des réclamations

## Fonctionnalités avancées
- Résumé automatique par Intelligence Artificielle
- Mailing d’avertissement automatique
- Statistiques dynamiques
- Visioconférence

## APIs utilisées
- Bad Words API
- Mailing API
- Video Conference API
- AI Summary API

---

# 🎉 Gestion des événements et réservations

## Fonctionnalités
- Création des événements
- Réservation des places
- Gestion des participants
- Génération des tickets PDF
- Génération QR Code
- Paiement en ligne des réservations

## Fonctionnalités métiers avancées
- Contrôle dynamique des capacités en temps réel
- Auto-annulation des réservations expirées
- Calcul automatique des revenus totaux

## APIs utilisées
- QRServer API
- Gemini API
- Wikipedia API
- PDF Export Service
- Payment API

---

# 🛠️ Technologies utilisées

- Symfony 6
- PHP 8
- Doctrine ORM
- MySQL
- Twig
- REST API
- JWT Authentication
- Composer

---

# 🔐 Sécurité

- Authentification sécurisée
- JWT Security
- CAPTCHA Protection
- Google OAuth
- Gestion des rôles
- Validation des données
- Protection CSRF
- Hashage des mots de passe

---

# ⚙️ Installation du projet

## 1️⃣ Cloner le projet

```bash
git clone https://github.com/your-repository/afkart-symfony.git
```

---

## 2️⃣ Installer les dépendances

```bash
composer install
```

---

## 3️⃣ Configurer l’environnement

Créer un fichier `.env.local`

```env
DATABASE_URL="mysql://root:password@127.0.0.1:3306/afkart"
```

---

## 4️⃣ Créer la base de données

```bash
php bin/console doctrine:database:create
```

---

## 5️⃣ Exécuter les migrations

```bash
php bin/console doctrine:migrations:migrate
```

---

## 6️⃣ Lancer le serveur Symfony

```bash
symfony server:start
```

---

# 📂 Architecture du projet

```bash
src/
 ├── Controller/
 ├── Entity/
 ├── Repository/
 ├── Security/
 ├── Service/
 ├── API/
 ├── Form/
 └── Utils/
```

---

# 👨‍💻 Équipe

Projet développé dans le cadre du projet intégré AfkArt.
