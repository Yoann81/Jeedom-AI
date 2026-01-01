# Changelog - AI Connector

Tous les changements notables de ce projet sont documentés dans ce fichier.

## [2.0.0] - 2026-01-01

### 🎉 Majeur

#### Nouvelles fonctionnalités

- ✅ **Support STT/TTS complet**
  - Google Cloud Speech-to-Text avec multilingue
  - Google Cloud Text-to-Speech avec 60+ voix neurales
  - Whisper local pour reconnaissance hors-ligne
  - Synthèse automatique des réponses IA

- ✅ **Détection de wakeword**
  - Picovoice Porcupine intégré
  - Sensibilité configurable (0.0-1.0)
  - Support multiples wakewords
  - Peu de faux positifs

- ✅ **Démon Python complet** (`ai_connector_daemon.py`)
  - Écoute vocale en continu
  - Gestion multi-équipement
  - Logging détaillé
  - Gestion PID robuste

- ✅ **Anti-boucle protection**
  - Cache intelligent par équipement
  - Timeouts différenciés (30s manual, 10s STT)
  - Évite surcharges API

#### Améliorations

- 🔧 **Timeout API augmenté**
  - 30s → 60s pour Gemini/OpenAI
  - Permet réponses plus lentes

- 🔧 **Détection audio dynamique**
  - Recherche automatique du périphérique
  - Support Headphones/bcm2835/USB
  - Fallback hw:0,0

- 🔧 **Gestion d'erreurs robuste**
  - Détection erreurs API (quota, auth, structure)
  - Messages d'erreur détaillés
  - Logging complet

- 🔧 **Installation améliorée**
  - Script install.sh complet
  - Vérification automatique dépendances
  - Script check_installation.sh

#### Bugs corrigés

- 🐛 Visibilité méthode TTS (private → public)
  - Permettait appel depuis ai_connectorCmd
  
- 🐛 Timeout Jeedom lors envoi STT
  - Augmenté de 5s → 15s
  - Evite timeouts inutiles

- 🐛 Logs de débogage excessifs
  - Nettoyés tous les logs WARNING/ERROR de debug
  - Conservé essentiels seulement

### 📚 Documentation

- ✅ README.md complet et à jour
- ✅ Documentation index.md détaillée (100+ lignes)
- ✅ Guide dépannage exhaustif
- ✅ Exemples d'utilisation concrets
- ✅ Table des matières complète

### ⚙️ Technique

**Dépendances ajoutées** :
- alsa-utils (arecord, aplay)
- python3-dev (compilation modules C)
- ffmpeg (génération audio)
- curl (pour PHP)
- jq (parsing JSON)

**Modèles ML** :
- Whisper tiny (140MB, hors-ligne)
- Picovoice Porcupine (détection locale)

**Performance** :
- Temps total réponse : 15-45s (normal)
- Enregistrement : 4-5s
- STT Google : 2-5s
- Gemini : 10-30s
- TTS : 1-2s

### 🔐 Sécurité

- ✅ Clés API jamais exposées
- ✅ HTTPS obligatoire pour APIs
- ✅ Tokens API chiffrés
- ✅ Anti-loop protection
- ✅ Gestion erreurs complète

---

## [1.0.0-beta] - 2025-12

### Initial

- ✅ Support Gemini/OpenAI/Mistral
- ✅ Commandes ask/answer
- ✅ Scénarios simples
- ✅ Logs basiques

---

## Format des entrées

Ce fichier suit le format [Keep a Changelog](https://keepachangelog.com).

### Types de changements

- **Added** : Nouvelles fonctionnalités
- **Changed** : Modifications existantes
- **Deprecated** : Fonctionnalités bientôt retirées
- **Removed** : Fonctionnalités supprimées
- **Fixed** : Corrections de bugs
- **Security** : Corrections de sécurité

### Versions

Le versioning suit [Semantic Versioning](https://semver.org/) :
- **MAJOR** : Changements incompatibles
- **MINOR** : Nouvelles fonctionnalités compatibles
- **PATCH** : Corrections de bugs
