# Documentation AI Multi-Connect

## 1. Description

Le plugin **AI Multi-Connect** centralise vos accès aux IA pour faciliter leur utilisation dans vos scénarios domotiques et introduit des fonctionnalités avancées d'assistant vocal.

### Moteurs disponibles :
- **Google Gemini** : Rapide et efficace (idéal pour le Raspberry Pi).
- **OpenAI (ChatGPT)** : Le standard du marché.
- **Mistral AI** : L'alternative française haute performance.
- *Azure et Vertex AI sont prévus dans les prochaines mises à jour.*

## 2. Configuration du plugin

Après installation du plugin, il vous suffit de l'activer. Aucune configuration globale n'est nécessaire ; tout se gère au niveau de chaque équipement.

## 3. Configuration des équipements

Rendez-vous dans le menu **Plugins > Communication > AI Multi-Connect**.

### Paramètres de l'équipement :
- **Nom de l'équipement** : Identifiant de votre IA (ex: "Gemini Assistant").
- **Objet parent** : Objet Jeedom auquel l'IA sera rattachée.
- **Catégorie** : Communication.
- **Activer / Visible** : Cocher pour utiliser l'équipement.

### Paramètres spécifiques aux moteurs d'IA :
- **Moteur d'IA** : Choisissez le fournisseur (Gemini, OpenAI, Mistral).
- **Clé API** : Collez ici la clé secrète fournie par le site de l'IA choisie.

### Fonctionnalités vocales (Assistant vocal) :
Le plugin intègre un démon Python (`ai_connector_daemon.py`) pour l'écoute vocale en continu et la détection de mot-clé (wakeword). Cette fonctionnalité transforme votre installation Jeedom en un assistant vocal local.

- **Activer l'écoute vocale (`voice_enable`)** : Cochez cette option sur l'équipement de votre choix pour activer le mode "écoute" pour cet équipement. **Attention : un seul équipement doit avoir l'écoute vocale activée à la fois.** Si plusieurs équipements sont configurés, seul le premier sera pris en compte par le démon.
- **ID de la commande de retour (HP) (`voice_cmd_id`)** : C'est l'ID de la commande Jeedom qui sera utilisée pour la synthèse vocale ou le retour d'information de l'IA. Par exemple, l'ID d'une commande "Parler" de votre module de synthèse vocale.
- **ID de l'appareil audio (`voice_device_id`)** : L'identifiant de votre microphone/périphérique d'entrée audio. Par défaut, "1" est souvent le microphone USB. Vous pouvez le trouver en exécutant `arecord -L` sur votre système.

### Détection de mot-clé (Wakeword Porcupine) :
Le plugin utilise Picovoice Porcupine pour la détection de mot-clé, permettant à votre assistant de ne réagir que lorsque le mot-clé est prononcé.

- **Activer le Wakeword (`porcupine_enable`)** : Cochez cette option pour activer la détection de mot-clé.
- **Clé d'accès Picovoice (`porcupine_access_key`)** : Nécessite une clé d'accès fournie gratuitement par Picovoice (pvporcupine). Rendez-vous sur [Picovoice Console](https://console.picovoice.ai/) pour obtenir votre clé. Sans cette clé, la détection de mot-clé ne fonctionnera pas, même si activée.

## 4. Les Commandes

Dès la sauvegarde, deux commandes sont créées :

- **Poser une question** (Action/Message) : C'est la commande que vous utilisez pour envoyer votre texte à l'IA ou pour envoyer une requête après un mot-clé détecté.
- **Dernière réponse** (Info/Autre) : Contient le texte brut renvoyé par l'IA.

## 5. Exemples d'utilisation

### Scénario : Alerte de sécurité intelligente
Si une présence est détectée et que l'alarme est mise :
- **Action** : `[Salon][Gemini][Poser une question]`
- **Message** : "Une présence a été détectée dans le jardin à 3h du matin. Rédige un message d'alerte court et urgent pour mon propriétaire."
- **Action** : `[Telegram][Moi][Envoi]` avec le contenu `#[Salon][Gemini][Dernière réponse]#`.

### Scénario : Conseil météo vocal
- L'équipement "Assistant Salon" a l'écoute vocale et le wakeword activés.
- Lorsque le mot-clé est prononcé, l'IA écoute la suite de la requête.
- **Requête vocale** : "Quelle est la météo de demain ?"
- Le démon envoie la requête à l'IA configurée dans l'équipement.
- La **Dernière réponse** de l'IA est récupérée.
- **Action** : `[Cuisine][Enceinte][Dire]` avec le contenu `#[Assistant Salon][Dernière réponse]#`.

## 6. FAQ

**Est-ce que le plugin est payant ?**
Le plugin lui-même dépend de sa licence sur le Market, mais l'utilisation des IA peut engendrer des coûts selon les quotas gratuits des fournisseurs (ex: Gemini possède un niveau gratuit généreux). La clé d'accès Picovoice est généralement gratuite pour un usage personnel.

**Le plugin est-il lent ?**
Le temps de réponse de l'IA dépend de la vitesse de l'API distante et de la complexité de votre question. Pour les requêtes vocales, il faut ajouter le temps de détection du mot-clé et de transcription de la parole.

## 7. Troubleshooting

- **"ModuleNotFoundError: No module named 'pyaudio'" ou problèmes d'écoute vocale** :
    - Assurez-vous d'avoir bien exécuté le script `install.sh` après les dernières mises à jour.
    - Vérifiez que les dépendances système nécessaires à `PyAudio` sont installées (`portaudio19-dev`). Si `sudo` est requis, lancez `sudo apt-get install -y portaudio19-dev`.
    - Assurez-vous que l'utilisateur `www-data` fait partie du groupe `audio` (`sudo usermod -aG audio www-data`). Un redémarrage complet de votre système peut être nécessaire pour que les changements de groupe prennent effet (`sudo reboot`).
    - Vérifiez que le démon Python est bien lancé avec l'interpréteur Python du Virtual Environment (voir les logs du démon).
- **"ERROR: Could not find a version that satisfies the requirement picovoice-porcupine"** :
    - Assurez-vous d'utiliser la bonne version du package (`pvporcupine` et non `picovoice-porcupine`). Les dernières versions du plugin intègrent cette correction.
    - Vérifiez que votre système est à jour et que `pip` est capable d'installer les binaires pour votre architecture (notamment AArch64 sur Raspberry Pi).
- **Pas de réponse de l'IA** : Vérifiez votre clé API et les logs du plugin (`Analyse > Logs > ai_connector`).
- **Erreur SSL** : Assurez-vous que votre Raspberry Pi est à l'heure (`sudo ntpd -q -g`).
- **Problèmes de permissions** : Exécutez le script `install.sh` qui gère les permissions ou vérifiez manuellement les droits sur le dossier du plugin et les fichiers exécutables.