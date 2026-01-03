# 🔧 Outils et tests

## Tests et diagnostics

### 1. Tests rapides

#### 1.1 Test dans l'interface Jeedom

**Niveau:** Débutant | **Temps:** 2 min

1. Allez dans **Plugins → Objet connecté → AI Connector**
2. Sélectionnez votre équipement IA
3. Trouvez la commande **"Demander"**
4. Entrez: `"Quel est ton nom?"`
5. Vérifiez la réponse

**Expected:** L'IA répond quelque chose comme "Je suis un assistant IA..."

#### 1.2 Test simple via scénario

**Niveau:** Débutant | **Temps:** 5 min

**Créer un scénario:**

```
Nom: Test IA Simple
Déclenchement: Manuel

Actions:
1. AI Connector → Demander
   Texte: "Quel est la date d'aujourd'hui?"

2. ATTENDRE 3 secondes

3. LOG
   Message: Réponse: #[Salon][AI Connector][Réponse]#
```

**Expected:** Les logs affichent la réponse de l'IA

#### 1.3 Test avec équipements

**Niveau:** Intermédiaire | **Temps:** 5 min

**Prérequis:** Au moins un équipement (lumière, thermostats, etc.)

```
Scénario: Test IA avec Équipements

Actions:
1. AI Connector → Demander
   Texte: "Liste tous les équipements disponibles"

2. ATTENDRE 3 secondes

3. LOG
   Message: #[Salon][AI Connector][Réponse]#
```

**Expected:** L'IA liste les équipements

### 2. Diagnostics disponibles

#### 2.1 Diagnostic ultra-simple (Recommandé)

**Fichier:** `core/php/diagnostic_ultra_simple.php`
**Niveau:** Débutant | **Temps:** 1 min

**Pour exécuter:**

1. Allez dans **Jeedom → Administration → Plugins → AI Connector**
2. Cliquez sur **Outils**
3. Cliquez sur **Diagnostic**

**Ou directement:**

```
http://your-jeedom-url/plugins/ai_connector/core/php/diagnostic_ultra_simple.php
```

**Vérifie:**
```
✓ Étape 1: Vérifier que Jeedom est accessible
✓ Étape 2: Vérifier que le plugin est installé
✓ Étape 3: Vérifier les équipements IA
✓ Étape 4: Récupérer les équipements Jeedom
✓ Étape 5: Tester les endpoints AJAX
✓ Étape 6: Vérifier la configuration de l'API IA
✓ Étape 7: Tester l'appel IA (Ping)
```

**Output:**
```
=== DIAGNOSTIC ULTRA-SIMPLE ===

Étape 1: Vérifier que Jeedom est accessible
✓ OK - Jeedom répond

Étape 2: Vérifier que le plugin est installé
✓ OK - Plugin installé (ID: 12345)

Étape 3: Vérifier les équipements IA
✓ OK - 1 équipement trouvé: "Mon Assistant IA"

Étape 4: Récupérer les équipements Jeedom
ℹ INFO - 3 équipements disponibles
├── Lumière salon (ID: 1)
├── Thermostat (ID: 2)
└── Volets (ID: 3)

Étape 5: Tester les endpoints AJAX
✓ getAllEquipments: OK (200)
✓ getEquipmentCommands: OK (200)

Étape 6: Vérifier la configuration de l'API IA
✓ Moteur: Gemini
✓ Clé API: Configurée
✓ Modèle: gemini-2.5-flash

Étape 7: Tester l'appel IA (Ping)
✓ OK - IA répond

=== RÉSUMÉ ===
✓ Tous les tests sont passés!
Votre installation fonctionne correctement.
```

#### 2.2 Diagnostic complet

**Fichier:** `core/php/diagnostic.php`
**Niveau:** Intermédiaire | **Temps:** 5 min

Plus détaillé que diagnostic_ultra_simple.

```
http://your-jeedom-url/plugins/ai_connector/core/php/diagnostic.php
```

**Vérifie en plus:**
```
- Informations système détaillées
- Configuration des équipements
- Permissions utilisateurs
- Cache et historique
- Performance
```

#### 2.3 Debug avec traces

**Fichier:** `core/php/debug.php`
**Niveau:** Avancé | **Temps:** 10 min

```
http://your-jeedom-url/plugins/ai_connector/core/php/debug.php
```

**Affiche:**
- Stack traces d'erreurs
- Détails des appels API
- Contenu des variables
- Timing de chaque étape

### 3. Tests d'API

#### 3.1 Test des endpoints AJAX

**Fichier:** `core/php/test_ajax_endpoints.php`

```
http://your-jeedom-url/plugins/ai_connector/core/php/test_ajax_endpoints.php
```

**Teste:**
```
✓ GET getAllEquipments
✓ GET getEquipmentCommands
✓ POST executeCommand
✓ GET getJeedomContext
✓ POST getAllEquipmentsWithCommands
```

#### 3.2 Test API IA simple

**Fichier:** `core/php/test_simple_ai.php`

```
http://your-jeedom-url/plugins/ai_connector/core/php/test_simple_ai.php
```

**Teste:**
- Connexion à l'API IA
- Authentification avec clé API
- Envoi d'une simple requête
- Récupération de la réponse

#### 3.3 Test équipements

**Fichier:** `core/php/test_equipments.php`

```
http://your-jeedom-url/plugins/ai_connector/core/php/test_equipments.php
```

**Teste:**
- Récupération des équipements Jeedom
- Récupération des commandes
- Format JSON
- Validation des données

### 4. Vérifications manuelles

#### 4.1 Vérifier les logs

**Accès:** Jeedom → Administration → Outils → Logs

**Cherchez:**
- `ai_connector` - Logs du plugin
- `error` - Erreurs
- `warning` - Avertissements

**À vérifier:**
```
[2026-01-03 14:23:45] AI Connector: Équipement "Mon Assistant IA" initialisé
[2026-01-03 14:24:10] AI Connector: 3 équipements Jeedom chargés
[2026-01-03 14:24:15] AI Connector: Réponse reçue de l'IA
```

#### 4.2 Vérifier les permissions

```
1. Allez dans: Administration → Sécurité → Utilisateurs
2. Sélectionnez votre utilisateur
3. Vérifiez les permissions:
   ☑ Plugin AI Connector (lecture)
   ☑ Plugin AI Connector (modification)
```

#### 4.3 Vérifier la configuration API

```
1. Allez dans: Plugins → AI Connector → Équipement IA
2. Onglet "Moteur IA":
   ✓ Moteur sélectionné
   ✓ Clé API non-vide
   ✓ Modèle défini
3. Onglet "Paramètres IA":
   ✓ "Inclure les équipements" coché
   ✓ Prompt système présent
```

### 5. Dépannage via tests

#### 5.1 "Aucun équipement détecté"

```
1. Exécutez: diagnostic_ultra_simple.php
2. Regardez l'Étape 4
3. Si "0 équipements":
   → Créez un équipement dans Jeedom
   → Vérifiez qu'il n'a pas "Ne pas exposer à l'IA"
4. Redémarrez le daemon
5. Réessayez
```

#### 5.2 "L'API IA ne répond pas"

```
1. Exécutez: test_simple_ai.php
2. Vérifiez:
   ✓ Clé API correcte
   ✓ Moteur disponible (pas en panne)
   ✓ Internet accessible
   ✓ Pas de proxy bloquant
3. Essayez un autre moteur (Gemini → OpenAI)
```

#### 5.3 "Les commandes ne s'exécutent pas"

```
1. Exécutez: test_equipments.php
2. Vérifiez que les commandes existent
3. Exécutez: test_ajax_endpoints.php
4. Regardez l'erreur retournée
5. Vérifiez les permissions Jeedom
```

### 6. Mode debug avancé

#### 6.1 Activer le logging détaillé

**Dans** `core/class/ai_connector.class.php`:

```php
// Ligne ~50
private static $debug = true;  // Mettre à true

// Puis partout:
if (self::$debug) {
    log::add('ai_connector', 'debug', 'Message détaillé');
}
```

#### 6.2 Ajouter des points d'arrêt

Si vous avez XDebug installé:

```
1. Configurez votre IDE (PhpStorm, VS Code)
2. Ajoutez des breakpoints
3. Exécutez le test via navigateur
4. L'IDE capture l'exécution
```

### 7. Tests de performance

#### 7.1 Temps de réponse

```
Script: core/php/diagnostic_ultra_simple.php

Affiche:
- Temps total: 1.234s
- Étape 1: 0.012s
- Étape 2: 0.008s
- ...
- Appel IA: 0.890s ← La plus lente (normale)
```

#### 7.2 Optimization

Si trop lent:
```
1. [ ] Augmentez le timeout (30s → 60s)
2. [ ] Changez de moteur IA (plus rapide)
3. [ ] Vérifiez votre connexion internet
4. [ ] Vérifiez la charge serveur Jeedom
```

### 8. Checklist complète

- [ ] Diagnostic ultra-simple: Tous verts ✓
- [ ] Test AJAX endpoints: Tous OK ✓
- [ ] Test API IA: Répond
- [ ] Test équipements: Liste complète
- [ ] Logs: Pas d'erreurs
- [ ] Permissions: Correctes
- [ ] Configuration API: Valide

---

**Prochaines étapes:**
- [Dépannage complet](05_DEBOGAGE.md)
- [API JavaScript](06_API_JAVASCRIPT.md)
