# 🔧 Erreur: "Call to undefined method ai_connector::getType()"

## 🚨 Erreur

```
Exception:
Message: Call to undefined method ai_connector::getType()
Fichier: /var/www/html/plugins/ai_connector/core/php/debug.php
Ligne: 81
```

## ✅ Solution

Cette erreur indique que la méthode `getType()` n'existe pas sur l'objet. C'est un problème du script de diagnostic, pas de votre IA.

**J'ai corrigé le problème.** Utilisez le nouveau diagnostic:

```
http://votre-jeedom/plugins/ai_connector/core/php/diagnostic_ultra_simple.php
```

Ce diagnostic:
- ✓ Évite les erreurs PHP
- ✓ Affichage HTML formaté
- ✓ Messages d'erreur clairs
- ✓ Vérifie étape par étape

## 🎯 Que faire

### 1. Lancez le nouveau diagnostic
```
http://votre-jeedom/plugins/ai_connector/core/php/diagnostic_ultra_simple.php
```

### 2. Vérifiez chaque étape
```
1️⃣ Chargement de Jeedom         → doit être ✓
2️⃣ Classe ai_connector           → doit être ✓
3️⃣ Équipement IA                 → doit être ✓
4️⃣ Configuration                 → doit être ✓
5️⃣ Commandes                     → doit être ✓
6️⃣ Équipements à contrôler       → peut être ⚠️ (normal si aucun créé)
7️⃣ Test API                      → doit être ✓
```

### 3. Notez les ❌ (erreurs) ou ⚠️ (avertissements)

## 📊 Exemples de résultats

### Parfait
```
✓ Jeedom chargé
✓ Classe ai_connector existe
✓ Équipement trouvé: test (ID: 89)
✓ Engine: gemini
✓ Configuration OK
✓ Commande 'ask' trouvée
✓ Commande 'reponse' trouvée
⚠️ Aucun équipement à contrôler (normal)
✓ Réponse reçue (142 chars)
```

### Avec problèmes
```
✓ Jeedom chargé
✓ Classe ai_connector existe
✓ Équipement trouvé
❌ Clé API manquante
❌ Prompt vide
⚠️ Aucun équipement à contrôler
```

## 🔍 Comment interpréter

### ✓ Vert (OK)
- Procédez à l'étape suivante
- Pas de problème

### ❌ Rouge (Erreur)
- **Il faut corriger!**
- Exemples:
  - Clé API manquante → Ajoutez-la
  - Prompt vide → Écrivez un prompt
  - Commande 'ask' manquante → Recréez l'équipement

### ⚠️ Orange (Avertissement)
- Pas critique
- Exemples:
  - Aucun équipement → Créez-en
  - Réponse vide → Peut être normal

## 🚀 Après le diagnostic

### Si tout est ✓
1. Créez des équipements (Lumière, Thermostat, etc.)
2. Testez l'IA en console:
   ```javascript
   aiConnector.sendMessage(89, 'Allume la lumière');
   ```

### Si une erreur ❌
1. Notez le message d'erreur
2. Corrigez le problème
3. Relancez le diagnostic

### Si un avertissement ⚠️
1. Pas urgent
2. Vous pouvez continuer
3. Créez des équipements pour progresser

## 📚 Guide complet

- [OUTILS_DIAGNOSTIC.md](../OUTILS_DIAGNOSTIC.md) - Tous les outils
- [AUCUN_EQUIPEMENT_DETECTE.md](../AUCUN_EQUIPEMENT_DETECTE.md) - Créer des équipements
- [GUIDE_EQUIPEMENTS_IA.md](../GUIDE_EQUIPEMENTS_IA.md) - Guide complet

## 💡 Pro tips

- **Bookmarkez ce lien:** Vous en aurez besoin plusieurs fois
- **Actualisez souvent:** F5 pour relancer le diagnostic
- **Consultez les logs:** Jeedom → Analyse → Logs → ai_connector
- **Décrivez l'erreur:** Si besoin d'aide, notez les ❌ et ⚠️

