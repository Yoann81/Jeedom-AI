# Guide d'utilisation - Équipements IA avec Jeedom

## 🎯 Objectif
Vos équipements IA (Gemini, OpenAI, Mistral) peuvent maintenant:
- **Voir** tous les équipements Jeedom de votre installation
- **Consulter** les commandes disponibles de chaque équipement
- **Commander** les équipements via votre IA

## ⚙️ Configuration

### 1. Activation de la fonctionnalité
Dans la configuration de votre équipement IA, assurez-vous que l'option **"Inclure les équipements Jeedom"** est activée (par défaut: OUI).

### 2. Configurer le prompt système
Ajoutez des instructions dans le prompt pour guider l'IA sur comment contrôler vos équipements. Exemple:

```
Tu es un assistant IA pour la maison intelligente Jeedom. 
Tu dois aider l'utilisateur à contrôler ses équipements.
Quand il demande une action:
1. Identifie l'équipement cible
2. Utilise la commande appropriée
3. Formate ainsi: [EXEC_COMMAND: id_commande]
4. Confirme l'action exécutée
```

## 📱 Exemples d'utilisation

### Exemple 1: Allumer une lumière
**Utilisateur:** "Allume la lumière du salon"

**IA identifie:**
- Équipement: Salon (ID: 5)
- Commande: On (ID: 42)

**IA répond:** 
```
[EXEC_COMMAND: 42]
J'ai allumé la lumière du salon pour vous.
```

### Exemple 2: Réguler la température
**Utilisateur:** "Mets le thermostat à 22 degrés"

**IA identifie:**
- Équipement: Thermostat (ID: 8)
- Commande: Température (ID: 67)

**IA répond:**
```
[EXEC_COMMAND: 67] avec la valeur 22
Thermostat réglé à 22°C.
```

## 🔌 Endpoints AJAX disponibles

### 1. getAllEquipments
Récupère tous les équipements Jeedom (sauf IA)

```javascript
$.ajax({
    type: 'POST',
    url: 'core/ajax/ai_connector.ajax.php',
    data: {action: 'getAllEquipments'},
    success: function(data) {
        console.log(data); // Array of equipments
    }
});
```

**Réponse:**
```json
[
    {
        "id": 5,
        "name": "Salon",
        "type": "light",
        "humanName": "Salon [Lumière]",
        "isEnable": true,
        "status": "On"
    }
]
```

### 2. getEquipmentCommands
Récupère les commandes d'un équipement

```javascript
$.ajax({
    type: 'POST',
    url: 'core/ajax/ai_connector.ajax.php',
    data: {
        action: 'getEquipmentCommands',
        eq_id: 5
    },
    success: function(data) {
        console.log(data); // Array of commands
    }
});
```

### 3. executeCommand
Exécute une commande Jeedom

```javascript
$.ajax({
    type: 'POST',
    url: 'core/ajax/ai_connector.ajax.php',
    data: {
        action: 'executeCommand',
        cmd_id: 42,
        options: '{"value": 22}' // JSON stringifié
    },
    success: function(data) {
        console.log(data); // "Commande exécutée avec succès"
    }
});
```

### 4. getJeedomContext
Récupère le contexte formaté pour l'IA (équipements + instructions)

```javascript
$.ajax({
    type: 'POST',
    url: 'core/ajax/ai_connector.ajax.php',
    data: {
        action: 'getJeedomContext',
        eq_id: 1 // ID de l'équipement IA
    },
    success: function(data) {
        console.log(data); // Contexte formaté
    }
});
```

### 5. getAllEquipmentsWithCommands
Récupère tous les équipements avec leurs commandes

```javascript
$.ajax({
    type: 'POST',
    url: 'core/ajax/ai_connector.ajax.php',
    data: {action: 'getAllEquipmentsWithCommands'},
    success: function(data) {
        console.log(data); // Array of equipments with commands
    }
});
```

## 🔍 Format du contexte IA

Quand vous posez une question à votre IA, elle reçoit le contexte suivant:

```
=== ÉQUIPEMENTS JEEDOM DISPONIBLES ===

📱 Salon [Lumière] (ID: 5)
Type: light
Commandes:
  - On (ID: 42) [action/other]
  - Off (ID: 43) [action/other]
  - Luminosité (ID: 44) [action/slider]
    Valeur actuelle: 85 %

📱 Chambre [Thermostat] (ID: 8)
Type: thermostat
Commandes:
  - Température (ID: 67) [action/slider]
    Valeur actuelle: 21 °C
  - Mode (ID: 68) [action/other]

=== INSTRUCTIONS ===
Tu peux contrôler les équipements Jeedom. Quand l'utilisateur demande quelque chose:
1. Identifie l'équipement et la commande correspondante
2. Utilise le format: [EXEC_COMMAND: id_commande]
3. Confirme l'action à l'utilisateur
```

## 🎛️ Options de configuration

Dans chaque équipement IA, vous pouvez configurer:

- **Inclure les équipements Jeedom** (1/0): Active/désactive le contexte Jeedom
- **Moteur IA** (gemini/openai/mistral): Choix du moteur
- **Clé API**: Votre clé API pour le moteur
- **Prompt système**: Instructions personnalisées pour l'IA
- **Écoute vocale**: Active la reconnaissance vocale
- **TTS**: Active la synthèse vocale

## ⚠️ Sécurité

- L'accès aux endpoints AJAX nécessite une authentification admin
- Les commandes exécutées respectent les droits Jeedom
- Testez d'abord sur des équipements non critiques
- Utilisez des prompts explicites pour les actions sensibles

## 🐛 Dépannage

### L'IA n'exécute pas les commandes
- Vérifiez que "Inclure les équipements Jeedom" est activé
- Vérifiez les logs de Jeedom pour les erreurs
- Testez manuellement la commande

### Les équipements ne s'affichent pas
- Vérifiez que les équipements sont activés
- Vérifiez que ce ne sont pas des équipements IA
- Vérifiez les droits d'accès

### Erreurs d'exécution
- Consultez le log `ai_connector_daemon`
- Vérifiez l'API de l'IA (quota, clé valide, etc.)
- Réessayez avec un prompt plus explicite

## 📝 Notes

- Le contexte s'ajoute automatiquement au prompt système
- Les commandes au format `[EXEC_COMMAND: id]` sont exécutées automatiquement
- Les balises de commande sont supprimées de la réponse visible
- Le système détecte et évite les boucles infinies
