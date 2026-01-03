# 🧪 Tests - Récupération et Exécution des Équipements

Ce dossier contient des scripts de test pour vérifier que votre plugin peut correctement:
- Récupérer tous les équipements Jeedom
- Récupérer les commandes de chaque équipement
- Exécuter les commandes via l'IA

## 📋 Fichiers de test

### `test_equipments.php`
Script de diagnostic complet qui affiche:
- ✓ Tous les équipements Jeedom (avec leur statut)
- ✓ Toutes les commandes de chaque équipement
- ✓ Le contexte IA généré
- ✓ Les commandes d'action disponibles

**Utilisation:**
```bash
php core/php/test_equipments.php
```

**Ou via Jeedom:**
```
http://votre-jeedom/plugins/ai_connector/core/php/test_equipments.php
```

### `test_ajax_endpoints.php`
Teste les endpoints AJAX:
- ✓ `getAllEquipments`
- ✓ `getAllEquipmentsWithCommands`
- ✓ `getEquipmentCommands`
- ✓ `getJeedomContext`

**Utilisation:**
```bash
php core/php/test_ajax_endpoints.php
```

## ✅ Vérifications à faire

### 1. Les équipements s'affichent?
Lancez `test_equipments.php` et vérifiez que la liste des équipements n'est pas vide.

```
Total: 5 équipement(s)
- Salon [Lumière] (ID: 5, Type: light, Activé: OUI)
- Chambre [Lumière] (ID: 6, Type: light, Activé: OUI)
...
```

Si vide, vérifiez:
- Vous avez des équipements configurés dans Jeedom
- Les équipements ne sont pas tous désactivés

### 2. Les commandes s'affichent?
Chaque équipement devrait avoir au moins une commande.

```
Salon [Lumière] (3 commande(s)):
   🔘 On (ID: 42)
   🔘 Off (ID: 43)
   ℹ️ État (ID: 44)
```

Si vide, vérifiez:
- Les équipements ont des commandes définies
- Les commandes ne sont pas toutes masquées

### 3. Les commandes d'action existent?
Le test devrait afficher au moins une commande d'action.

```
Total: 12 commande(s) d'action
Exemples:
   - Salon [Lumière] → On (ID: 42)
   - Chambre [Lumière] → Off (ID: 43)
```

Si vide, vérifiez:
- Vous avez des équipements avec des actions (pas juste des capteurs)

### 4. Le contexte IA est généré?
Le contexte devrait contenir la liste formatée des équipements.

```
=== ÉQUIPEMENTS JEEDOM DISPONIBLES ===

📱 Salon [Lumière] (ID: 5)
   Type: light
   Commandes:
     • On (ID: 42) [ACTION]
     • Off (ID: 43) [ACTION]
```

Si vide, vérifiez:
- Vous avez un équipement AI configuré
- Vous avez activé "Inclure les équipements Jeedom"

## 🔍 Dépannage

### Les IDs ne correspondent pas?
Les IDs affichés dans les tests doivent correspondre à ceux visibles dans Jeedom.

**Pour vérifier:**
1. Allez dans Outils → Résumé Domotique
2. Notez les IDs des équipements
3. Comparez avec le résultat du test

### Les commandes ne s'exécutent pas?
Vérifiez dans la console Jeedom:
1. Allez dans Analyse → Logs
2. Sélectionnez le log `ai_connector`
3. Lancez une commande et vérifiez les erreurs

### Erreurs de permissions?
Les tests nécessitent les permissions admin. Vérifiez:
- Vous êtes connecté comme admin
- L'authentification Jeedom est valide

## 📊 Résultat attendu

Un test réussi affiche:
- ✓ Au moins 1 équipement
- ✓ Au moins 1 commande par équipement
- ✓ Au moins 1 commande d'action
- ✓ Un contexte IA non vide
- ✓ Aucune erreur PHP

## 🚀 Prochaines étapes

Si les tests passent:
1. ✓ Les équipements sont bien détectés
2. ✓ L'IA peut les voir
3. ✓ L'IA peut les contrôler

Vous pouvez maintenant:
- Tester l'IA en console JavaScript: `aiConnector.sendMessage(1, 'Allume le salon')`
- Vérifier les logs: `Analyse → Logs → ai_connector`
- Utiliser l'IA pour vraiment contrôler vos équipements

## 📝 Logs à consulter

Après un test ou une action IA, consultez:
- `plugins/ai_connector/log/ai_connector_daemon` - Logs du démon
- `var/log/core` - Logs généraux Jeedom
