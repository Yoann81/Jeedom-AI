# Guide de Test - AI Connector

Guide complet pour tester toutes les fonctionnalités du plugin.

## 🚀 Phase 1 : Installation & Configuration (10 min)

### Étape 1.1 : Vérifier l'installation

```bash
sudo bash /var/www/html/plugins/ai_connector/resources/check_installation.sh
```

✅ Tous les éléments doivent afficher ✓ (sauf avertissements tolérés)

### Étape 1.2 : Créer un équipement Gemini

1. **Jeedom** > **Plugins** > **Communication** > **AI Connector**
2. **+ Ajouter** équipement
3. Configuration minimale :
   ```
   Nom          : Test Gemini
   Moteur       : Google Gemini
   Clé API      : AIza... (from Google AI Studio)
   Actif        : ✓
   ```
4. **Sauvegarder**

Commandes créées automatiquement :
- `Poser une question` (action)
- `Dernière réponse` (info)

---

## 🧪 Phase 2 : Test Texte Seul (5 min)

### Test 2.1 : Question simple en scénario

```
Bloc d'action:
1. #[Votre objet][Test Gemini][Poser une question]#
   Message: "Quel est 2+2?"

2. Attendre 10 secondes

3. Afficher notification: #[Votre objet][Test Gemini][Dernière réponse]#
```

✅ **Résultat attendu** : Notification affichant "4"

**Logs** :
```bash
tail -f /var/www/html/log/ai_connector | grep -E "Gemini|Réponse"

[2026-01-01 18:55:06] DEBUG  Sending to Gemini URL: ...
[2026-01-01 18:55:16] INFO   Réponse IA: 2+2=4
```

### Test 2.2 : Avec paramètre dynamique

```
Message: "En Python, comment créer une liste vide?"
```

✅ Doit recevoir syntaxe Python correcte

### Test 2.3 : Erreur intentionnelle

```
Clé API: "invalide"
Message: "Test"
```

✅ Doit afficher erreur API : "Invalid API key" dans logs

---

## 🎤 Phase 3 : Configuration TTS (15 min)

### Étape 3.1 : Configuration audio

```bash
# Lister les périphériques
aplay -l

# Résultat attendu:
# card 2: Headphones
#   device 0: bcm2835 Headphones
# → Utilisez hw:2,0
```

### Étape 3.2 : Configurer TTS

Éditer l'équipement et ajouter :

```
TTS activé           : ✓
Clé Google Cloud     : AIza... (Gemini key OK ou Google Cloud)
Langue TTS          : fr-FR
Voix TTS            : fr-FR-Neural2-A
Périphérique audio  : hw:2,0 (ou détecté auto)
```

**Sauvegarder**

### Test 3.3 : Test TTS manuel

```
Bloc d'action:
1. #[Test Gemini][Poser une question]#
   Message: "Bonjour, ceci est un test audio"

2. Attendre 5 secondes
```

✅ **Résultat attendu** : 
- Vous entendez la phrase lue en français
- Logs affichent : "TTS: Audio en cours de lecture"

**Dépannage** :
```bash
# Si pas de son:
aplay -l                    # Vérifier device
speaker-test -t sine -f 1000 -l 1  # Test haut-parleur
file /tmp/ai_tts.mp3       # Vérifier fichier généré
tail -50 /var/www/html/log/ai_connector | grep TTS
```

---

## 🎙️ Phase 4 : Configuration STT (15 min)

### Étape 4.1 : Tester le microphone

```bash
# Enregistrer 3 secondes
arecord -t wav -c 1 -r 16000 /tmp/test_record.wav

# Doit créer un fichier ~96KB
ls -lh /tmp/test_record.wav

# Écouter (optionnel)
aplay /tmp/test_record.wav
```

✅ Doit capturer votre voix correctement

### Étape 4.2 : Configurer STT

Éditer l'équipement et ajouter :

```
STT activé          : ✓
Moteur STT          : whisper (ou google)
Langue STT          : fr-FR
Dispositif audio    : 1 (voir arecord -L)
```

**Sauvegarder**

### Test 4.3 : Mode périodique (sans wakeword)

Le démon enregistre régulièrement et transcrit.

```bash
# Vérifier que le démon tourne
pgrep -a ai_connector_daemon

# Voir les logs
tail -f /var/www/html/log/ai_connector_daemon
```

Attendez 5-10 secondes dans le silence.

✅ **Résultat attendu** :
```
[18:55:00] INFO  Démon AI Multi-Connect : Enregistrement audio...
[18:55:05] INFO  Démon AI Multi-Connect : Transcription audio...
[18:55:07] INFO  Google STT response: {'results': [...]}
[18:55:07] INFO  Démon AI Multi-Connect : Texte transcrit : '(silence)'
```

---

## 🔔 Phase 5 : Wakeword Detection (15 min)

### Étape 5.1 : Configuration Picovoice

1. Aller sur https://console.picovoice.ai/
2. Se connecter et copier **AccessKey**

Éditer l'équipement :

```
Wakeword activé     : ✓
Clé Picovoice       : (votre AccessKey)
Wakewords           : picovoice
Sensibilité         : 0.95
```

**Sauvegarder**

### Étape 5.2 : Relancer le démon

```bash
# Arrêter
sudo systemctl stop jeedom

# Vérifier arrêt
pgrep ai_connector_daemon
# Doit retourner vide

# Relancer
sudo systemctl start jeedom

# Vérifier redémarrage
sleep 5
pgrep -a ai_connector_daemon
```

### Test 5.3 : Détection de wakeword

```bash
# Voir logs en direct
tail -100 /var/www/html/log/ai_connector_daemon | grep -i porcupine

# Parlez "picovoice" clairement près du micro
```

✅ **Résultat attendu** :
```
[18:55:00] INFO  Utilisation des wakewords : picovoice
[18:55:00] INFO  Sensibilité Picovoice : 0.95
[18:55:00] INFO  Démon AI Multi-Connect en attente de 'picovoice'...
[18:55:03] INFO  Détection de wakeword: picovoice
[18:55:03] INFO  Enregistrement audio de 5 secondes...
[18:55:08] INFO  Transcription audio...
[18:55:10] INFO  Démon AI : Texte transcrit : 'quel est 2+2'
```

**Dépannage si pas détecté** :
```bash
# 1. Augmenter sensibilité à 0.99
# 2. Parler plus fort/plus proche
# 3. Tester micro: arecord -t wav -c 1 -r 16000 -D hw:1,0 /tmp/test.wav
# 4. Vérifier clé Picovoice valide
# 5. Logs détaillés:
   grep -i porcupine /var/www/html/log/ai_connector_daemon
   grep -i "ERROR\|Exception" /var/www/html/log/ai_connector_daemon
```

---

## 🔄 Phase 6 : Test complet STT→IA→TTS (20 min)

### Configuration requise
- ✅ STT activé
- ✅ TTS activé
- ✅ Wakeword activé

### Test 6.1 : Workflow complet

1. **Dire** "picovoice"
   - Bip de confirmation
   - "Enregistrement..." pendant 5s

2. **Dire** "quel est le sens de la vie"
   - Enregistrement terminé
   - Transcription...
   - Gemini répond...
   - **Vous entendez** la réponse lue en français

✅ **Processus complet** : 15-45 secondes

**Logs attendus** :
```bash
tail -100 /var/www/html/log/ai_connector_daemon

[18:55:00] INFO  Détection de wakeword: picovoice
[18:55:00] INFO  Enregistrement audio de 5 secondes
[18:55:05] INFO  Transcription audio
[18:55:07] INFO  Google STT response: {'results': [...'quel est le sens'...]}
[18:55:07] INFO  Envoi à Jeedom : quel est le sens de la vie
[18:55:07] INFO  Texte envoyé à Jeedom avec succès

tail -100 /var/www/html/log/ai_connector

[18:55:07] DEBUG  Sending to Gemini URL: ...
[18:55:20] INFO   Réponse IA: La vie a autant de sens que...
[18:55:20] DEBUG  TTS: Audio en cours de lecture
[18:55:22] (Vous entendez la réponse)
```

---

## 📊 Phase 7 : Tests de stress (optionnel)

### Test 7.1 : Scénario rapide répétitif

```
Répéter 5 fois:
1. #[Test Gemini][Poser une question]#
   Message: "Bonjour"
2. Attendre 2 secondes
3. Afficher: #[Test Gemini][Dernière réponse]#
4. Attendre 1 seconde
```

✅ **Résultat** : Anti-loop doit bloquer requêtes dupliquées
- 1ère requête : ✓ Réponse
- 2-5 requêtes : ✓ Réponse bloquée (cache)
- Après 30s : ✓ Réponse à nouveau acceptée

**Vérifier logs** :
```bash
grep "dupliqué\|Prompt" /var/www/html/log/ai_connector
```

### Test 7.2 : Charge API

```bash
# Lancer 10 requêtes rapides
for i in {1..10}; do
  curl -X POST "http://127.0.0.1/core/api/jeeApi.php?apikey=YOUR_KEY&type=cmd&id=DEVICE_ID&message=Test%20$i"
done

# Vérifier qu'aucune erreur quota
grep "quota\|error" /var/www/html/log/ai_connector
```

---

## ✅ Checklist de tests

- [ ] Phase 1 : Installation OK
- [ ] Phase 2 : Text simple OK
- [ ] Phase 3 : TTS audio OK
- [ ] Phase 4 : STT transcription OK
- [ ] Phase 5 : Wakeword détecté OK
- [ ] Phase 6 : Workflow complet OK
- [ ] Phase 7 : Stress tests OK

---

## 📝 Rapport de test

Template pour documenter vos résultats :

```
Date        : 2026-01-01
Testeur     : Nom
Hardware    : Raspberry Pi 4, 2GB RAM
OS          : Raspbian Bullseye
Jeedom      : v4.4.0
Plugin      : AI Connector 2.0.0

Résultats   : ✅ TOUS LES TESTS PASSENT
Durée totale: 90 minutes
Issues      : Aucune

Notes       : Plugin fonctionnel et stable
```

---

## 🐛 Issues rencontrées

Si un test échoue, collectez les logs :

```bash
# Plugin logs
tar czf /tmp/ai_connector_logs.tar.gz /var/www/html/log/ai_connector*

# Démon Python logs (stdout)
journalctl -u jeedom -n 100 > /tmp/jeedom_logs.txt

# Infos système
uname -a > /tmp/system_info.txt
python3 --version >> /tmp/system_info.txt
free -h >> /tmp/system_info.txt
df -h >> /tmp/system_info.txt
```

Créez une **GitHub Issue** avec ces fichiers.

---

**Version** : 2.0.0  
**Dernière mise à jour** : Janvier 2026
