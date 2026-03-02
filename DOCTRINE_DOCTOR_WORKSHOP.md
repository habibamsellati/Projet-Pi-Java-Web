# Guide Doctrine Doctor - Workshop

**Date**: 2 mars 2026  
**Projet**: Projet-Pi-Java-Web-Gestion_user_MA  
**Objectif**: Améliorer les performances (intégrité, sécurité, configuration)

---

## ✅ Étape 1: Installation (TERMINÉE)

### Commandes exécutées:
```bash
composer require ahmed-bhs/doctrine-doctor:^1.0 webmozart/assert:^1.11 --with-all-dependencies
```

### Configuration ajoutée dans `config/bundles.php`:
```php
AhmedBhs\DoctrineDoctor\DoctrineDoctorBundle::class => ['dev' => true],
```

### Cache vidé:
```bash
php bin/console cache:clear
```

---

## 📋 Étape 2: Accéder à Doctrine Doctor

### 1. Démarrer le serveur Symfony
```bash
symfony server:start
```

Ou si vous utilisez PHP built-in server:
```bash
php -S localhost:8000 -t public
```

### 2. Accéder à une page de votre application
Ouvrez votre navigateur et allez sur:
```
http://localhost:8000
```

Ou n'importe quelle page de votre application, par exemple:
- `http://localhost:8000/article`
- `http://localhost:8000/evenement`
- `http://localhost:8000/reclamation`

### 3. Ouvrir le Symfony Web Profiler
- Regardez en bas de la page
- Vous verrez une barre d'outils (toolbar) avec des icônes
- Cliquez sur n'importe quelle icône pour ouvrir le profiler

### 4. Cliquer sur "Doctrine Doctor"
- Dans le profiler, cherchez l'onglet **"Doctrine Doctor"**
- Cliquez dessus pour voir l'analyse

---

## 🔍 Étape 3: Analyser les Problèmes

Doctrine Doctor va analyser 4 catégories:

### 1. **Integrity (Intégrité)**
Problèmes de cohérence des données:
- Relations manquantes
- Clés étrangères incorrectes
- Contraintes d'intégrité

**Exemple de problème:**
```
Missing foreign key constraint on table 'commande' 
for column 'client_id' referencing 'user(id)'
```

### 2. **Security (Sécurité)**
Problèmes de sécurité:
- Colonnes sensibles non chiffrées
- Permissions incorrectes
- Données exposées

**Exemple de problème:**
```
Column 'motdepasse' in table 'user' should be hashed
```

### 3. **Configuration**
Problèmes de configuration Doctrine:
- Index manquants
- Types de colonnes non optimaux
- Longueurs de champs

**Exemple de problème:**
```
Missing index on column 'email' in table 'user'
```

### 4. **Slowest Queries (Requêtes lentes)**
Requêtes qui prennent du temps:
- Requêtes sans index
- Requêtes N+1
- Requêtes complexes

---

## 🛠️ Étape 4: Résoudre les Problèmes

### Processus de correction:

#### 1. Identifier le problème
Prenez une capture d'écran du problème dans Doctrine Doctor

#### 2. Corriger dans l'entité
Exemple: Ajouter un index manquant

**Avant:**
```php
#[ORM\Column(length: 255)]
private ?string $email = null;
```

**Après:**
```php
#[ORM\Column(length: 255)]
#[ORM\Index(name: 'idx_user_email')]
private ?string $email = null;
```

#### 3. Mettre à jour la base de données
```bash
php bin/console doctrine:schema:update --force
```

Ou créer une migration:
```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

#### 4. Vider le cache
```bash
php bin/console cache:clear
```

#### 5. Redémarrer le serveur
```bash
symfony server:stop
symfony server:start
```

#### 6. Vérifier dans le profiler
- Rechargez la page
- Ouvrez le profiler
- Vérifiez que le problème est résolu

---

## 📝 Problèmes Courants et Solutions

### Problème 1: Relations sans clé étrangère

**Symptôme:**
```
Missing foreign key constraint on 'article.user_id'
```

**Solution:**
```php
#[ORM\ManyToOne(targetEntity: User::class)]
#[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
private ?User $user = null;
```

---

### Problème 2: Index manquant

**Symptôme:**
```
Missing index on frequently queried column 'statut'
```

**Solution:**
```php
#[ORM\Column(length: 50)]
#[ORM\Index(name: 'idx_commande_statut')]
private ?string $statut = null;
```

---

### Problème 3: Type de colonne non optimal

**Symptôme:**
```
Column 'prix' should use DECIMAL instead of VARCHAR
```

**Solution:**
```php
// Avant
#[ORM\Column(length: 255)]
private ?string $prix = null;

// Après
#[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
private ?string $prix = null;
```

---

### Problème 4: Longueur de colonne excessive

**Symptôme:**
```
Column 'nom' has excessive length (255) for typical data
```

**Solution:**
```php
// Avant
#[ORM\Column(length: 255)]
private ?string $nom = null;

// Après
#[ORM\Column(length: 100)]
private ?string $nom = null;
```

---

## 🎯 Checklist de Vérification

Après chaque correction:

- [ ] Capture d'écran du problème AVANT
- [ ] Modification de l'entité
- [ ] Mise à jour de la base de données
- [ ] Cache vidé
- [ ] Serveur redémarré
- [ ] Capture d'écran APRÈS (problème résolu)
- [ ] Documentation de la correction

---

## 📊 Exemple de Rapport

### Problème détecté:
**Type**: Integrity  
**Entité**: Commande  
**Description**: Missing foreign key constraint on 'client_id'

### Correction appliquée:
```php
#[ORM\ManyToOne(targetEntity: User::class)]
#[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
private ?User $client = null;
```

### Commandes exécutées:
```bash
php bin/console doctrine:schema:update --force
php bin/console cache:clear
symfony server:stop
symfony server:start
```

### Résultat:
✅ Problème résolu - Vérifié dans le profiler

---

## 🚀 Commandes Utiles

### Démarrer/Arrêter le serveur
```bash
symfony server:start
symfony server:stop
symfony server:status
```

### Cache
```bash
php bin/console cache:clear
php bin/console cache:warmup
```

### Base de données
```bash
# Voir les changements
php bin/console doctrine:schema:update --dump-sql

# Appliquer les changements
php bin/console doctrine:schema:update --force

# Créer une migration
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### Vérifier la configuration
```bash
php bin/console debug:config doctrine
php bin/console doctrine:mapping:info
```

---

## 📸 Captures d'écran à Faire

1. **Page d'accueil avec profiler** (barre en bas)
2. **Onglet Doctrine Doctor** dans le profiler
3. **Section Integrity** avec problèmes
4. **Section Security** avec problèmes
5. **Section Configuration** avec problèmes
6. **Section Slowest Queries**
7. **Après corrections** - Tout en vert ✅

---

## ✅ Résultat Attendu

Après toutes les corrections, Doctrine Doctor devrait afficher:

```
✅ Integrity: No issues found
✅ Security: No issues found
✅ Configuration: No issues found
✅ Queries: All queries optimized
```

---

## 📝 Notes Importantes

1. **Toujours faire une sauvegarde** de la base de données avant les modifications
2. **Tester l'application** après chaque correction
3. **Documenter chaque problème** et sa solution
4. **Prendre des captures d'écran** pour le rapport
5. **Vider le cache** après chaque modification

---

**Bon travail! 🎉**
