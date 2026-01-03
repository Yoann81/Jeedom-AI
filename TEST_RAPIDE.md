# 🚀 Test Rapide - Équipements IA Jeedom

## ⚡ Commandes de test dans la console navigateur

Ouvrez la console JavaScript de votre navigateur (F12) et utilisez ces commandes pour tester:

### 1. Lister tous les équipements
```javascript
aiConnector.getAllEquipments().then(eq => console.table(eq));
```

### 2. Afficher le contexte IA
Remplacez `1` par l'ID de votre équipement IA:
```javascript
aiConnector.getJeedomContext(1).then(ctx => console.log(ctx));
```

### 3. Lister les commandes d'un équipement
Remplacez `5` par l'ID d'un équipement:
```javascript
aiConnector.listEquipmentCommands(5);
```

### 4. Envoyer un message à l'IA
```javascript
aiConnector.sendMessage(1, 'Salut, comment ça va?').then(r => console.log('Réponse:', r));
```

### 5. Exécuter une commande Jeedom
```javascript
aiConnector.executeCommand(42).then(r => console.log('Résultat:', r));
```

## 📋 Checklist d'installation

- [ ] Vous avez créé un équipement IA dans Jeedom
- [ ] Vous avez configuré la clé API
- [ ] Vous avez ajouté un prompt système
- [ ] Vous avez activé "Inclure les équipements Jeedom"
- [ ] Les endpoints AJAX répondent correctement
- [ ] Le contexte IA s'ajoute aux prompts

## 🎯 Première utilisation

1. **Créer un équipement IA** dans Jeedom
   - Type: Jeedom-AI
   - Nom: Assistant Maison
   - Moteur: Gemini (ou OpenAI/Mistral)
   - API Key: Votre clé

2. **Configurer le prompt**
   ```
   Tu es un assistant IA pour contrôler ma maison Jeedom.
   Aide-moi à gérer mes équipements intelligents.
   ```

3. **Activer l'option**
   - Inclure les équipements Jeedom: OUI

4. **Tester dans la console**
   ```javascript
   aiConnector.sendMessage(1, 'Allume la lumière').then(r => alert(r));
   ```

## 🔧 Debug

### Les équipements ne s'affichent pas?
```javascript
// Vérifier s'ils sont activés
aiConnector.getAllEquipments().then(eq => {
    console.log('Total:', eq.length);
    console.log('Activés:', eq.filter(e => e.isEnable).length);
    console.table(eq);
});
```

### Les commandes ne s'exécutent pas?
```javascript
// Vérifier les IDs
aiConnector.listEquipmentCommands(5).then(cmds => {
    console.log('Total commandes:', cmds.length);
    console.table(cmds.filter(c => c.isVisible));
});
```

### Le contexte IA est vide?
```javascript
aiConnector.getJeedomContext(1).then(ctx => {
    if (ctx.includes('ÉQUIPEMENTS')) {
        console.log('✓ Contexte chargé');
    } else {
        console.log('✗ Contexte vide');
    }
});
```

## 💡 Exemples de prompts testés

### Assistant généraliste
```
Tu es un assistant IA pour la domotique Jeedom en français.
Tu peux voir et contrôler tous les équipements de la maison.
Sois courtois et utile. Confirme chaque action.
```

### Assistant spécialisé énergie
```
Tu es expert en gestion énergétique.
Analyse ma consommation et propose des optimisations.
Contrôle les équipements pour réduire la consommation.
```

### Assistant ludique
```
Tu es un assistant IA amusant pour ma maison.
Sois créatif et utile. Raconte des blagues!
Utilise des emojis. Aide-moi à contrôler mes équipements.
```

## 📊 Logs utiles

Vérifiez les logs dans Jeedom:
- `plugins/ai_connector/log/ai_connector_daemon` - Logs du démon
- Console Jeedom - Logs en temps réel

## 🆘 Support

Si quelque chose ne fonctionne pas:

1. Vérifiez la console navigateur (F12)
2. Vérifiez les logs Jeedom
3. Testez les endpoints AJAX manuellement
4. Assurez-vous que Jeedom a les droits nécessaires
5. Redémarrez le démon si nécessaire

## ✅ Cas d'usage supportés

✓ Allumer/éteindre des équipements
✓ Réguler la luminosité
✓ Contrôler la température
✓ Ouvrir/fermer des portes
✓ Mettre en scène (lumière + température)
✓ Gérer la consommation énergétique
✓ Programmer des automations via IA

## 🚫 Limitations actuelles

✗ Pas de boucles de feedback en temps réel
✗ Pas de cron/planification via IA (utiliser Jeedom)
✗ Pas d'apprentissage persistant (réinitialiser à chaque appel)
✗ Latence réseau de l'API IA (généralement 2-5s)

## 📝 Notes de sécurité

- Les endpoints AJAX nécessitent une authentification admin
- Testez d'abord sur des équipements non critiques
- Validez les prompts pour éviter les injections
- Les logs contiennent les demandes (attention aux données sensibles)
