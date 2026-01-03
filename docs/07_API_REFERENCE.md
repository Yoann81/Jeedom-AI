# 📚 Référence API complète

## Endpoints AJAX

### Vue d'ensemble

**Base URL:** `/plugins/ai_connector/core/ajax/ai_connector.ajax.php`

**Authentification:** Admin uniquement

**Format:** GET/POST avec action en paramètre

---

### 1. getAllEquipments

#### Description
Récupère la liste complète de tous les équipements Jeedom

#### Requête

```http
GET /plugins/ai_connector/core/ajax/ai_connector.ajax.php?action=getAllEquipments
```

#### Paramètres
Aucun

#### Réponse (200 OK)

```json
{
  "success": true,
  "equipments": [
    {
      "id": 1,
      "name": "Lumière salon",
      "type": "Lumière",
      "object": "Salon",
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
    }
  ]
}
```

#### Codes d'erreur

| Code | Raison |
|------|--------|
| 401 | Non authentifié |
| 403 | Accès refusé |
| 500 | Erreur serveur |

#### Exemple JavaScript

```javascript
fetch('/plugins/ai_connector/core/ajax/ai_connector.ajax.php?action=getAllEquipments')
  .then(r => r.json())
  .then(data => console.log(data.equipments));
```

---

### 2. getEquipmentCommands

#### Description
Récupère les commandes d'un équipement spécifique

#### Requête

```http
GET /plugins/ai_connector/core/ajax/ai_connector.ajax.php?action=getEquipmentCommands&id=1
```

#### Paramètres

| Nom | Type | Obligatoire | Description |
|-----|------|-------------|-------------|
| id | int | ✓ | ID de l'équipement |

#### Réponse (200 OK)

```json
{
  "success": true,
  "equipment_id": 1,
  "equipment_name": "Lumière salon",
  "commands": [
    {
      "id": 10,
      "name": "On",
      "type": "action",
      "subtype": null,
      "unit": "",
      "minValue": null,
      "maxValue": null
    },
    {
      "id": 11,
      "name": "Off",
      "type": "action",
      "subtype": null,
      "unit": "",
      "minValue": null,
      "maxValue": null
    }
  ]
}
```

#### Codes d'erreur

| Code | Raison |
|------|--------|
| 400 | ID manquant |
| 404 | Équipement non trouvé |
| 401 | Non authentifié |
| 500 | Erreur serveur |

#### Exemple JavaScript

```javascript
fetch('/plugins/ai_connector/core/ajax/ai_connector.ajax.php?action=getEquipmentCommands&id=1')
  .then(r => r.json())
  .then(data => console.log(data.commands));
```

---

### 3. executeCommand

#### Description
Exécute une commande Jeedom

#### Requête

```http
GET /plugins/ai_connector/core/ajax/ai_connector.ajax.php?action=executeCommand&id=10&value=22
```

#### Paramètres

| Nom | Type | Obligatoire | Description |
|-----|------|-------------|-------------|
| id | int | ✓ | ID de la commande |
| value | string/int | ✗ | Valeur du paramètre |

#### Réponse (200 OK)

```json
{
  "success": true,
  "command_id": 10,
  "command_name": "On",
  "equipment_id": 1,
  "equipment_name": "Lumière salon",
  "message": "Lumière allumée"
}
```

#### Codes d'erreur

| Code | Raison |
|------|--------|
| 400 | ID manquant |
| 404 | Commande non trouvée |
| 409 | Boucle infinie détectée |
| 401 | Non authentifié |
| 500 | Erreur exécution |

#### Exemple JavaScript

```javascript
// Sans paramètre
fetch('/plugins/ai_connector/core/ajax/ai_connector.ajax.php?action=executeCommand&id=10')
  .then(r => r.json())
  .then(data => console.log(data.message));

// Avec paramètre
fetch('/plugins/ai_connector/core/ajax/ai_connector.ajax.php?action=executeCommand&id=20&value=22')
  .then(r => r.json())
  .then(data => console.log(data.message));
```

---

### 4. getJeedomContext

#### Description
Récupère le contexte complet pour l'IA (équipements + informations Jeedom)

#### Requête

```http
GET /plugins/ai_connector/core/ajax/ai_connector.ajax.php?action=getJeedomContext
```

#### Paramètres
Aucun

#### Réponse (200 OK)

```json
{
  "success": true,
  "jeedom_info": {
    "name": "Ma Maison",
    "version": "4.4.0",
    "admin": "admin",
    "location": "France",
    "timezone": "Europe/Paris"
  },
  "equipments": [
    {
      "id": 1,
      "name": "Lumière salon",
      "type": "Lumière",
      "status": "ON",
      "commands": []
    }
  ],
  "statistics": {
    "total_equipments": 15,
    "active_equipments": 12,
    "powered_off": 3,
    "last_activity": "2026-01-03 14:30:00"
  }
}
```

#### Codes d'erreur

| Code | Raison |
|------|--------|
| 401 | Non authentifié |
| 500 | Erreur serveur |

#### Exemple JavaScript

```javascript
fetch('/plugins/ai_connector/core/ajax/ai_connector.ajax.php?action=getJeedomContext')
  .then(r => r.json())
  .then(data => console.log(data.jeedom_info));
```

---

### 5. getAllEquipmentsWithCommands

#### Description
Récupère tous les équipements avec toutes leurs commandes (endpoint combiné)

#### Requête

```http
POST /plugins/ai_connector/core/ajax/ai_connector.ajax.php
Content-Type: application/x-www-form-urlencoded

action=getAllEquipmentsWithCommands
```

#### Paramètres
Aucun

#### Réponse (200 OK)

```json
{
  "success": true,
  "data": {
    "equipments": [...],
    "total": 15,
    "timestamp": "2026-01-03T14:30:00Z"
  }
}
```

#### Codes d'erreur

| Code | Raison |
|------|--------|
| 401 | Non authentifié |
| 500 | Erreur serveur |

---

## Méthodes PHP

### Vue d'ensemble

**Fichier:** `core/class/ai_connector.class.php`

**Classe:** `ai_connector extends eqLogic`

---

### 1. getAllEquipments()

#### Description
Récupère tous les équipements Jeedom

#### Signature

```php
public static function getAllEquipments()
```

#### Retour

```php
Array(
  0 => Array(
    'id' => 1,
    'name' => 'Lumière salon',
    'type' => 'Lumière',
    'object' => 'Salon',
    'status' => 'ON',
    'commands' => Array(...)
  ),
  ...
)
```

#### Exemple

```php
$equipments = ai_connector::getAllEquipments();
foreach ($equipments as $equipment) {
  echo $equipment['name'] . ': ' . $equipment['status'] . "\n";
}
```

---

### 2. getEquipmentCommands($equipmentId)

#### Description
Récupère les commandes d'un équipement

#### Signature

```php
public static function getEquipmentCommands($equipmentId)
```

#### Paramètres

| Nom | Type | Description |
|-----|------|-------------|
| equipmentId | int | ID de l'équipement |

#### Retour

```php
Array(
  'equipment_id' => 1,
  'equipment_name' => 'Lumière salon',
  'commands' => Array(
    0 => Array(
      'id' => 10,
      'name' => 'On',
      'type' => 'action'
    ),
    ...
  )
)
```

#### Exemple

```php
$commands = ai_connector::getEquipmentCommands(1);
foreach ($commands['commands'] as $cmd) {
  echo $cmd['name'] . ' (ID: ' . $cmd['id'] . ")\n";
}
```

---

### 3. executeJeedomCommand($commandId, $options)

#### Description
Exécute une commande Jeedom

#### Signature

```php
public static function executeJeedomCommand($commandId, $options = array())
```

#### Paramètres

| Nom | Type | Description |
|-----|------|-------------|
| commandId | int | ID de la commande |
| options | array | Options (value, extra, etc.) |

#### Retour

```php
Array(
  'success' => true,
  'command_id' => 10,
  'command_name' => 'On',
  'equipment_id' => 1,
  'equipment_name' => 'Lumière salon',
  'message' => 'Lumière allumée'
)
```

#### Exemple

```php
// Sans paramètre
$result = ai_connector::executeJeedomCommand(10);

// Avec paramètre
$result = ai_connector::executeJeedomCommand(20, ['value' => 22]);

if ($result['success']) {
  log::add('ai_connector', 'info', $result['message']);
}
```

---

### 4. getJeedomContextForAI()

#### Description
Récupère le contexte complet pour l'IA

#### Signature

```php
public static function getJeedomContextForAI()
```

#### Retour

```php
Array(
  'jeedom_info' => Array(...),
  'equipments' => Array(...),
  'statistics' => Array(...)
)
```

#### Exemple

```php
$context = ai_connector::getJeedomContextForAI();
$json = json_encode($context, JSON_PRETTY_PRINT);
echo $json;
```

---

### 5. processAICommands($userInput)

#### Description
Traite une commande utilisateur via l'IA

#### Signature

```php
public static function processAICommands($userInput)
```

#### Paramètres

| Nom | Type | Description |
|-----|------|-------------|
| userInput | string | Texte de l'utilisateur |

#### Retour

```php
Array(
  'response' => 'Réponse textuelle de l\'IA',
  'commands_executed' => 2,
  'commands' => Array(
    0 => Array(
      'id' => 10,
      'status' => 'success'
    ),
    ...
  ),
  'errors' => Array()
)
```

#### Exemple

```php
$result = ai_connector::processAICommands("Allume la lumière du salon");
echo $result['response'];
```

---

### 6. callAIEngine($prompt, $context)

#### Description
Appelle directement le moteur IA configuré

#### Signature

```php
public static function callAIEngine($prompt, $context = null)
```

#### Paramètres

| Nom | Type | Description |
|-----|------|-------------|
| prompt | string | Prompt pour l'IA |
| context | array | Contexte optionnel |

#### Retour

```php
String (réponse de l'IA)
```

#### Exemple

```php
$response = ai_connector::callAIEngine("Quelle est ta fonction?");
echo $response;
```

---

## Formats de commandes

### Format standard

```
[EXEC_COMMAND: <command_id>]
```

**Exemple:**
```
[EXEC_COMMAND: 10]
```

### Format avec valeur

```
[EXEC_COMMAND: <command_id> value=<valeur>]
```

**Exemple:**
```
[EXEC_COMMAND: 20 value=22]
```

### Format avec paramètres multiples

```
[EXEC_COMMAND: <command_id> param1=val1 param2=val2]
```

**Exemple:**
```
[EXEC_COMMAND: 30 duration=5000 level=50]
```

---

## Codes de réponse HTTP

| Code | Signification |
|------|---------------|
| 200 | OK - Requête réussie |
| 201 | Created - Ressource créée |
| 204 | No Content - Succès sans contenu |
| 400 | Bad Request - Paramètre invalide |
| 401 | Unauthorized - Non authentifié |
| 403 | Forbidden - Accès refusé |
| 404 | Not Found - Ressource non trouvée |
| 409 | Conflict - Anti-loop détecté |
| 429 | Too Many Requests - Limite atteinte |
| 500 | Internal Server Error - Erreur serveur |
| 503 | Service Unavailable - Service indisponible |

---

## Types de données

### Equipment

```json
{
  "id": 1,
  "name": "string",
  "type": "string",
  "object": "string",
  "status": "string|number",
  "commands": [...]
}
```

### Command

```json
{
  "id": 10,
  "name": "string",
  "type": "action|info",
  "subtype": "string|null",
  "unit": "string",
  "minValue": "number|null",
  "maxValue": "number|null"
}
```

### Response

```json
{
  "success": "boolean",
  "message": "string",
  "data": "mixed|null",
  "timestamp": "ISO-8601 timestamp"
}
```

---

## Authentification

### Via Jeedom

Toutes les requêtes API héritent de l'authentification Jeedom.

```javascript
// Automatique si connecté à Jeedom
fetch('/plugins/ai_connector/core/ajax/ai_connector.ajax.php?action=getAllEquipments')
  .then(r => r.json());
```

### Via Token

Si implémentation personnalisée:

```php
if (!isConnect('admin')) {
  throw new Exception('Unauthorized');
}
```

---

## Limites et quotas

### Limites d'appels

| Ressource | Limite | Période |
|-----------|--------|---------|
| getAllEquipments | 100 | Par minute |
| executeCommand | 50 | Par minute |
| Appels IA | 10 | Par minute (dépend de l'API) |

### Gestion du timeout

```php
set_time_limit(60);  // 60 secondes max
```

---

## Caching

### Stratégie de cache

```php
$cache_key = 'ai_connector_equipments';
$cache_ttl = 300;  // 5 minutes

// Vérifier le cache
$cached = cache::byKey($cache_key)->getValue();

// Sauvegarder en cache
cache::set($cache_key, $data, $cache_ttl);
```

---

**Prochaines étapes:**
- [Exemples de configuration](08_EXEMPLES.md)
- [FAQ](09_FAQ.md)
