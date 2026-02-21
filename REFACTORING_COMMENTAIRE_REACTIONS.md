# 🔄 REFACTORING: Suppression de CommentaireReaction

## ✅ CHANGEMENTS EFFECTUÉS

### 1. Modification de l'entité Commentaire

**Avant**: Utilisation d'une entité séparée `CommentaireReaction` avec relation OneToMany

**Après**: Intégration directe des likes/dislikes dans `Commentaire` avec SEULEMENT 2 colonnes simples

#### Nouveaux attributs ajoutés:
```php
#[ORM\Column(type: 'integer', options: ['default' => 0])]
private int $likes = 0;

#[ORM\Column(type: 'integer', options: ['default' => 0])]
private int $dislikes = 0;
```

#### Nouvelles méthodes:
- `getLikes()` / `setLikes()` / `incrementLikes()` / `decrementLikes()`
- `getDislikes()` / `setDislikes()` / `incrementDislikes()` / `decrementDislikes()`

---

### 2. Modification de l'entité User

**Supprimé**:
```php
#[ORM\OneToMany(mappedBy: 'user', targetEntity: CommentaireReaction::class, orphanRemoval: true)]
private Collection $commentaireReactions;
```

Et toutes les méthodes associées:
- `getCommentaireReactions()`
- `addCommentaireReaction()`
- `removeCommentaireReaction()`

---

### 3. Modification du ArticleController

#### Méthode `show()`:
- Plus besoin de `CommentaireReactionRepository`
- Plus besoin de passer des statistiques au template

#### Méthode `reactToComment()`:
- **Nouvelle logique ultra-simple**:
```php
if ($type === 'like') {
    $commentaire->incrementLikes();
} else {
    $commentaire->incrementDislikes();
}
```

---

### 4. Fichiers supprimés

✅ `src/Entity/CommentaireReaction.php`
✅ `src/Repository/CommentaireReactionRepository.php`

---

### 5. Modifications en base de données

#### Colonnes ajoutées à `commentaire`:
- `likes` INT DEFAULT 0 NOT NULL
- `dislikes` INT DEFAULT 0 NOT NULL

#### Tables supprimées:
- `commentaire_reaction`
- `commentaire_user_likes` 
- `commentaire_user_dislikes`

---

## 🎯 AVANTAGES DE CE REFACTORING

### 1. **Extrême simplicité**
- Seulement 2 colonnes dans la table `commentaire`
- Aucune table de liaison
- Aucune jointure nécessaire
- Code minimal

### 2. **Performance maximale**
- Compteurs directement dans la table
- SELECT ultra-rapide
- Pas de COUNT()
- Pas de JOIN

### 3. **Logique métier ultra-claire**
```php
// Avant (complexe avec entité séparée)
$reaction = new CommentaireReaction();
$reaction->setCommentaire($commentaire);
$reaction->setUser($user);
$reaction->setType(CommentaireReaction::TYPE_LIKE);
$entityManager->persist($reaction);

// Après (simple)
$commentaire->incrementLikes();
```

---

## 📊 UTILISATION DANS LES TEMPLATES TWIG

### Afficher les compteurs:
```twig
<span>👍 {{ commentaire.likes }}</span>
<span>👎 {{ commentaire.dislikes }}</span>
```

### Boutons de réaction:
```twig
<form method="post" action="{{ path('app_commentaire_reaction', {commentaire: commentaire.id, type: 'like'}) }}">
    <input type="hidden" name="_token" value="{{ csrf_token('commentaire_reaction_' ~ commentaire.id ~ '_like') }}">
    <button type="submit">👍 J'aime ({{ commentaire.likes }})</button>
</form>

<form method="post" action="{{ path('app_commentaire_reaction', {commentaire: commentaire.id, type: 'dislike'}) }}">
    <input type="hidden" name="_token" value="{{ csrf_token('commentaire_reaction_' ~ commentaire.id ~ '_dislike') }}">
    <button type="submit">👎 Je n'aime pas ({{ commentaire.dislikes }})</button>
</form>
```

---

## ⚠️ LIMITATIONS

### Un utilisateur peut liker/disliker plusieurs fois
- Pas de suivi des utilisateurs qui ont réagi
- Chaque clic incrémente le compteur
- Pour empêcher ça, il faudrait:
  - Soit utiliser JavaScript (localStorage)
  - Soit créer une table de liaison (mais c'est ce qu'on voulait éviter)

### Solution recommandée (si nécessaire):
Ajouter une vérification côté JavaScript:
```javascript
// Stocker dans localStorage
if (localStorage.getItem('liked_comment_' + commentId)) {
    alert('Vous avez déjà liké ce commentaire');
    return false;
}
localStorage.setItem('liked_comment_' + commentId, 'true');
```

---

## 🔧 COMMANDES EXÉCUTÉES

```bash
# Mise à jour du schéma de base de données
php bin/console doctrine:schema:update --force

# Nettoyage du cache
php bin/console cache:clear
```

---

## 📝 STRUCTURE FINALE

### Table `commentaire`:
```sql
CREATE TABLE commentaire (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contenu VARCHAR(255) NOT NULL,
    datepub DATETIME NOT NULL,
    article_id INT NOT NULL,
    user_id INT NOT NULL,
    parent_id INT NULL,
    likes INT DEFAULT 0 NOT NULL,      -- ← NOUVEAU
    dislikes INT DEFAULT 0 NOT NULL,   -- ← NOUVEAU
    FOREIGN KEY (article_id) REFERENCES article(id),
    FOREIGN KEY (user_id) REFERENCES user(id),
    FOREIGN KEY (parent_id) REFERENCES commentaire(id) ON DELETE CASCADE
);
```

---

## 🎓 CONCEPTS SYMFONY UTILISÉS

1. **Doctrine ORM**:
   - `#[ORM\Column]` - Définir des colonnes simples
   - `options: ['default' => 0]` - Valeur par défaut en BDD

2. **Logique métier**:
   - Méthodes `increment` et `decrement`
   - Protection contre les valeurs négatives

3. **Bonnes pratiques**:
   - Code minimal et performant
   - Pas de sur-ingénierie
   - KISS (Keep It Simple, Stupid)



---

### 2. Modification de l'entité User

**Supprimé**:
```php
#[ORM\OneToMany(mappedBy: 'user', targetEntity: CommentaireReaction::class, orphanRemoval: true)]
private Collection $commentaireReactions;
```

Et toutes les méthodes associées:
- `getCommentaireReactions()`
- `addCommentaireReaction()`
- `removeCommentaireReaction()`

---

### 3. Modification du ArticleController

#### Méthode `show()`:
- **Supprimé**: `CommentaireReactionRepository $commentaireReactionRepository`
- **Supprimé**: Variables `$commentReactionStats` et `$userCommentReactions`
- Plus besoin de passer ces données au template

#### Méthode `reactToComment()`:
- **Supprimé**: `CommentaireReactionRepository $commentaireReactionRepository`
- **Nouvelle logique**:
```php
if ($type === 'like') {
    if ($commentaire->isLikedByUser($user)) {
        $commentaire->removeLikedByUser($user);
    } else {
        if ($commentaire->isDislikedByUser($user)) {
            $commentaire->removeDislikedByUser($user);
        }
        $commentaire->addLikedByUser($user);
    }
}
```

---

### 4. Fichiers supprimés

✅ `src/Entity/CommentaireReaction.php`
✅ `src/Repository/CommentaireReactionRepository.php`

---

### 5. Modifications en base de données

#### Tables créées:
- `commentaire_user_likes` (table de liaison ManyToMany)
- `commentaire_user_dislikes` (table de liaison ManyToMany)

#### Colonnes ajoutées à `commentaire`:
- `likes` INT DEFAULT 0 NOT NULL
- `dislikes` INT DEFAULT 0 NOT NULL

#### Table supprimée:
- `commentaire_reaction`

---

## 🎯 AVANTAGES DE CE REFACTORING

### 1. **Simplicité**
- Plus besoin d'une entité séparée
- Moins de jointures en base de données
- Code plus lisible et maintenable

### 2. **Performance**
- Compteurs `likes` et `dislikes` directement dans la table `commentaire`
- Pas besoin de COUNT() pour afficher le nombre de likes/dislikes
- Requêtes plus rapides

### 3. **Logique métier plus claire**
```php
// Avant (complexe)
$reaction = new CommentaireReaction();
$reaction->setCommentaire($commentaire);
$reaction->setUser($user);
$reaction->setType(CommentaireReaction::TYPE_LIKE);
$entityManager->persist($reaction);

// Après (simple)
$commentaire->addLikedByUser($user);
```

### 4. **Prévention des doublons**
- Les tables ManyToMany empêchent automatiquement qu'un user like/dislike plusieurs fois
- Plus besoin de contrainte UniqueConstraint

---

## 📊 UTILISATION DANS LES TEMPLATES TWIG

### Afficher les compteurs:
```twig
<span>👍 {{ commentaire.likes }}</span>
<span>👎 {{ commentaire.dislikes }}</span>
```

### Vérifier si l'utilisateur a réagi:
```twig
{% if commentaire.isLikedByUser(app.user) %}
    <button class="active">👍 J'aime</button>
{% else %}
    <button>👍 J'aime</button>
{% endif %}
```

---

## ✅ TESTS À EFFECTUER

1. ✅ Créer un commentaire
2. ✅ Liker un commentaire
3. ✅ Disliker un commentaire
4. ✅ Changer de like à dislike
5. ✅ Retirer un like/dislike
6. ✅ Vérifier que les compteurs s'incrémentent/décrémentent correctement
7. ✅ Vérifier qu'un user ne peut pas liker ET disliker en même temps

---

## 🔧 COMMANDES EXÉCUTÉES

```bash
# Mise à jour du schéma de base de données
php bin/console doctrine:schema:update --force

# Nettoyage du cache
php bin/console cache:clear
```

---

## 📝 NOTES IMPORTANTES

- Les anciennes données de `commentaire_reaction` ont été supprimées
- Si vous aviez des likes/dislikes existants, ils ont été perdus
- Pour migrer les données, il faudrait créer une migration Doctrine personnalisée

---

## 🎓 CONCEPTS SYMFONY UTILISÉS

1. **Doctrine ORM**:
   - `#[ORM\Column]` - Définir des colonnes
   - `#[ORM\ManyToMany]` - Relations plusieurs-à-plusieurs
   - `#[ORM\JoinTable]` - Personnaliser les tables de liaison
   - `Collection` - Gérer les collections d'entités

2. **Validation**:
   - Logique métier dans les setters
   - Incrémentation/décrémentation automatique

3. **Bonnes pratiques**:
   - Méthodes métier claires (`isLikedByUser`, `addLikedByUser`)
   - Gestion automatique des compteurs
   - Prévention des états incohérents

