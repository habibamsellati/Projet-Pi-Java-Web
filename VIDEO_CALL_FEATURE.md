# Fonctionnalité de Visioconférence pour Réclamations - VERSION INTÉGRÉE

## Vue d'ensemble

Système de visioconférence **intégré directement dans l'application** permettant aux administrateurs de créer des appels vidéo avec les clients pour résoudre leurs réclamations en temps réel. Les appels vidéo s'ouvrent maintenant dans l'application au lieu de rediriger vers un site externe.

## Technologie Utilisée

**Jitsi Meet (Embedded)** - Solution de visioconférence gratuite et open-source intégrée
- ✅ Aucune installation requise
- ✅ Intégré directement dans votre application
- ✅ Gratuit et illimité
- ✅ Sécurisé (chiffrement de bout en bout)
- ✅ Pas besoin de compte
- ✅ Interface professionnelle personnalisée

## Fonctionnalités

### Pour l'Administrateur

1. **Créer une visioconférence**
   - Bouton "📹 Créer une visioconférence" sur la page de détails de la réclamation
   - Génère automatiquement un ID de salle unique
   - Envoie un email d'invitation au client
   - Affiche le bouton pour rejoindre l'appel

2. **Rejoindre l'appel**
   - Cliquer sur "🎥 Rejoindre la visioconférence"
   - S'ouvre en plein écran dans l'application
   - Interface Jitsi Meet intégrée
   - Contexte de la réclamation visible en haut
   - Bouton "Quitter" pour retourner à la page de réclamation

### Pour le Client

1. **Recevoir l'invitation**
   - Email automatique avec le lien vers l'application
   - Instructions claires
   - Lien accessible à tout moment

2. **Voir l'invitation sur le site**
   - Section spéciale sur la page de détails de la réclamation
   - Bouton "🎥 Rejoindre la visioconférence"
   - Informations sur l'utilisation

3. **Rejoindre l'appel**
   - Un clic pour rejoindre depuis l'application
   - Interface plein écran intégrée
   - Aucune installation nécessaire
   - Fonctionne sur ordinateur, tablette et mobile

## Comment Utiliser

### Étape 1: Créer une Visioconférence

1. Aller sur la page de détails d'une réclamation (backoffice)
2. Cliquer sur "📹 Créer une visioconférence"
3. Le système:
   - Génère un ID de salle unique
   - Envoie un email au client avec lien vers l'application
   - Affiche le bouton pour rejoindre

### Étape 2: Rejoindre l'Appel

**Admin:**
1. Cliquer sur "🎥 Rejoindre la visioconférence"
2. Page plein écran s'ouvre dans l'application
3. Autoriser caméra et microphone
4. Attendre que le client rejoigne

**Client:**
1. Cliquer sur le lien dans l'email OU
2. Aller sur la page de la réclamation et cliquer sur le bouton
3. Page plein écran s'ouvre dans l'application
4. Autoriser caméra et microphone
5. Commencer la discussion

### Étape 3: Pendant l'Appel

Fonctionnalités disponibles:
- 🎥 Vidéo HD
- 🎤 Audio clair
- 💬 Chat textuel
- 🖥️ Partage d'écran
- 📝 Tableau blanc collaboratif
- 🎨 Arrière-plan virtuel / flou
- ✋ Lever la main
- 📊 Statistiques de qualité
- 🎬 Enregistrement (si activé)
- ⚙️ Paramètres audio/vidéo

### Étape 4: Quitter l'Appel

1. Cliquer sur le bouton "✕ Quitter" en haut à droite
2. Confirmation demandée
3. Retour automatique à la page de réclamation appropriée

## Email d'Invitation

Le client reçoit automatiquement un email contenant:
- Titre de la réclamation
- Lien vers la page de visioconférence dans l'application
- Instructions d'utilisation
- Informations techniques
- Design professionnel avec en-tête coloré

## Caractéristiques Techniques

### Base de Données

Deux champs dans la table `reclamation`:
- `video_call_link` (VARCHAR 500) - Stocke l'ID de salle unique
- `video_call_scheduled_at` (DATETIME) - Date de création

### Routes Ajoutées

1. **Création de visioconférence**
   - `POST /back/reclamation/{id}/create-video-call`
   - Nom: `back_reclamation_create_video_call`
   - Accès: Admin uniquement

2. **Rejoindre (Admin)**
   - `GET /back/reclamation/{id}/video-call`
   - Nom: `back_reclamation_video_call`
   - Accès: Admin uniquement

3. **Rejoindre (Client)**
   - `GET /reclamation/{id}/video-call`
   - Nom: `app_reclamation_video_call`
   - Accès: Propriétaire de la réclamation uniquement

### Sécurité

- Token CSRF pour la création
- ID de salle unique par réclamation
- Format: `reclamation-{id}-{random_16_chars}`
- Vérification des permissions avant de rejoindre
- Client ne peut rejoindre que ses propres réclamations

### Génération de l'ID de Salle

```php
$roomId = 'reclamation-' . $reclamation->getId() . '-' . bin2hex(random_bytes(8));
```

Exemple: `reclamation-1-a3f7b9c2d4e6f8a1`

### Intégration Jitsi Meet

Utilise l'API JavaScript externe de Jitsi Meet:
```javascript
const api = new JitsiMeetExternalAPI('meet.jit.si', {
    roomName: roomId,
    width: '100%',
    height: '100%',
    parentNode: document.querySelector('#jitsi-meet'),
    userInfo: { displayName: userName }
});
```

## Interface Utilisateur

### Page de Visioconférence

**Éléments affichés:**
- Interface Jitsi Meet en plein écran
- En-tête avec contexte de réclamation (ID et titre)
- Bouton "Quitter" en haut à droite
- Nom d'utilisateur automatiquement défini

**Fonctionnalités:**
- Prévention de fermeture accidentelle
- Confirmation avant de quitter
- Retour automatique à la page appropriée
- Responsive (fonctionne sur mobile)

## Avantages de la Version Intégrée

### Par rapport à la version externe:

1. **Expérience professionnelle**
   - Reste dans votre application
   - Pas de redirection externe
   - Interface cohérente avec votre marque

2. **Meilleure UX**
   - Pas de nouvel onglet
   - Contexte toujours visible
   - Sortie contrôlée

3. **Plus sécurisé**
   - Vérification des permissions
   - Pas de liens externes à partager
   - Contrôle total de l'accès

4. **Plus professionnel**
   - Semble faire partie de votre application
   - Pas de branding Jitsi visible
   - Interface personnalisable

## Avantages Généraux

### Pour l'Admin
- ✅ Résolution rapide des problèmes
- ✅ Communication directe
- ✅ Meilleure compréhension du problème
- ✅ Démonstration visuelle possible
- ✅ Gain de temps vs emails multiples
- ✅ Interface intégrée professionnelle

### Pour le Client
- ✅ Réponse personnalisée
- ✅ Explication claire en direct
- ✅ Peut montrer le problème visuellement
- ✅ Résolution plus rapide
- ✅ Meilleure satisfaction
- ✅ Expérience fluide dans l'application

## Compatibilité

### Navigateurs Supportés
- ✅ Chrome (recommandé)
- ✅ Firefox
- ✅ Safari
- ✅ Edge
- ✅ Opera

### Appareils
- ✅ Ordinateur (Windows, Mac, Linux)
- ✅ Tablette (iPad, Android)
- ✅ Smartphone (iOS, Android)

### Connexion Internet
- Minimum: 1 Mbps
- Recommandé: 3+ Mbps
- HD: 5+ Mbps

## Limitations

- Un seul appel par réclamation
- L'ID de salle reste valide indéfiniment
- Pas de limite de durée d'appel
- Pas de limite de participants (mais recommandé: 2-4)

## Fichiers Créés/Modifiés

### Créés:
1. `templates/admin/video_call.html.twig` - Page de visio pour admin
2. `templates/reclamation/video_call.html.twig` - Page de visio pour client

### Modifiés:
1. `src/Controller/BackController.php`
   - Modifié `createVideoCall()` - stocke ID au lieu d'URL
   - Ajouté `joinVideoCallAdmin()` - affiche page intégrée
   - Modifié `sendVideoCallInvitation()` - envoie lien application

2. `src/Controller/ReclamationController.php`
   - Ajouté `joinVideoCall()` - affiche page intégrée pour client

3. `templates/admin/reclamation_show.html.twig`
   - Lien vers page intégrée au lieu d'URL externe

4. `templates/reclamation/show.html.twig`
   - Lien vers page intégrée au lieu d'URL externe

## Dépannage

### Le lien ne fonctionne pas?

1. Vérifier la connexion internet
2. Essayer un autre navigateur
3. Autoriser caméra/microphone
4. Désactiver bloqueur de publicités
5. Vérifier les paramètres de confidentialité

### Pas de vidéo/audio?

1. Vérifier les permissions du navigateur
2. Tester caméra/micro dans les paramètres système
3. Fermer autres applications utilisant caméra/micro
4. Redémarrer le navigateur

### Email non reçu?

1. Vérifier le dossier spam
2. Vérifier l'adresse email du client
3. Utiliser le bouton sur la page de réclamation

### Page blanche ou erreur?

1. Vérifier que JavaScript est activé
2. Vider le cache du navigateur
3. Vérifier la console pour erreurs
4. Essayer en navigation privée

## Prochaines Améliorations Possibles

1. **Planification d'appel**
   - Choisir date et heure
   - Rappels automatiques

2. **Historique des appels**
   - Liste des appels passés
   - Durée de chaque appel
   - Participants

3. **Enregistrement automatique**
   - Sauvegarder les appels
   - Transcription automatique

4. **Feedback post-appel**
   - Évaluation de la qualité
   - Commentaires client
   - Résolution confirmée

5. **Intégration calendrier**
   - Synchronisation Google Calendar
   - Outlook Calendar

6. **Personnalisation avancée**
   - Logo de l'entreprise
   - Couleurs personnalisées
   - Arrière-plan personnalisé

## Configuration Avancée

### Changer le serveur Jitsi

Pour utiliser votre propre serveur Jitsi:
```javascript
const domain = 'votre-serveur-jitsi.com';
```

### Personnaliser l'interface

Modifier les options dans `interfaceConfigOverwrite`:
```javascript
interfaceConfigOverwrite: {
    SHOW_JITSI_WATERMARK: false,
    DEFAULT_BACKGROUND: '#votre-couleur',
    TOOLBAR_BUTTONS: [...] // Personnaliser les boutons
}
```

## Support

Pour toute question ou problème:
1. Vérifier cette documentation
2. Tester avec un autre navigateur
3. Vérifier les permissions caméra/micro
4. Contacter le support technique

## Conclusion

Cette fonctionnalité intégrée améliore significativement l'expérience utilisateur en gardant tout dans l'application. La communication directe et personnalisée entre l'admin et le client conduit à une résolution plus rapide et une meilleure satisfaction client, le tout dans une interface professionnelle et cohérente.

