# 🤖 Équipements IA - Guide complet

## Comment fonctionne l'IA Connector

### 1. Vue d'ensemble du flux

```
┌─────────────────────────────────────────────────────────┐
│                   Équipement IA Connector               │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  1. Vous tapez:  "Allume le salon"                    │
│                          ↓                             │
│  2. Envoyé à:   API IA (Gemini/OpenAI/Mistral)       │
│                          ↓                             │
│  3. L'IA voit:   Liste de tous les équipements        │
│     (Lumière salon, Thermostat, Volets, etc.)        │
│                          ↓                             │
│  4. L'IA génère: [EXEC_COMMAND: 123]                 │
│     (Allumer la lumière salon)                        │
│                          ↓                             │
│  5. Jeedom:      Exécute la commande                 │
│                          ↓                             │
│  6. Réponse:     "Lumière du salon allumée ✓"        │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

### 2. Récupération des équipements

#### 2.1 Comment l'IA voit vos équipements

L'IA récupère:

```
Pour chaque équipement Jeedom:
├── ID (identifiant unique)
├── Nom (ex: "Lumière salon")
├── État actuel (allumée/éteinte)
├── Type (Lumière, Thermostat, etc.)
└── Commandes disponibles:
    ├── ID de la commande
    ├── Nom (ex: "On/Off")
    ├── Type (action/info)
    └── Type de retour (binaire/numérique/text)
```

#### 2.2 Méthode PHP

```php
// core/class/ai_connector.class.php

// Récupérer TOUS les équipements
$equipments = ai_connector::getAllEquipments();

// Récupérer les COMMANDES d'un équipement
$commands = ai_connector::getEquipmentCommands($equipment_id);
```

#### 2.3 Résultat JSON

```json
{
  "equipments": [
    {
      "id": 1,
      "name": "Lumière salon",
      "type": "Lumière",
      "status": "ON",
      "commands": [
        {
          "id": 10,
          "name": "On",
          "type": "action"
        },
        {
          "id": 11,
          "name": "Off",
          "type": "action"
        },
        {
          "id": 12,
          "name": "État",
          "type": "info"
        }
      ]
    },
    {
      "id": 2,
      "name": "Thermostat salon",
      "type": "Thermostat",
      "status": 21.5,
      "commands": [
        {
          "id": 20,
          "name": "Chauffer",
          "type": "action"
        },
        {
          "id": 21,
          "name": "Température",
          "type": "info"
        }
      ]
    }
  ]
}
```

### 3. Format des commandes IA

#### 3.1 Format basique

L'IA utilise ce format pour exécuter une commande:

```
[EXEC_COMMAND: <command_id>]
```

**Exemple:**
```
L'IA: "Je vais allumer la lumière du salon pour vous"
[EXEC_COMMAND: 10]
```

#### 3.2 Format avec paramètres

Pour les commandes avec valeur:

```
[EXEC_COMMAND: <command_id> value=<valeur>]
```

**Exemple:**
```
L'IA: "Je vais mettre le thermostat à 22°C"
[EXEC_COMMAND: 20 value=22]
```

#### 3.3 Format avec multiple paramètres

```
[EXEC_COMMAND: <command_id> param1=val1 param2=val2]
```

**Exemple:**
```
L'IA: "Je vais fermer les volets du salon à 50%"
[EXEC_COMMAND: 30 duration=5000 level=50]
```

### 4. Exécution des commandes

#### 4.1 Méthode PHP

```php
// core/class/ai_connector.class.php

// Exécuter une commande
$result = ai_connector::executeJeedomCommand($command_id, $options);

// Avec paramètre
$result = ai_connector::executeJeedomCommand(20, ['value' => 22]);
```

#### 4.2 Validation

Avant exécution, le système vérifie:

```
✓ La commande existe
✓ Les paramètres sont valides
✓ Le type d'équipement est compatible
✓ Pas de boucle infinie (anti-loop)
✓ L'utilisateur a les permissions
```

#### 4.3 Résultat

```php
$result = [
    'success' => true,           // Commande exécutée?
    'command_id' => 10,          // ID de la commande
    'command_name' => 'On',      // Nom de la commande
    'equipment_id' => 1,         // ID de l'équipement
    'equipment_name' => 'Salon', // Nom de l'équipement
    'message' => 'Lumière allumée'
];
```

### 5. Contexte Jeedom pour l'IA

#### 5.1 Qu'est-ce que le contexte?

Le contexte est le document que l'IA reçoit pour comprendre votre maison:

```
{
  "jeedom_info": {
    "name": "Ma Maison",
    "version": "4.4.0",
    "admin": "User123"
  },
  "equipments": [
    {
      "id": 1,
      "name": "Lumière salon",
      "object": "Salon",
      "state": "ON",
      "type": "Lumière",
      "commands": [
        {"id": 10, "name": "On"},
        {"id": 11, "name": "Off"}
      ]
    }
    // ... autres équipements
  ],
  "statistics": {
    "total_equipments": 15,
    "active_equipments": 12,
    "powered_off": 3
  }
}
```

#### 5.2 Méthode PHP

```php
// Récupérer le contexte
$context = ai_connector::getJeedomContextForAI();
echo json_encode($context, JSON_PRETTY_PRINT);
```

### 6. Traitement des commandes IA

#### 6.1 Étapes du traitement

```
1. Réception du texte utilisateur
   ↓
2. Envoi à l'API IA (Gemini/OpenAI/Mistral)
   ↓
3. Contexte inclus (équipements, commandes)
   ↓
4. L'IA génère une réponse
   ↓
5. Extraction des [EXEC_COMMAND: id]
   ↓
6. Validation de chaque commande
   ↓
7. Exécution des commandes
   ↓
8. Compilation de la réponse finale
   ↓
9. Retour à l'utilisateur
```

#### 6.2 Méthode PHP

```php
// Traiter une commande IA
$user_input = "Allume la lumière du salon";
$result = ai_connector::processAICommands($user_input);

echo "Réponse: " . $result['response'];
// Réponse: Lumière du salon allumée ✓
```

### 7. Endpoints AJAX

#### 7.1 Récupérer tous les équipements

```javascript
// GET /plugins/ai_connector/core/ajax/ai_connector.ajax.php
// Action: getAllEquipments

fetch('/plugins/ai_connector/core/ajax/ai_connector.ajax.php?action=getAllEquipments')
  .then(r => r.json())
  .then(data => console.log(data));
```

#### 7.2 Récupérer les commandes d'un équipement

```javascript
fetch('/plugins/ai_connector/core/ajax/ai_connector.ajax.php?action=getEquipmentCommands&id=1')
  .then(r => r.json())
  .then(data => console.log(data));
```

#### 7.3 Exécuter une commande

```javascript
fetch('/plugins/ai_connector/core/ajax/ai_connector.ajax.php?action=executeCommand&id=10&value=22')
  .then(r => r.json())
  .then(data => console.log(data));
```

### 8. Exemples pratiques

#### Exemple 1: Question simple

```
Utilisateur: "Quel est la température actuelle?"

→ L'IA voit tous les capteurs de température
→ L'IA répond: "La température du salon est 21.5°C"
```

#### Exemple 2: Exécution de commande

```
Utilisateur: "Éteins les lumières du salon"

→ L'IA génère: [EXEC_COMMAND: 11]
→ Jeedom: Exécute commande 11 (Éteindre lumière salon)
→ L'IA répond: "Lumières du salon éteintes ✓"
```

#### Exemple 3: Comando avec paramètre

```
Utilisateur: "Mets le chauffage à 23 degrés"

→ L'IA génère: [EXEC_COMMAND: 20 value=23]
→ Jeedom: Exécute commande 20 avec value=23
→ L'IA répond: "Chauffage réglé à 23°C ✓"
```

#### Exemple 4: Scénario complexe

```
Utilisateur: "Je vais me coucher, tout éteindre"

→ L'IA génère:
   [EXEC_COMMAND: 11]  (Éteindre lumière salon)
   [EXEC_COMMAND: 12]  (Éteindre lumière chambre)
   [EXEC_COMMAND: 13]  (Fermer volets)
   [EXEC_COMMAND: 14]  (Alarme activée)

→ Jeedom: Exécute les 4 commandes
→ L'IA répond: "Maison sécurisée pour la nuit ✓"
```

### 9. Dépannage des équipements

#### 9.1 Je n'ai pas d'équipements dans l'IA

Vérifiez:
```
1. [ ] "Inclure les équipements Jeedom" est coché
2. [ ] Vous avez au moins un équipement créé
3. [ ] L'équipement n'a pas la flag "Ne pas exposer à l'IA"
4. [ ] Redémarrez le daemon si changement récent
```

#### 9.2 L'IA voit les équipements mais ne les commande pas

Vérifiez:
```
1. [ ] Les commandes existent (action type)
2. [ ] Les commandes ne sont pas désactivées
3. [ ] Les permissions Jeedom sont correctes
4. [ ] Pas d'erreur dans les logs
```

#### 9.3 La commande s'exécute mais le résultat est incorrect

Vérifiez:
```
1. [ ] Le format [EXEC_COMMAND: id] est correct
2. [ ] Les paramètres sont valides
3. [ ] Pas d'anti-loop qui bloque
4. [ ] Le délai de timeout est suffisant
```

---

**Prochaine étape:** [Outils et tests](04_OUTILS_TESTS.md)
