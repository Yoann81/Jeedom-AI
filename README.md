# AI Connector - Plugin Jeedom

Assistant IA multimodal pour Jeedom avec support STT/TTS et détection de wakeword.

## 🌟 Fonctionnalités principales

### Moteurs d'IA supportés
- **Google Gemini** (3-flash-preview) - Rapide et efficace
- **OpenAI ChatGPT** (gpt-4o-mini, gpt-4)
- **Mistral AI** (tiny, small, medium)

### Capacités vocales
- **STT (Speech-to-Text)** :
  - Google Cloud Speech-to-Text
  - Whisper local (hors ligne)
- **TTS (Text-to-Speech)** :
  - Google Cloud Text-to-Speech avec voix neurales
- **Détection de wakeword** :
  - Picovoice Porcupine (détection locale)
  - Sensibilité configurable (0.0 - 1.0)

### Protection anti-boucle
- Cache intelligent pour éviter les requêtes dupliquées
- Timeouts différenciés : 30s (manual) / 10s (STT)

### Sécurité
- Clés API stockées localement
- Logs détaillés pour débogage
- Gestion des erreurs API complète

## 🚀 Installation

### Prérequis système
- Jeedom v4.3+
- Raspbian/Debian moderne
- Python 3.8+
- 200MB+ d'espace disque

### Installation automatique
```bash
sudo bash /var/www/html/plugins/ai_connector/resources/install.sh
```

### Vérification après installation
```bash
sudo bash /var/www/html/plugins/ai_connector/resources/check_installation.sh
```

## ⚙️ Configuration

### 1. Obtenir les clés API

#### Google Gemini
1. Allez sur [Google AI Studio](https://aistudio.google.com)
2. Cliquez sur "Get API Key"
3. Créez une clé API gratuite
4. Copie la clé

#### Google Cloud (STT/TTS)
1. Créez un compte [Google Cloud](https://console.cloud.google.com)
2. Activez les APIs : Speech-to-Text, Text-to-Speech
3. Créez une clé API (type JSON)
4. Stockez le fichier JSON

#### OpenAI
1. Rendez-vous sur [OpenAI Platform](https://platform.openai.com)
2. Créez une clé API dans Account > API keys
3. Copiez la clé

#### Mistral
1. Visitez [Mistral Console](https://console.mistral.ai)
2. Générez une clé API
3. Copiez la clé

#### Picovoice (Wakeword)
1. Créez un compte [Picovoice](https://console.picovoice.ai)
2. Accédez à AccessKey et copiez votre clé

### 2. Créer un équipement

1. **Jeedom > Plugins > Communication > AI Connector**
2. **+ Ajouter** un nouvel équipement
3. **Renseignez** :
   - Nom de l'équipement
   - Moteur d'IA (Gemini, OpenAI, Mistral)
   - Clé API
   - Modèle (optionnel)
   - Paramètres STT/TTS (si activés)
   - Sensibilité wakeword (0.0 - 1.0)
4. **Sauvegardez**

Les commandes s'ajoutent automatiquement :
- `Poser une question` (action, type message)
- `Dernière réponse` (info, type string)

## 📖 Utilisation

### Dans un scénario

**Exemple 1 : Question simple**
```
Action: #[Cuisine][Mon IA][Poser une question]#
Message: "Quel est le meilleur moment pour arroser les plantes?"
```

**Exemple 2 : Question avec contexte**
```
Action: #[Salon][Mon IA][Poser une question]#
Message: "La température est de #[Extérieur][Sonde Temp][Température]#°C. Donne-moi un conseil vestimentaire court."
```

**Exemple 3 : Utiliser la réponse**
```
Bloc d'action:
IF #[Salon][Mon IA][Dernière réponse]# contient "oui"
  THEN #[Salon][Lumière][Allumer]#
```

### Avec TTS (synthèse vocale)

Le TTS s'active automatiquement si configuré. La réponse est lue à voix haute via le périphérique audio défini.

**Périphériques supportés** :
- Carte son système (Raspberry Pi GPIO)
- Casque USB
- Enceinte Bluetooth

### Avec STT (reconnaissance vocale)

#### Activation manuelle
- Appuyez sur le wakeword détecté
- Parlez après le bip
- L'audio est transcrit et envoyé à l'IA

#### Mode wakeword continu
Le démon écoute en permanence et déclenche sur detection du wakeword (par ex: "picovoice").

**Configuration** :
- Ajustez la sensibilité si non-détecté (augmentez si nécessaire)
- Choisissez la langue STT (en-US, fr-FR, etc)
- Sélectionnez le moteur STT (Whisper local ou Google)

## 🔧 Dépannage

### TTS ne joue pas
```
Vérifiez:
1. TTS activé dans configuration
2. API Key Google valide
3. mpg123 installé: which mpg123
4. Périphérique audio détecté: aplay -l
5. Permissions: sudo usermod -aG audio www-data
Logs: tail -f /var/www/html/log/ai_connector_daemon
```

### STT ne transcrit pas
```
Vérifiez:
1. Mode wakeword / périodique correct
2. Microphone test: arecord -t wav -c 1 -r 16000 /tmp/test.wav
3. Whisper modèle téléchargé: ls resources/whisper.cpp/models/
4. API Google STT clé valide (si mode Google)
Logs: tail -f /var/www/html/log/ai_connector_daemon
```

### Réponses tardives (>30s)
```
C'est normal si Gemini est lent. Timeout défaut: 60s
Vérifiez:
1. Connexion internet
2. Quota API Google
3. Charge serveur Jeedom
Logs: grep "CURL\|Gemini\|timeout" /var/www/html/log/ai_connector
```

### Timeout Jeedom (Erreur d'envoi)
```
Vérifiez:
1. Jeedom répond: curl http://127.0.0.1/ping
2. Timeout daemon: grep "HTTPConnectionPool" /var/www/html/log/ai_connector_daemon
3. Augmentez timeout daemon (15s défaut)
Solution: Relancez Jeedom
```

## 📊 Logs

### Fichiers de logs
- **Plugin** : `/var/www/html/log/ai_connector`
- **Démon** : `/var/www/html/log/ai_connector_daemon`

### Affichage en temps réel
```bash
# Plugin
tail -f /var/www/html/log/ai_connector

# Démon STT/TTS
tail -f /var/www/html/log/ai_connector_daemon
```

### Filtrer les erreurs
```bash
grep "ERROR\|Exception" /var/www/html/log/ai_connector
```

## 🔐 Sécurité

- ✅ Clés API jamais exposées
- ✅ Requêtes HTTPS obligatoires
- ✅ Tokens API chiffrés en base
- ✅ Anti-loop protection
- ✅ Timeouts configurable
- ✅ Gestion d'erreurs complète

## 🎯 Performance

| Opération | Temps estimé |
|-----------|--------------|
| Enregistrement audio | 4-5s |
| Transcription STT (Google) | 2-5s |
| Réponse Gemini | 10-30s |
| Génération TTS | 1-2s |
| **Total** | **15-45s** |

*Note: Wakeword detection + STT ajoute ~5-10s*

## 📝 Licence

AGPL v3.0

## 👨‍💻 Auteur

**Yoann Joulia** - Fondateur Maison Joulia

---

## 🐛 Support

Pour les bugs et demandes de fonctionnalités : [GitHub Issues](https://github.com/Yoann81/Jeedom-AI/issues)

**Version** : 2.0.0  
**Dernière mise à jour** : Janvier 2026
