# ⚠️ Aucun équipement détecté - Guide de solution

## 🔍 Diagnostic

Vous avez vu ceci:
```
=== 4. ÉQUIPEMENTS DISPONIBLES ===
Total: 0
```

Cela signifie que **l'IA n'a accès à aucun équipement pour contrôler**.

## 🚨 Causes possibles

### 1. **Aucun équipement dans Jeedom** ❌
L'installation de Jeedom ne contient aucun équipement.

**Solution:**
- Allez dans **Plugins** → **Communication** (ou autre catégorie)
- Créez des équipements:
  - Lumières connectées
  - Thermostats
  - Serrures
  - Capteurs
  - etc.

### 2. **Équipements désactivés** ⚠️
Tous les équipements sont désactivés.

**Vérifier:**
```
Outils → Résumé Domotique
Vérifiez que les équipements sont cochés (activés)
```

**Activer:**
- Cliquez sur l'équipement
- Cochez "Activer"
- Sauvegardez

### 3. **Tous les équipements sont des IA** 🤖
L'installation ne contient que des équipements AI Connector.

**Solution:**
- Créez des équipements réels (non-IA)
- Ensuite l'IA pourra les contrôler

## ✅ Comment créer des équipements

### Via Jeedom nativement

**Lumière (virtuelle):**
1. Allez dans **Plugins** → **Outils** → **Commande virtuelle**
2. Créez une nouvelle commande:
   - Nom: "Lumière Salon"
   - Type: Lumière
   - Ajouter des sous-commandes (On, Off)
3. Sauvegardez

### Via un plugin

**Exemple avec Z-Wave, ZigBee, etc.:**
1. Installez le plugin correspondant
2. Synchronisez vos équipements (appairage)
3. Les équipements apparaîtront automatiquement

### Via Jeedom virtuel (pour tester)

1. **Créez un équipement virtuel:**
   ```
   Plugins → Outils → Commande virtuelle
   ```

2. **Ajoutez des commandes:**
   - Action: On
   - Action: Off
   - Info: État
   - Info: Luminosité (slider)

3. **Sauvegardez**

4. **Vérifiez qu'il apparaît dans le diagnostic:**
   ```
   http://votre-jeedom/plugins/ai_connector/core/php/diagnostic.php
   ```

## 🧪 Tester avec des équipements virtuels

Créez une petite installation de test:

```
1. Équipement virtuel "Lumière Test"
   - Commande On (action)
   - Commande Off (action)

2. Équipement virtuel "Thermostat Test"
   - Commande Température (action slider, 15-30)
   - Commande Température actuelle (info)

3. Équipement virtuel "Volets Test"
   - Commande Ouvrir (action)
   - Commande Fermer (action)
   - Commande Position (action slider, 0-100)
```

## 📊 Après avoir créé des équipements

1. **Relancez le diagnostic:**
   ```
   http://votre-jeedom/plugins/ai_connector/core/php/diagnostic.php
   ```

2. **Vous devriez voir:**
   ```
   === 4. ÉQUIPEMENTS DISPONIBLES ===
   Total trouvés: 3
   Premiers équipements:
     • Salon [Lumière] (ID: 5, Type: light, Commandes: 3)
     • Chambre [Thermostat] (ID: 6, Type: thermostat, Commandes: 2)
     • Cuisine [Volets] (ID: 7, Type: cover, Commandes: 3)
   ```

3. **Testez l'IA:**
   ```javascript
   // Console du navigateur (F12)
   aiConnector.sendMessage(89, 'Allume la lumière du salon');
   ```

## 🎯 Étapes pour démarrer

1. ✅ Créer un équipement virtuel (test rapide)
2. ✅ Activer l'équipement
3. ✅ Relancer le diagnostic
4. ✅ Vérifier que l'équipement apparaît
5. ✅ Tester avec l'IA

## 📝 Exemple complet pour débuter

### Créer un équipement virtuel "Lumière Salon"

1. Allez dans **Plugins → Outils → Commande virtuelle**
2. Cliquez sur **"Ajouter"**
3. Nom: `Lumière Salon`
4. Objet parent: (optionnel) `Salon`
5. **Créer les commandes:**

   **Commande 1: On**
   - Nom: `On`
   - Type: `Action`
   - Sous-type: `Défaut`

   **Commande 2: Off**
   - Nom: `Off`
   - Type: `Action`
   - Sous-type: `Défaut`

   **Commande 3: Luminosité**
   - Nom: `Luminosité`
   - Type: `Action`
   - Sous-type: `Slider`
   - Min: `0`
   - Max: `100`

   **Commande 4: État**
   - Nom: `État`
   - Type: `Info`
   - Sous-type: `Binaire`

6. **Sauvegardez**

7. **Testez avec l'IA:**
   ```javascript
   aiConnector.sendMessage(89, 'Allume la lumière du salon');
   ```

## 🔗 Ressources

- [Jeedom - Documentation Virtuel](https://jeedom.github.io/documentation/fr_FR/core/4.2/object)
- [Jeedom - Commandes](https://jeedom.github.io/documentation/fr_FR/core/4.2/cmd)
- [Plugin AI Connector - Guide](../GUIDE_EQUIPEMENTS_IA.md)

## ✋ Besoin d'aide?

Si après ces étapes le diagnostic affiche toujours "Total: 0":

1. Vérifiez que les équipements créés sont **ACTIVÉS**
2. Relancez le diagnostic
3. Cherchez le message d'erreur spécifique
4. Consultez les logs Jeedom: `Analyse → Logs → ai_connector`

## 🎊 Succès!

Une fois que le diagnostic affiche vos équipements, vous pouvez:
- ✓ Tester l'IA en console: `aiConnector.sendMessage(89, 'ta commande')`
- ✓ Utiliser le STT (parole) si activé
- ✓ Créer des scénarios IA pour automatiser
