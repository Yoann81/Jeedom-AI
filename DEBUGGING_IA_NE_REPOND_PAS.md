# 🔧 Debugging - L'IA ne répond pas

## 🚨 Symptômes

```
[2026-01-03 17:11:31] INFO  Succès ! Le démon est lancé. PID(s) : 892198
[2026-01-03 17:11:44] INFO  Exécution commande avec prompt: Ferme le volet salon
[2026-01-03 17:11:44] WARNING Prompt dupliqué ignoré pour éviter la boucle (30s): Ferme le volet salon
```

La commande est appelée **DEUX FOIS** trop rapidement et la deuxième est bloquée.

## ✅ Corrections apportées

### 1. **Système anti-boucle amélioré**
- **Avant:** Comparait le prompt exact (string) toutes les 30 secondes
- **Après:** Utilise un hash MD5 + timeout de 5 secondes
- ✓ Moins restrictif (5s au lieu de 30s)
- ✓ Plus robuste (hash au lieu de string)

### 2. **Meilleur logging**
- Logs détaillés avec marqueurs `>>>` et `<<<`
- Logs de chaque étape du traitement
- Messages d'erreur plus explicites (avec ❌ et ✓)

### 3. **Gestion d'erreurs**
- Try/catch autour du processMessage
- Vérifie que les réponses ne sont pas vides
- Logs des exceptions

## 🧪 Comment tester le diagnostic

### Via le navigateur
```
http://votre-jeedom/plugins/ai_connector/core/php/diagnostic.php
```

### Affichage attendu
```
=== 1. ÉQUIPEMENTS IA ===
Trouvés: 1 équipement(s) IA
ID: 1
Nom: Assistant Maison
Activé: OUI ✓

=== 2. CONFIGURATION ===
Engine: gemini
API Key: ✓ Configurée (39 chars)
Model: gemini-1.5-flash
Prompt: ✓ 150 chars
Include Equipments: ✓ OUI

=== 3. COMMANDES ===
✓ Commande 'ask' trouvée (ID: 42)
✓ Commande 'reponse' trouvée (ID: 43)

=== 4. ÉQUIPEMENTS DISPONIBLES ===
Total: 5
Premiers 3:
  - Salon [Lumière] (ID: 5)
  - Chambre [Lumière] (ID: 6)
  - ...

=== 5. TEST API IA ===
Tentative d'appel à l'API gemini...
✓ Réponse reçue: Bonjour! Je suis prêt à vous aider.
```

## 🔍 Checklist de dépannage

### ❌ Équipement IA non trouvé
- [ ] Allez dans Plugins → Jeedom-AI
- [ ] Vérifiez que vous avez au moins un équipement IA créé
- [ ] Vérifiez que l'équipement est **ACTIVÉ**

### ❌ API Key manquante
- [ ] Allez dans l'équipement IA → Configuration
- [ ] Trouvez le champ "Clé API"
- [ ] Collez votre clé API (Gemini, OpenAI, ou Mistral)
- [ ] Sauvegardez

### ❌ Prompt vide
- [ ] Allez dans l'équipement IA → Configuration
- [ ] Trouvez "Prompt par défaut"
- [ ] Ajoutez un prompt (exemple: "Tu es un assistant utile")
- [ ] Sauvegardez

### ❌ Commandes 'ask' et 'reponse' manquantes
- [ ] Supprimez l'équipement IA
- [ ] Recréez-le
- [ ] Les commandes doivent se créer automatiquement au postSave

### ❌ Réponse API vide
- [ ] Vérifiez votre clé API
- [ ] Vérifiez que vous n'avez pas atteint le quota
- [ ] Vérifiez la connectivité Internet
- [ ] Vérifiez les logs Jeedom pour les erreurs CURL

## 📊 Flux d'exécution

```
Démon Python (STT)
        ↓
Transcription du texte
        ↓
Appel à la commande 'ask' (ID: 42)
        ↓
ai_connectorCmd::execute() 
        ↓
Vérifie les doublons (5 secondes)
        ↓
eqLogic->processMessage($prompt)
        ↓
Ajoute le contexte des équipements (si activé)
        ↓
Appelle l'API IA (Gemini/OpenAI/Mistral)
        ↓
Traite les commandes [EXEC_COMMAND: id]
        ↓
Met à jour la commande 'reponse'
        ↓
TTS si activé
        ↓
Fin
```

## 🐛 Problèmes courants

### "Prompt dupliqué ignoré"
**Cause:** Le démon envoie deux fois le même prompt

**Solutions:**
- Attendez 5 secondes avant de relancer
- Vérifiez la configuration du démon
- Vérifiez que la STT n'active pas deux fois

### "Réponse vide"
**Cause:** L'API IA n'a pas répondu

**Vérifier:**
```
Logs → ai_connector → Cherchez "CURL Error" ou "HTTP Code"
```

### "Erreur de clé API"
**Vérifier:**
- La clé API est correcte
- La clé n'a pas expiré
- Vous n'avez pas atteint le quota

## 📝 Logs à consulter

### Logs du démon Python
```
http://votre-jeedom/view?p=log
Fichier: ai_connector_daemon
```

### Logs généraux
```
http://votre-jeedom/view?p=log
Fichier: ai_connector
```

### Chercher
- `>>>` = Début du traitement
- `<<<` = Fin du traitement
- `CURL` = Appel API
- `Erreur` = Problème
- `WARNING` = Avertissement

## 🎯 Actions rapides

### Pour relancer le démon
```
Outils → Démon → Arrêter
Outils → Démon → Démarrer
```

### Pour purger le cache
```
Analyse → Résumé Domotique → Vider le cache
```

### Pour voir les commandes
```
Plugins → Jeedom-AI → Votre équipement
Onglet: Commandes
```

## 📞 Besoin d'aide?

1. Lancez le diagnostic: `/plugins/ai_connector/core/php/diagnostic.php`
2. Consultez les logs: `Analyse → Logs → ai_connector`
3. Vérifiez la configuration: Plugins → Jeedom-AI → Votre équipement
