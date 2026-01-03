# 📱 Intégration JavaScript

## Classe API JavaScript ai_connector

### 1. Vue d'ensemble

La classe **AIConnectorAPI** vous permet de contrôler l'IA depuis JavaScript.

```javascript
// Créer une instance
const aiAPI = new AIConnectorAPI();

// Utiliser l'API
aiAPI.getAllEquipments().then(equipments => {
    console.log('Équipements:', equipments);
});
```

### 2. Installation

**Fichier:** `desktop/js/ai_connector.js`

**Inclusion dans votre page:**

```html
<script src="/plugins/ai_connector/desktop/js/ai_connector.js"></script>

<script>
  const aiAPI = new AIConnectorAPI();
  // Utiliser l'API...
</script>
```

### 3. Méthodes disponibles

#### 3.1 `getAllEquipments()`

**Description:** Récupère tous les équipements

**Syntaxe:**
```javascript
aiAPI.getAllEquipments()
  .then(equipments => {
    // equipments = Array
  })
  .catch(error => {
    console.error('Erreur:', error);
  });
```

**Retourne:**
```javascript
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
        }
      ]
    }
  ]
}
```

**Exemple d'utilisation:**
```javascript
aiAPI.getAllEquipments().then(data => {
  data.equipments.forEach(eq => {
    console.log(`${eq.name} (${eq.type}): ${eq.status}`);
  });
});
```

#### 3.2 `getEquipmentCommands(equipmentId)`

**Description:** Récupère les commandes d'un équipement

**Syntaxe:**
```javascript
aiAPI.getEquipmentCommands(1)  // ID de l'équipement
  .then(commands => {
    // commands = Object
  })
  .catch(error => {
    console.error('Erreur:', error);
  });
```

**Retourne:**
```javascript
{
  "equipment_id": 1,
  "equipment_name": "Lumière salon",
  "commands": [
    {
      "id": 10,
      "name": "On",
      "type": "action",
      "subtype": null
    },
    {
      "id": 11,
      "name": "Off",
      "type": "action",
      "subtype": null
    },
    {
      "id": 12,
      "name": "État",
      "type": "info",
      "subtype": "binary"
    }
  ]
}
```

**Exemple:**
```javascript
aiAPI.getEquipmentCommands(1).then(data => {
  console.log(`Commandes de ${data.equipment_name}:`);
  data.commands.forEach(cmd => {
    console.log(`  - ${cmd.name} (${cmd.type})`);
  });
});
```

#### 3.3 `executeCommand(commandId, value)`

**Description:** Exécute une commande

**Syntaxe:**
```javascript
// Sans paramètre
aiAPI.executeCommand(10)
  .then(result => {
    // result = Object
  })
  .catch(error => {
    console.error('Erreur:', error);
  });

// Avec paramètre
aiAPI.executeCommand(20, 22)  // value = 22
  .then(result => {
    // result = Object
  });
```

**Retourne:**
```javascript
{
  "success": true,
  "command_id": 10,
  "command_name": "On",
  "equipment_id": 1,
  "equipment_name": "Lumière salon",
  "message": "Lumière allumée"
}
```

**Exemple:**
```javascript
// Allumer la lumière
aiAPI.executeCommand(10).then(result => {
  if (result.success) {
    alert(result.message);  // "Lumière allumée"
  } else {
    alert('Erreur: ' + result.message);
  }
});
```

#### 3.4 `getJeedomContext()`

**Description:** Récupère le contexte complet pour l'IA

**Syntaxe:**
```javascript
aiAPI.getJeedomContext()
  .then(context => {
    // context = Object
  })
  .catch(error => {
    console.error('Erreur:', error);
  });
```

**Retourne:** Voir [Guide équipements](03_EQUIPEMENTS_IA.md#51-quest-ce-que-le-contexte)

#### 3.5 `getAllEquipmentsWithCommands()`

**Description:** Récupère tous les équipements avec leurs commandes

**Syntaxe:**
```javascript
aiAPI.getAllEquipmentsWithCommands()
  .then(data => {
    // data = Object complet
  })
  .catch(error => {
    console.error('Erreur:', error);
  });
```

**Utile pour:** Afficher un dashboard complet

### 4. Gestion des erreurs

#### 4.1 Try/catch

```javascript
async function example() {
  try {
    const equipments = await aiAPI.getAllEquipments();
    console.log(equipments);
  } catch (error) {
    console.error('Erreur réseau:', error);
  }
}

example();
```

#### 4.2 Catch sur promise

```javascript
aiAPI.getAllEquipments()
  .then(equipments => {
    console.log('Succès:', equipments);
  })
  .catch(error => {
    console.error('Erreur:', error);
    // error.message, error.status, etc.
  });
```

#### 4.3 Codes d'erreur courants

| Code | Signification |
|------|---------------|
| 200 | OK |
| 401 | Non authentifié |
| 403 | Accès refusé |
| 404 | Non trouvé |
| 500 | Erreur serveur |

### 5. Exemples pratiques

#### 5.1 Afficher tous les équipements

```javascript
async function displayAllEquipments() {
  const data = await aiAPI.getAllEquipments();
  
  const html = data.equipments.map(eq => `
    <div class="equipment">
      <h3>${eq.name}</h3>
      <p>Type: ${eq.type}</p>
      <p>État: ${eq.status}</p>
      <p>ID: ${eq.id}</p>
    </div>
  `).join('');
  
  document.getElementById('equipments-list').innerHTML = html;
}

displayAllEquipments();
```

#### 5.2 Créer un bouton de commande

```html
<!-- HTML -->
<button id="btn-lumiere-on">Allumer lumière</button>
<div id="result"></div>

<script>
document.getElementById('btn-lumiere-on').addEventListener('click', async () => {
  try {
    const result = await aiAPI.executeCommand(10);  // ID: 10 = Allumer
    
    if (result.success) {
      document.getElementById('result').innerHTML = 
        '<p style="color: green;">' + result.message + '</p>';
    } else {
      document.getElementById('result').innerHTML = 
        '<p style="color: red;">Erreur: ' + result.message + '</p>';
    }
  } catch (error) {
    document.getElementById('result').innerHTML = 
      '<p style="color: red;">Erreur: ' + error.message + '</p>';
  }
});
</script>
```

#### 5.3 Dashboard interactif

```html
<!-- HTML -->
<div id="dashboard"></div>

<script>
async function buildDashboard() {
  const data = await aiAPI.getAllEquipments();
  
  let html = '<div class="dashboard">';
  
  data.equipments.forEach(equipment => {
    html += `
      <div class="card equipment-${equipment.id}">
        <h4>${equipment.name}</h4>
        <p>État: ${equipment.status}</p>
        <div class="commands">
    `;
    
    equipment.commands.forEach(cmd => {
      if (cmd.type === 'action') {
        html += `
          <button onclick="executeCmd(${cmd.id}, '${cmd.name}')">
            ${cmd.name}
          </button>
        `;
      }
    });
    
    html += '</div></div>';
  });
  
  html += '</div>';
  document.getElementById('dashboard').innerHTML = html;
}

async function executeCmd(cmdId, cmdName) {
  try {
    const result = await aiAPI.executeCommand(cmdId);
    alert(result.message);
    buildDashboard();  // Rafraîchir le dashboard
  } catch (error) {
    alert('Erreur: ' + error.message);
  }
}

buildDashboard();
</script>

<style>
.dashboard {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 20px;
}

.card {
  border: 1px solid #ccc;
  padding: 15px;
  border-radius: 8px;
  background: #f9f9f9;
}

.card h4 {
  margin-top: 0;
}

.commands {
  display: flex;
  gap: 10px;
  margin-top: 10px;
  flex-wrap: wrap;
}

.commands button {
  flex: 1;
  min-width: 80px;
  padding: 8px;
  background: #007bff;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.commands button:hover {
  background: #0056b3;
}
</style>
```

#### 5.4 Système de recherche

```javascript
async function searchEquipments(query) {
  const data = await aiAPI.getAllEquipments();
  
  const results = data.equipments.filter(eq => 
    eq.name.toLowerCase().includes(query.toLowerCase()) ||
    eq.type.toLowerCase().includes(query.toLowerCase())
  );
  
  console.log(`Trouvé ${results.length} équipements:`);
  results.forEach(eq => {
    console.log(`  - ${eq.name} (${eq.type})`);
  });
  
  return results;
}

// Utilisation
searchEquipments('lumière');
searchEquipments('salon');
```

#### 5.5 Création d'un contrôleur vocal

```javascript
// Nécessite la Web Speech API (navigateurs supportés)

class VoiceController {
  constructor() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    this.recognition = new SpeechRecognition();
    this.recognition.lang = 'fr-FR';
    this.recognition.onresult = (event) => this.handleVoiceInput(event);
  }
  
  start() {
    this.recognition.start();
    console.log('Écoute...');
  }
  
  async handleVoiceInput(event) {
    const transcript = event.results[0][0].transcript;
    console.log('Vous avez dit:', transcript);
    
    // Envoyer à l'IA (si intégré)
    // await this.sendToAI(transcript);
  }
}

// Utilisation
const voiceController = new VoiceController();
document.getElementById('btn-voice').addEventListener('click', () => {
  voiceController.start();
});
```

### 6. Intégration avec d'autres plugins

#### 6.1 Avec des scénarios

```javascript
// Depuis un dashboard custom

async function runScenario() {
  // Récupérer les équipements
  const data = await aiAPI.getAllEquipments();
  
  // Créer une série de commandes
  for (const eq of data.equipments) {
    if (eq.type === 'Lumière') {
      // Éteindre toutes les lumières
      for (const cmd of eq.commands) {
        if (cmd.name === 'Off') {
          await aiAPI.executeCommand(cmd.id);
          await new Promise(r => setTimeout(r, 500));  // Attendre 500ms
        }
      }
    }
  }
  
  alert('Scénario "Tout éteindre" exécuté!');
}
```

#### 6.2 Avec un plugin de traçage

```javascript
// Tracer chaque commande exécutée

class CommandLogger {
  async executeWithLog(commandId) {
    const timestamp = new Date().toISOString();
    console.log(`[${timestamp}] Exécution commande ${commandId}`);
    
    const result = await aiAPI.executeCommand(commandId);
    
    console.log(`[${timestamp}] Résultat:`, result);
    
    // Sauvegarder dans le localStorage
    const logs = JSON.parse(localStorage.getItem('cmd-logs') || '[]');
    logs.push({
      timestamp,
      commandId,
      result
    });
    localStorage.setItem('cmd-logs', JSON.stringify(logs));
    
    return result;
  }
}

const logger = new CommandLogger();
```

### 7. Performance et optimisations

#### 7.1 Cache des résultats

```javascript
class CachedAIAPI {
  constructor(cacheTime = 30000) {  // 30 secondes
    this.cache = {};
    this.cacheTime = cacheTime;
  }
  
  async getAllEquipments(useCache = true) {
    if (useCache && this.cache.equipments) {
      const age = Date.now() - this.cache.equipments.timestamp;
      if (age < this.cacheTime) {
        console.log('Cache utilisé');
        return this.cache.equipments.data;
      }
    }
    
    const data = await new AIConnectorAPI().getAllEquipments();
    
    this.cache.equipments = {
      data,
      timestamp: Date.now()
    };
    
    return data;
  }
}

const cachedAPI = new CachedAIAPI(60000);  // Cache 60 secondes
```

#### 7.2 Batching de requêtes

```javascript
class BatchedAIAPI {
  constructor() {
    this.queue = [];
    this.processing = false;
  }
  
  addCommand(commandId, value) {
    return new Promise((resolve, reject) => {
      this.queue.push({ commandId, value, resolve, reject });
      this.processBatch();
    });
  }
  
  async processBatch() {
    if (this.processing || this.queue.length === 0) return;
    
    this.processing = true;
    
    // Attendre 100ms pour accumuler les commandes
    await new Promise(r => setTimeout(r, 100));
    
    const batch = this.queue.splice(0, 5);  // Traiter 5 à la fois
    
    for (const item of batch) {
      try {
        const result = await new AIConnectorAPI()
          .executeCommand(item.commandId, item.value);
        item.resolve(result);
      } catch (error) {
        item.reject(error);
      }
    }
    
    this.processing = false;
    this.processBatch();  // Continuer avec la suite
  }
}

const batchedAPI = new BatchedAIAPI();
```

### 8. Sécurité

#### 8.1 Validation des entrées

```javascript
function validateCommandId(id) {
  if (!Number.isInteger(id) || id <= 0) {
    throw new Error('ID invalide');
  }
  return id;
}

function validateValue(value) {
  // Selon votre besoin
  if (typeof value !== 'string' && typeof value !== 'number') {
    throw new Error('Valeur invalide');
  }
  return value;
}

async function safeExecuteCommand(commandId, value) {
  try {
    const validId = validateCommandId(commandId);
    const validValue = value ? validateValue(value) : null;
    
    return await aiAPI.executeCommand(validId, validValue);
  } catch (error) {
    console.error('Entrée invalide:', error);
    return { success: false, message: error.message };
  }
}
```

#### 8.2 Gestion des authentifications

```javascript
class SecureAIAPI extends AIConnectorAPI {
  constructor(token) {
    super();
    this.token = token;
  }
  
  async _makeRequest(endpoint, options = {}) {
    options.headers = options.headers || {};
    options.headers['Authorization'] = `Bearer ${this.token}`;
    
    return super._makeRequest(endpoint, options);
  }
}

// Utilisation avec token
const secureAPI = new SecureAIAPI('votre-token-ici');
```

### 9. Debugging

#### 9.1 Logs détaillés

```javascript
class DebugAIAPI extends AIConnectorAPI {
  async getAllEquipments() {
    console.log('>>> Appel getAllEquipments()');
    const start = performance.now();
    
    try {
      const result = await super.getAllEquipments();
      const elapsed = performance.now() - start;
      
      console.log('<<< Réponse reçue en', elapsed, 'ms');
      console.log('Équipements:', result);
      
      return result;
    } catch (error) {
      console.error('!!! Erreur:', error);
      throw error;
    }
  }
}

const debugAPI = new DebugAIAPI();
```

---

**Prochaines étapes:**
- [Référence API](07_API_REFERENCE.md)
- [Exemples de configuration](08_EXEMPLES.md)
- [FAQ](09_FAQ.md)
