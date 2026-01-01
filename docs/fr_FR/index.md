# Documentation AI Connector v2.0.0

## 📋 Table des matières
1. [Vue d'ensemble](#vue-densemble)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Utilisation](#utilisation)
5. [Fonctionnalités avancées](#fonctionnalités-avancées)
6. [Dépannage](#dépannage)

---

## Vue d'ensemble

### Qu'est-ce que AI Connector ?

AI Connector est un plugin Jeedom multimodal qui intègre les principaux moteurs d'Intelligence Artificielle du marché :

- **Google Gemini** (3-flash-preview) - API gratuite, réponses rapides
- **OpenAI ChatGPT** (gpt-4o-mini, gpt-4) - Modèles puissants
- **Mistral AI** (tiny, small, medium) - Alternative opensource performante

### Fonctionnalités

#### Moteurs de texte
- ✅ Intégration multiples IA dans un seul plugin
- ✅ Créez autant d'équipements que d'IA
- ✅ Réponses exploitables dans scénarios
- ✅ Gestion d'erreurs complète et logs détaillés

#### Reconnaissance vocale (STT)
- ✅ Google Cloud Speech-to-Text (cloud)
- ✅ Whisper (local, hors-ligne)
- ✅ Enregistrement automatique via démon Python
- ✅ Support multilingue (en-US, fr-FR, etc)

#### Synthèse vocale (TTS)
- ✅ Google Cloud Text-to-Speech
- ✅ 60+ voix neurales
- ✅ Lecture automatique de réponses IA
- ✅ Détection dynamique du périphérique audio

#### Détection de wakeword
- ✅ Picovoice Porcupine (détection locale, sans cloud)
- ✅ Sensibilité configurable (0.0 - 1.0)
- ✅ Support multiples wakewords
- ✅ Peu de faux positifs

#### Protection anti-boucle
- ✅ Cache intelligent
- ✅ Timeouts différenciés (30s manual, 10s STT)
- ✅ Évite les appels API inutiles

---

## Installation

### Prérequis système

- **Jeedom** : v4.3 ou plus récent
- **OS** : Debian/Raspbian moderne (Bullseye+)
- **Python** : 3.8 ou plus
- **Espace disque** : 200MB minimum
- **Son** : Microphone + Haut-parleur (optionnel)

### Procédure d'installation

#### Étape 1 : Téléchargement et activation

1. **Jeedom** > **Plugins** > **Gestion des plugins**
2. **+ Ajouter** > Rechercher "AI Connector"
3. **Installer** et **Activer**

#### Étape 2 : Installation des dépendances

```bash
sudo bash /var/www/html/plugins/ai_connector/resources/install.sh
```

Cela installe :
- Librairies audio (alsa-utils, portaudio, mpg123)
- Python venv et dépendances (pvporcupine, requests, pyaudio)
- Modèle Whisper (tiny, 140MB)
- Fichier son de notification

**Durée** : 5-10 minutes (selon vitesse internet et CPU)

#### Étape 3 : Vérification

```bash
sudo bash /var/www/html/plugins/ai_connector/resources/check_installation.sh
```

Doit afficher ✅ pour tous les éléments critiques.

#### Étape 4 : Redémarrage

```bash
sudo systemctl restart jeedom
```

---

## Configuration

### 1. Obtenir les clés API

#### 🔑 Google Gemini (Recommandé pour débuter)

1. Allez sur https://aistudio.google.com
2. **Get API Key** > **Create API key in new project**
3. Acceptez les conditions
4. **Copier** la clé (commence par `AIza...`)

*Gratuit : 60 requêtes/min, illimitées en requêtes/jour*

#### 🔑 Google Cloud (pour STT/TTS)

1. https://console.cloud.google.com
2. **Créer un projet**
3. **APIs & Services** > **Activer les APIs** :
   - Cloud Speech-to-Text
   - Cloud Text-to-Speech
4. **Identifiants** > **Créer un identifiant** > **Clé API**
5. Télécharger le JSON et copier la clé

*Gratuit : 60 minutes STT/mois, 1 million caractères TTS/mois*

#### 🔑 OpenAI

1. https://platform.openai.com/account/api-keys
2. **+ Create new secret key**
3. **Copier** (commence par `sk-...`)

*Payant : ~0.005$ par 1K tokens (gpt-4o-mini)*

#### 🔑 Mistral

1. https://console.mistral.ai/api-keys
2. **Generate a new API key**
3. **Copier** (commence par `bHd...`)

*Gratuit : 50 requêtes/jour (tier gratuit)*

#### 🔑 Picovoice Porcupine (Wakeword)

1. https://console.picovoice.ai/
2. Se connecter/créer compte
3. **AccessKey** > Copier la clé
4. Garder le modèle par défaut "picovoice" ou en créer un personnalisé

*Gratuit : 1 modèle personnalisé*

### 2. Créer un équipement

#### Configuration basique (texte seul)

1. **Jeedom** > **Plugins** > **Communication** > **AI Connector**
2. **+ Ajouter** équipement
3. Remplir :
   ```
   Nom          : Ma IA Gemini
   Objet parent : Cuisine
   Moteur       : Google Gemini
   Clé API      : AIza... (copié plus haut)
   Modèle       : (laisser vide ou gemini-3-flash-preview)
   Actif        : ✓ Coché
   Visible      : ✓ Coché
   ```
4. **Sauvegarder**

Les commandes sont créées automatiquement :
- `Poser une question` (action, type message)
- `Dernière réponse` (info, type string)

#### Configuration avancée (avec STT/TTS)

Ajouter les paramètres :

```
TTS activé           : ✓ Coché
Clé Google Cloud     : (copier du JSON ou API Gemini)
Langue TTS          : fr-FR
Voix TTS            : fr-FR-Neural2-A (ou autre)
Périphérique audio  : hw:2,0 (détecté automatiquement)

STT activé          : ✓ Coché
Moteur STT          : google (ou whisper pour local)
Langue STT          : fr-FR
Dispositif audio    : 1 (voir arecord -L)

Wakeword activé     : ✓ Coché
Clé Picovoice       : (copier d'AccessKey)
Wakewords           : picovoice (ou autres)
Sensibilité         : 0.95 (0.0 min, 1.0 max)
```

5. **Sauvegarder**

---

## Utilisation

### Cas d'usage 1 : Question simple dans un scénario

```
Bloc d'action:
Action : #[Cuisine][Ma IA][Poser une question]#
Message : Quel est le meilleur moment pour faire cuire un gâteau?
```

Puis récupérer la réponse :
```
Bloc d'action suivant:
Afficher notification : #[Cuisine][Ma IA][Dernière réponse]#
```

### Cas d'usage 2 : Contexte dynamique

```
Message : "La température extérieure est #[Terrasse][Sonde][Temp]#°C et l'humidité #[Terrasse][Sonde][Humidité]#%. \
Dois-je faire ma lessive aujourd'hui?"
```

L'IA reçoit les valeurs actuelles de Jeedom.

### Cas d'usage 3 : Activation via wakeword + TTS

1. Assistant détecte "picovoice"
2. Enregistrement de 5 secondes
3. Transcription STT
4. Réponse IA
5. Lecture TTS automatique

**Configuration requise** :
- Wakeword activé ✓
- STT activé ✓
- TTS activé ✓

### Cas d'usage 4 : Utiliser la réponse dans une logique

```
SI #[Salon][Mon IA][Dernière réponse]# contient "oui"
ALORS
  #[Salon][Lumière salon][Allumer]#
FIN SI
```

---

## Fonctionnalités avancées

### Timeouts anti-boucle

Le plugin utilise 2 stratégies :

| Situation | Timeout | Raison |
|-----------|---------|--------|
| Appel manuel (scénario) | 30 secondes | Laisser le temps de tester |
| Appel STT (démon) | 10 secondes | Éviter les répétitions microphone |

**Comment ça marche** :
1. Vous envoyez "Bonjour"
2. Réponse reçue et cachée
3. Pendant 30s, toute demande "Bonjour" est bloquée
4. Après 30s, la demande est à nouveau acceptée

*Cela évite les boucles infinies si un scénario déclenche lui-même l'IA.*

### Gestion dynamique du périphérique audio

Le plugin détecte automatiquement :

```bash
Recherche dans aplay -l:
- "Headphones" (Jack stéréo Raspberry Pi)
- "bcm2835" (Raspberry Pi onboard)
- "USB Audio" (Casque/Enceinte USB)
```

Si aucun détecté → hw:0,0 (fallback)

**Vérifier votre périphérique** :
```bash
aplay -l
# Exemple output:
# card 2: Headphones [Headphones], device 0: bcm2835 Headphones [bcm2835 Headphones]
#   Subdevices: 0/1
#     Subdevice #0: subdevice #0

# → Utilisez hw:2,0
```

### Logs détaillés

**Plugin** (`/var/www/html/log/ai_connector`) :
```
[2026-01-01 18:55:06] DEBUG  Sending to Gemini...
[2026-01-01 18:55:10] DEBUG  CURL HTTP Code: 200
[2026-01-01 18:55:10] INFO   Réponse IA: Bonjour...
[2026-01-01 18:55:10] DEBUG  TTS: Audio en cours de lecture
```

**Démon** (`/var/www/html/log/ai_connector_daemon`) :
```
[2026-01-01 18:54:09] INFO  Démon lancé en mode Wakeword
[2026-01-01 18:54:15] INFO  Démon STT response: {"results": [...]}
[2026-01-01 18:54:16] INFO  Envoi à Jeedom : votre question
[2026-01-01 18:54:20] INFO  Texte envoyé à Jeedom avec succès
```

---

## Dépannage

### ❌ TTS ne joue pas

**Diagnostic** :
```bash
# 1. Vérifier mpg123
which mpg123

# 2. Tester le son directement
speaker-test -t sine -f 1000 -l 1

# 3. Vérifier les droits
groups www-data | grep audio

# 4. Vérifier le fichier audio
file /tmp/ai_tts.mp3

# 5. Logs détaillés
tail -f /var/www/html/log/ai_connector | grep TTS
```

**Solutions** :
- Si mpg123 manquant : `sudo apt-get install mpg123`
- Si pas de son : `sudo usermod -aG audio www-data` + redémarrage
- Si périphérique incorrect : Vérifier avec `aplay -l` et ajuster dans config

### ❌ STT ne transcrit pas

**Diagnostic** :
```bash
# 1. Tester microphone
arecord -t wav -c 1 -r 16000 /tmp/test.wav
# Parlez puis Ctrl+C - doit avoir creé le fichier

# 2. Vérifier le modèle Whisper
ls /var/www/html/plugins/ai_connector/resources/whisper.cpp/models/
# Doit avoir ggml-tiny.bin

# 3. Vérifier si démon tourne
pgrep -a ai_connector_daemon

# 4. Vérifier les logs
tail -f /var/www/html/log/ai_connector_daemon | grep STT
```

**Solutions** :
- Microphone non détecté : Vérifier `arecord -L` et ajuster device_id
- Modèle manquant : Relancer install.sh
- Démon arrêté : Redémarrer Jeedom

### ❌ Réponse lente (>45s)

**C'est normal** si :
- Gemini dépasse 30s (API surchargée)
- Microphone enregistre 5s + transcription 5s = 10s rien que pour ça

**Diagnostic** :
```bash
# Vérifier temps Gemini
grep "CURL\|Gemini" /var/www/html/log/ai_connector | tail -20

# Vérifier quota API
# → Panel Google Console, Graph API

# Vérifier charge Jeedom
uptime
```

**Solutions** :
- Si quota dépassé : Attendre ou passer à OpenAI
- Si CPU élevé : Arrêter autres plugins
- Si réseau lent : Approcher du WiFi

### ❌ Wakeword ne se déclenche pas

**Diagnostic** :
```bash
# 1. Vérifier sensibilité (par défaut 0.95)
# Configuration > Sensibilité Picovoice

# 2. Tester micro en direct
arecord -t wav -c 1 -r 16000 -D hw:1,0 /tmp/test.wav
# Parlez "picovoice" clairement
# Vérifier le fichier a du contenu

# 3. Vérifier clé Picovoice
grep "porcupine_access_key" /var/www/html/log/ai_connector_daemon

# 4. Logs détaillés
tail -100 /var/www/html/log/ai_connector_daemon | grep -i porcupine
```

**Solutions** :
- Augmenter sensibilité à 0.99 (plus sensible)
- Parler plus fort/près du micro
- Vérifier clé Picovoice valide
- Relancer démon : `jeedom::daemon_stop()` + restart Jeedom

### ❌ "Erreur Gemini : Structure inconnue"

**Diagnostic** :
```bash
# Vérifier réponse brute Gemini
grep "CURL Raw response\|Gemini response" /var/www/html/log/ai_connector

# Vérifier API key valide
# Vérifier quota
```

**Solutions** :
- Vérifier clé API valide
- Vérifier quota Gemini (60 req/min)
- Essayer autre modèle
- Vérifier connexion internet : `curl -I https://google.com`

### ❌ Timeout Jeedom (HTTPConnectionPool)

**Diagnostic** :
```bash
# Vérifier si Jeedom répond
curl http://127.0.0.1/ping

# Vérifier logs démon
grep "HTTPConnectionPool" /var/www/html/log/ai_connector_daemon
```

**Solutions** :
- Jeedom est lent : `sudo systemctl restart jeedom`
- Augmenter timeout daemon : éditer ressource, augmenter 15s
- Vérifier charge : `htop`

---

## 📞 Support & Ressources

**GitHub** : https://github.com/Yoann81/Jeedom-AI

**Logs** :
- Plugin : `tail -f /var/www/html/log/ai_connector`
- Démon : `tail -f /var/www/html/log/ai_connector_daemon`

**Version** : 2.0.0  
**Auteur** : Yoann Joulia  
**Licence** : AGPL v3.0
