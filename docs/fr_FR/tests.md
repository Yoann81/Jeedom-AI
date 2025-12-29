# Plan de Tests pour AI Multi-Connect

Ce document décrit un ensemble de tests pour valider le bon fonctionnement du plugin AI Multi-Connect après installation ou mise à jour.

## 1. Tests d'Installation et de Dépendances

### Objectif
Vérifier que le script `install.sh` s'exécute sans erreur et que toutes les dépendances sont correctement installées.

### Procédure
1.  **Exécution du script `install.sh`** :
    *   Exécuter `sudo ./resources/install.sh` sur un Raspberry Pi fraîchement configuré ou après une suppression complète des dépendances du plugin.
    *   **Attendu** : Le script doit se terminer sans erreur critique (les avertissements `WARNING: apt...` ou `LF will be replaced by CRLF` sont généralement acceptables).
    *   **Attendu** : `pvporcupine` et `PyAudio` doivent être listés comme "Successfully installed" dans les logs.
    *   **Attendu** : `whisper.cpp` doit compiler et le modèle `ggml-base.bin` doit être téléchargé.
    *   **Attendu** : Le message de redémarrage (`sudo reboot`) doit apparaître à la fin.
2.  **Vérification des droits et groupes** :
    *   Après un redémarrage, vérifier que l'utilisateur `www-data` est bien membre du groupe `audio` : `groups www-data`.
    *   **Attendu** : `www-data` doit être dans le groupe `audio`.
    *   Vérifier les permissions sur le dossier du plugin : `ls -l /var/www/html/plugins/ai_connector`.
    *   **Attendu** : Le propriétaire doit être `www-data:www-data` avec des permissions appropriées (775 pour les dossiers, 664 pour les fichiers).

## 2. Tests de Fonctionnalité de Base (Moteurs IA)

### Objectif
Valider la communication avec les différents moteurs d'IA et la réception des réponses.

### Procédure
1.  **Configuration d'un équipement AI Multi-Connect** :
    *   Dans Jeedom, créer un équipement AI Multi-Connect.
    *   Configurer avec une **Clé API valide** pour Google Gemini.
    *   Sauvegarder l'équipement.
2.  **Test "Poser une question" (Gemini)** :
    *   Utiliser la commande "Poser une question" de l'équipement.
    *   Envoyer un message simple, ex: "Quelle est la capitale de la France ?".
    *   **Attendu** : La commande "Dernière réponse" doit contenir "Paris" ou une réponse similaire pertinente.
    *   Consulter les logs `ai_connector` (`Analyse > Logs`) pour vérifier l'absence d'erreurs d'API.
3.  **Test avec OpenAI et Mistral AI** :
    *   Modifier l'équipement pour utiliser OpenAI, puis Mistral AI (avec leurs clés API respectives).
    *   Répéter le test "Poser une question".
    *   **Attendu** : Des réponses correctes et pertinentes.

## 3. Tests de Fonctionnalité Vocale (Daemon, Wakeword, PyAudio)

### Objectif
Vérifier le bon fonctionnement du démon Python pour l'écoute vocale, la détection de mot-clé et l'intégration audio.

### Pré-requis
*   Un microphone fonctionnel connecté au Raspberry Pi.
*   Une clé d'accès Picovoice valide configurée dans l'équipement Jeedom.

### Procédure
1.  **Démarrage du démon** :
    *   Dans Jeedom, créer/modifier un équipement AI Multi-Connect.
    *   Activer l'option "**Activer l'écoute vocale**".
    *   Renseigner l'**ID de la commande de retour (HP)** (ex: une commande de synthèse vocale).
    *   Activer le "**Wakeword Porcupine**" et configurer la "**Clé d'accès Picovoice**".
    *   Sauvegarder l'équipement.
    *   Aller dans la page de configuration du plugin, vérifier le statut du démon. S'il n'est pas "OK", le démarrer manuellement.
    *   **Attendu** : Le statut du démon doit passer à "OK".
    *   Consulter les logs `ai_connector_daemon` pour vérifier le démarrage sans erreur du démon Python, l'activation de `pvporcupine` et `PyAudio`.
2.  **Test de détection de mot-clé (Wakeword)** :
    *   Prononcer le mot-clé configuré (ex: "Picovoice") devant le microphone.
    *   **Attendu** : Le démon doit détecter le mot-clé. Une indication visuelle/sonore ou un log de détection doit apparaître.
3.  **Test de commande vocale** :
    *   Après la détection du mot-clé, énoncer une question, ex: "Dis-moi une blague".
    *   **Attendu** : La question doit être transcrite par Whisper, envoyée à l'IA, et la "Dernière réponse" de l'équipement doit être mise à jour avec la blague.
    *   **Attendu** : Si une commande de retour HP est configurée, l'IA doit verbaliser la réponse via le système de synthèse vocale de Jeedom.
4.  **Test d'arrêt du démon** :
    *   Arrêter le démon via l'interface Jeedom.
    *   **Attendu** : Le statut du démon doit passer à "NOK".
    *   **Attendu** : Les logs `ai_connector_daemon` doivent indiquer l'arrêt du processus.

## 4. Tests d'Erreur et de Robustesse

### Objectif
Vérifier comment le plugin réagit aux configurations invalides et aux conditions d'erreur.

### Procédure
1.  **Clé API invalide** :
    *   Configurer un équipement avec une clé API volontairement erronée pour un moteur d'IA.
    *   Envoyer une question.
    *   **Attendu** : Une erreur claire doit être remontée dans les logs `ai_connector` et/ou dans la commande "Dernière réponse".
2.  **Mot-clé Picovoice manquant** :
    *   Activer le wakeword mais laisser la clé d'accès Picovoice vide.
    *   Démarrer le démon.
    *   **Attendu** : Le démon doit loguer un avertissement/erreur concernant l'absence de la clé et ne pas activer le wakeword.
3.  **Démon sans écoute vocale activée** :
    *   Tenter de démarrer le démon alors qu'aucun équipement n'a l'écoute vocale activée.
    *   **Attendu** : Le démon doit loguer une erreur indiquant qu'aucun équipement d'écoute n'est actif et ne doit pas démarrer.
4.  **Problèmes de réseau** :
    *   Désactiver temporairement la connexion internet du Raspberry Pi.
    *   Tenter d'envoyer une question à l'IA ou de démarrer le démon.
    *   **Attendu** : Des erreurs de connexion réseau doivent être loguées.

## 5. Tests de Performance (Optionnel)

### Objectif
Évaluer les performances du plugin sous différentes charges.

### Procédure
1.  **Temps de réponse de l'IA** :
    *   Mesurer le temps entre l'envoi d'une question et la réception de la réponse pour différents moteurs et complexités de questions.
    *   **Attendu** : Des temps de réponse acceptables (généralement 2-10 secondes selon l'IA et la requête).
2.  **Consommation CPU/RAM du démon** :
    *   Observer la consommation de ressources du processus Python du démon (`top`, `htop`).
    *   **Attendu** : Une consommation raisonnable en mode veille (écoute wakeword) et une augmentation temporaire lors du traitement d'une commande vocale.

Ce plan de test doit être exécuté de manière méthodique pour assurer la stabilité et la fiabilité du plugin.
