# 🔧 Outils de diagnostic et débogage

## 🚨 Le diagnostic s'arrête sans résultat?

Utilisez ces outils pour trouver le problème:

## 🧪 1. Diagnostic SIMPLE (recommandé)
```
http://votre-jeedom/plugins/ai_connector/core/php/diagnostic_simple.php
```

**Affichage:**
- ✓ Équipement IA
- ✓ Configuration
- ✓ Commandes
- ✓ Équipements disponibles
- ✓ Test simple API

**Avantages:**
- Affiche TOUTES les erreurs
- Affichage coloré
- HTML formaté
- Arrête aux vraies erreurs

## 🐛 2. Débogage détaillé (pour les techniciens)
```
http://votre-jeedom/plugins/ai_connector/core/php/debug.php
```

**Affichage:**
- ✓ Chargement de Jeedom
- ✓ Vérification des équipements
- ✓ Vérification de la méthode `getAllEquipments()`
- ✓ Test API IA
- ✓ Fichiers du plugin

**Avantages:**
- Traces complètes d'exceptions
- Affiche tous les avertissements PHP
- Vérifie les fichiers
- Affiche les méthodes disponibles

## 📊 3. Test simple IA
```
http://votre-jeedom/plugins/ai_connector/core/php/test_simple_ai.php
```

**Teste juste:** L'API IA fonctionne-t-elle?

## 📝 4. Test équipements
```
http://votre-jeedom/plugins/ai_connector/core/php/test_equipments.php
```

**Teste juste:** Les équipements sont-ils détectés?

---

## 🎯 Quel outil utiliser?

### "Le diagnostic s'arrête"
→ Utilisez **diagnostic_simple.php**

### "J'ai une erreur PHP"
→ Utilisez **debug.php**

### "L'IA ne répond pas"
→ Utilisez **test_simple_ai.php**

### "Les équipements ne s'affichent pas"
→ Utilisez **test_equipments.php**

### "Je ne sais pas où est le problème"
→ Utilisez **diagnostic_simple.php** d'abord, puis **debug.php** si nécessaire

---

## 🔍 Comment lire les résultats

### ✓ Vert = OK
```
✓ Commande 'ask' trouvée (ID: 787)
✓ API Key configurée (39 chars)
```

### ❌ Rouge = Erreur
```
❌ Aucun équipement IA! Créez-en un d'abord.
❌ Commande 'reponse' NON trouvée
```

### ⚠️ Orange = Avertissement
```
⚠️ Aucun équipement détecté
⚠️ Prompt vide
```

---

## 📋 Étapes pour déboguer

1. **Lancez diagnostic_simple.php**
   - Notez tous les ❌ (erreurs)
   - Notez tous les ⚠️ (avertissements)

2. **Si erreur PHP ou exception:**
   - Lancez debug.php
   - Regardez la section "Débogage détaillé"
   - Notez les messages d'erreur

3. **Vérifiez la configuration:**
   - API Key configurée?
   - Prompt configuré?
   - Équipement activé?

4. **Vérifiez les équipements:**
   - Des équipements créés dans Jeedom?
   - Sont-ils activés?
   - Ont-ils des commandes?

5. **Consultez les logs:**
   - Jeedom: Analyse → Logs → ai_connector
   - Cherchez les erreurs
   - Notez les timestamps

---

## 🆘 Messages d'erreur courants

### "Aucun équipement IA"
**Cause:** Vous n'avez pas créé d'équipement AI Connector

**Solution:**
1. Plugins → Jeedom-AI
2. Bouton "Ajouter"
3. Remplissez les paramètres
4. Sauvegardez

### "Méthode getAllEquipments() non trouvée"
**Cause:** La classe ai_connector n'est pas chargée correctement

**Solution:**
1. Vérifiez que core/class/ai_connector.class.php existe
2. Relancez le diagnostic
3. Redémarrez Jeedom si nécessaire

### "Aucun équipement détecté"
**Cause:** Vous n'avez pas créé d'équipements (ou tous sont IA)

**Solution:**
1. Créez des équipements (Lumières, Thermostats, etc.)
2. Relancez le diagnostic

### "Exception: [...]"
**Cause:** Erreur PHP ou Jeedom

**Solution:**
1. Lancez debug.php
2. Regardez la trace complète
3. Consultez les logs Jeedom
4. Vérifiez votre configuration PHP

---

## 💡 Pro tips

- **Gardez la page ouverte:** F5 pour actualiser
- **Copier/coller:** Les erreurs dans les logs
- **Tester plusieurs fois:** Les problèmes intermittents existent
- **Vérifier les logs:** Toujours regarder après un diagnostic
- **Redémarrer:** En dernier recours (Outils → Démon → Redémarrer)

---

## 🔗 Autres ressources

- [AUCUN_EQUIPEMENT_DETECTE.md](../AUCUN_EQUIPEMENT_DETECTE.md) - Créer des équipements
- [DEBUGGING_IA_NE_REPOND_PAS.md](../DEBUGGING_IA_NE_REPOND_PAS.md) - L'IA ne répond pas
- [GUIDE_EQUIPEMENTS_IA.md](../GUIDE_EQUIPEMENTS_IA.md) - Guide complet

