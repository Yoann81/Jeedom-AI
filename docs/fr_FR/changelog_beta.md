# Changelog AI Multi-Connect (Bêta)

## Version 1.0.0 (26/12/2025)
- **Init** : Création de la structure de base du plugin.
- **Moteurs** : Ajout du support pour **Google Gemini 1.5**.
- **Moteurs** : Ajout du support pour **OpenAI (GPT-4o)**.
- **Moteurs** : Ajout du support pour **Mistral AI**.
- **Commandes** : Création automatique des commandes "Question" et "Réponse" à la sauvegarde.
- **Internationalisation** : Support du français.

## Version 1.1.0 (29/12/2025)
- **Fonctionnalités** : Introduction du démon Python (`ai_connector_daemon.py`) pour l'écoute vocale en continu.
- **Fonctionnalités** : Intégration de la détection de mot-clé (wakeword) via Picovoice Porcupine (pvporcupine).
- **Correctif** : Correction du nom de package `picovoice-porcupine` vers `pvporcupine` dans `install.sh` pour une installation correcte des dépendances.
- **Correctif** : Ajustement du script `install.sh` pour résoudre l'erreur `chmod` sur `whisper.cpp/main` après renommage en `whisper-cli`.
- **Correctif** : Mise à jour du fichier `ai_connector.class.php` pour que le démon Python utilise l'interpréteur du Virtual Environment, assurant ainsi la bonne détection des modules comme `PyAudio`.
- **Documentation** : Mise à jour de `index.md` avec des détails précis sur les fonctionnalités vocales et les dépannages associés.