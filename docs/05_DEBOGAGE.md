# 🐛 Dépannage et debugging

## Erreurs courantes et solutions

### 1. L'IA ne répond pas

#### Symptômes
```
- Vous posez une question
- Rien ne se passe
- Pas de réponse après 30 secondes
- Pas d'erreur dans les logs
```

#### Solutions étape par étape

**Étape 1: Vérifier la configuration API**

```
Plugins → AI Connector → [Votre IA] → Moteur IA
Vérifiez:
✓ Moteur sélectionné (Gemini/OpenAI/Mistral)
✓ Clé API non-vide
✓ Clé API correcte (copiée exactement)
✓ Pas d'espaces au début/fin
```

**Étape 2: Vérifier la connexion internet**

```
1. Ouvrez un terminal/PowerShell
2. Exécutez:
   - Pour Gemini: ping generativelanguage.googleapis.com
   - Pour OpenAI: ping api.openai.com
   - Pour Mistral: ping api.mistral.ai
3. Vous devez recevoir une réponse (pas "unreachable")
```

**Étape 3: Tester l'API IA directement**

```
1. Allez dans: core/php/test_simple_ai.php
2. Regardez le résultat:
   - Si "✓ OK": L'API fonctionne
   - Si "✗ Error": Problème API
```

**Étape 4: Vérifier le timeout**

```
1. Plugins → AI Connector → Paramètres IA
2. Augmentez "Délai de timeout":
   30s → 60s (ou plus)
3. Sauvegardez et réessayez
```

**Étape 5: Vérifier les logs**

```
1. Administration → Outils → Logs
2. Sélectionnez le log: ai_connector
3. Cherchez les erreurs (messages rouges)
4. Si vous voyez une erreur API:
   - Notez le code d'erreur
   - Allez à la section "Erreurs API" ci-dessous
```

**Étape 6: Vérifier les permissions**

```
1. Administration → Sécurité → Utilisateurs
2. Sélectionnez votre utilisateur
3. Vérifiez:
   ✓ Plugin AI Connector (lecture)
   ✓ Plugin AI Connector (modification)
   ✓ Accès en lecture aux équipements IA
```

**Étape 7: Redémarrer le daemon**

```
1. Administration → Plugins
2. Trouvez "AI Connector"
3. Cliquez sur l'icône "Redémarrer"
4. Attendez 10 secondes
5. Réessayez
```

**Étape 8: Derniers recours**

```
1. Videz le cache:
   - Administration → Système → Cache
   - Cliquez "Vider le cache"

2. Réinstallez le plugin:
   - Plugins → Plugins de la communauté
   - Trouvez AI Connector
   - Cliquez "Supprimer"
   - Cliquez "Réinstaller"

3. Vérifiez les crédits API:
   - Gemini: https://aistudio.google.com/app/apikey
   - OpenAI: https://platform.openai.com/account/billing
   - Mistral: https://console.mistral.ai
```

### 2. Aucun équipement détecté

#### Symptômes
```
- L'IA répond "Aucun équipement disponible"
- Mais vous avez des équipements dans Jeedom
- Ou: "Inclure les équipements" est coché mais ne change rien
```

#### Causes courantes et solutions

**Cause 1: "Inclure les équipements" non coché**

```
Solution:
1. Plugins → AI Connector → [Votre IA]
2. Onglet "Paramètres IA"
3. ☑ Cochez "Inclure les équipements Jeedom"
4. Sauvegardez
```

**Cause 2: Aucun équipement créé dans Jeedom**

```
Solution:
1. Créez un équipement:
   - Plugins → Lumière (ou autre)
   - Cliquez "Ajouter"
   - Donnez un nom
   - Sauvegardez

2. Puis retestez l'IA
```

**Cause 3: Équipement marqué "Ne pas exposer à l'IA"**

```
Solution:
1. Pour chaque équipement à exposer:
   - Allez dans l'équipement
   - Décochez "Ne pas exposer à l'IA"
   - Sauvegardez
```

**Cause 4: Cache ou daemon pas mis à jour**

```
Solution:
1. Redémarrez le daemon:
   - Administration → Plugins
   - AI Connector → Redémarrer

2. Videz le cache:
   - Administration → Système → Cache
   - Vider le cache

3. Attendez 10 secondes
4. Réessayez
```

**Cause 5: Problème de permissions**

```
Solution:
1. Vérifiez les permissions utilisateur:
   - Administration → Sécurité → Utilisateurs
   - ✓ Lecture/modification AI Connector

2. Vérifiez les permissions d'objet:
   - Chaque équipement doit être assigné à un objet
   - Cet objet doit être visible pour l'utilisateur
```

### 3. Erreurs courantes de l'API IA

#### Erreur 401 - Unauthorized

```
Message: "Invalid API key" ou "Unauthorized"

Cause: Clé API incorrecte ou expirée

Solution:
1. Vérifiez la clé API:
   - Copier/coller depuis la console API
   - Pas d'espaces au début/fin
   
2. Générez une nouvelle clé:
   - Gemini: https://aistudio.google.com/app/apikey
   - OpenAI: https://platform.openai.com/api-keys
   - Mistral: https://console.mistral.ai/api-keys
   
3. Collez la nouvelle clé dans la configuration
4. Sauvegardez et testez
```

#### Erreur 429 - Rate Limited

```
Message: "Too many requests" ou "Rate limit exceeded"

Cause: Trop de requêtes envoyées à l'API (quota atteint)

Solution:
1. Attendez quelques minutes avant de réessayer
2. Pour OpenAI/Mistral: mettez à niveau votre plan payant
3. Pour Gemini: c'est gratuit mais limité (50k requêtes/jour)
4. Réduisez la fréquence des appels IA

A long terme:
- Utilisez un cache pour les requêtes identiques
- Groupez les questions
- Réduisez la détail du prompt système
```

#### Erreur 500 - Server Error

```
Message: "Internal server error"

Cause: Problème chez le fournisseur API (Gemini/OpenAI/etc.)

Solution:
1. Attendez quelques minutes
2. L'erreur devrait disparaître
3. Vérifiez le statut du service:
   - Gemini: https://status.cloud.google.com
   - OpenAI: https://status.openai.com
   - Mistral: https://status.mistral.ai
4. Continuez d'utiliser le service normalement
```

#### Erreur 403 - Forbidden

```
Message: "Access denied" ou "Forbidden"

Cause: Clé API valide mais pas accès à ce modèle/service

Solution:
1. Vérifiez votre plan d'accès
2. Pour OpenAI: le modèle peut être en bêta
3. Changez de moteur/modèle
4. Contactez le support du fournisseur
```

#### Erreur de timeout

```
Message: "Connection timeout" ou "Request timeout"

Cause: L'API met trop longtemps à répondre

Solution:
1. Augmentez le délai de timeout:
   - Paramètres IA → Délai de timeout
   - 30s → 60s (ou plus)

2. Vérifiez votre connexion internet
3. Réessayez avec une requête plus simple
4. Changez de moteur (Gemini est généralement plus rapide)
```

### 4. Erreur getType() non trouvé

#### Symptômes
```
Log error: "Call to undefined method getType()"
```

#### Cause
```
Une commande Jeedom n'a pas la méthode getType()
Cela ne devrait pas arriver (c'est un bug)
```

#### Solution
```
Le code a été corrigé pour vérifier l'existence de la méthode:

if (method_exists($cmd, 'getType')) {
    $type = $cmd->getType();
} else {
    $type = 'unknown';
}

Mettez à jour le plugin à la dernière version.
```

### 5. Boucles infinies (anti-loop)

#### Symptômes
```
- La même réponse se répète
- L'IA pose la même question
- Commandes exécutées en boucle
```

#### Cause
```
Système anti-boucle déclenché

Le plugin détecte:
- Même réponse dans les 5 dernières secondes
- Même commande exécutée répétées fois
```

#### Solution
```
1. Attendez 5 secondes avant de réessayer

2. Modifiez le prompt système:
   - Ajoutez une instruction explicite
   - Ex: "Si la réponse ne change pas, arrête"

3. Testez avec un prompt différent:
   - Soyez plus explicite dans votre demande
   - Évitez les demandes ambiguës

4. Augmentez le timeout:
   - Paramètres IA → Délai de timeout
   - Donnez plus de temps à l'IA de réfléchir
```

### 6. Les commandes Jeedom ne s'exécutent pas

#### Symptômes
```
- L'IA dit "Lumière allumée" mais elle ne s'allume pas
- Pas d'erreur visible
- Les logs ne montrent rien
```

#### Diagnostic

```
1. Exécutez: core/php/test_ajax_endpoints.php
   Regardez "executeCommand"
   
2. Testez la commande manuellement:
   - Allez dans l'équipement
   - Cliquez sur la commande
   - Vérifiez qu'elle fonctionne
   
3. Vérifiez les permissions:
   - L'utilisateur peut-il exécuter cette commande?
```

#### Solutions possibles

**Solution 1: Commande n'existe pas**

```
Le plugin génère le format: [EXEC_COMMAND: id]
Si la commande n'existe pas → pas d'exécution

Vérification:
1. Allez dans l'équipement concerné
2. Vérifiez la présence des commandes action
3. Notez leurs IDs
4. Vérifiez que l'IA utilise les bons IDs
```

**Solution 2: Permissions insuffisantes**

```
L'utilisateur n'a pas le droit d'exécuter la commande

Vérification:
1. Administration → Sécurité → Utilisateurs
2. Sélectionnez l'utilisateur
3. Vérifiez:
   ✓ Accès à l'équipement (lecture + modification)
   ✓ Accès aux objets associés
```

**Solution 3: Paramètre invalide**

```
La commande a besoin d'un paramètre mais l'IA n'en fournit pas

Exemple:
"[EXEC_COMMAND: 20]"  ← Pas de paramètre
Mais la commande attend:
"[EXEC_COMMAND: 20 value=22]"  ← Avec paramètre

Solution:
- Modifiez le prompt système
- Donnez des exemples clairs à l'IA
- Testez avec test_equipments.php pour voir les paramètres attendus
```

**Solution 4: Délai trop court**

```
L'IA envoie les commandes mais le daemon n'a pas le temps d'exécuter

Solution:
1. Augmentez le délai d'attente dans vos scénarios:
   ATTENDRE 1 seconde → ATTENDRE 3 secondes

2. Vérifiez la charge du serveur Jeedom
```

### 7. L'IA génère des commandes incorrectes

#### Symptômes
```
- L'IA dit "Je vais allumer la lumière"
- Mais elle génère: [EXEC_COMMAND: 999]
- Ou un mauvais équipement est commandé
```

#### Cause
```
L'IA confond les IDs des commandes
```

#### Solutions

**Solution 1: Améliorer le prompt**

```
Ajoutez des instructions claires au prompt système:

"Quand tu dois exécuter une action:
1. Trouve l'équipement concerné par son NOM
2. Trouve la commande appropriée
3. Utilise le format exact: [EXEC_COMMAND: id value=x]
4. Vérifie deux fois avant d'exécuter"
```

**Solution 2: Fournir des exemples**

```
Dans le prompt système, ajoutez:

"EXEMPLES:
- Pour allumer la lumière du salon (ID 10): [EXEC_COMMAND: 10]
- Pour mettre le thermostat à 22°C (ID 20): [EXEC_COMMAND: 20 value=22]"
```

**Solution 3: Simplifier le contexte**

```
Si l'IA a trop d'équipements:
- Elle peut se tromper

Solution:
1. Groupez les équipements par pièce
2. N'exposez que les équipements nécessaires
3. Cachéz les équipements rarement utilisés
   (Équipement → "Ne pas exposer à l'IA")
```

### 8. Performance lente

#### Symptômes
```
- Les réponses prennent plus de 30 secondes
- Jeedom ralentit quand l'IA répond
- Autres plugins affectés
```

#### Causes et solutions

**Cause 1: API IA lente**

```
Solutions:
1. Changez de moteur (Gemini généralement plus rapide)
2. Réduisez la longueur du prompt système
3. Réduisez la longueur du contexte (moins d'équipements exposés)
```

**Cause 2: Trop d'équipements**

```
Si vous exposez 100+ équipements:
- L'IA prend plus de temps
- Le contexte est énorme

Solution:
1. Cachez les équipements inutiles:
   Équipement → "Ne pas exposer à l'IA"
2. Groupez par pièce
3. Créez plusieurs IA (une par pièce)
```

**Cause 3: Serveur surchargé**

```
Solutions:
1. Vérifiez la charge du serveur:
   Administration → Système → Information
   
2. Arrêtez les plugins inutilisés
3. Augmentez les ressources serveur (RAM, CPU)
```

### 9. Autres erreurs

#### Erreur "Plugin non activé"

```
Message: "Plugin disabled" ou "Plugin not active"

Solution:
1. Plugins → Plugins de la communauté
2. Trouvez AI Connector
3. Cliquez sur l'icône pour l'activer
4. Attendez quelques secondes
5. Réessayez
```

#### Erreur "Équipement non trouvé"

```
Message: "Equipment not found"

Solution:
1. Vérifiez que l'équipement existe:
   - Allez dans l'équipement concerné
   - Vérifiez son ID
   
2. Vérifiez les permissions:
   - L'utilisateur peut-il voir cet équipement?
```

#### Erreur "Commande non trouvée"

```
Message: "Command not found"

Solution:
1. Exécutez: test_equipments.php
2. Cherchez la commande par nom
3. Notez son ID
4. Vérifiez que l'IA utilise le bon ID
```

### 10. Checklist dépannage

- [ ] Configuration API vérifiée
- [ ] Clé API correcte
- [ ] Connexion internet OK
- [ ] Permissions Jeedom OK
- [ ] "Inclure les équipements" coché
- [ ] Au moins 1 équipement créé
- [ ] Daemon redémarré
- [ ] Cache vidé
- [ ] Logs consultés
- [ ] Diagnostics lancés

### 11. Contacter le support

Si vous avez encore des problèmes:

1. **Collectez les informations:**
   - Résultats du diagnostic_ultra_simple.php
   - Extraits des logs (dernières 50 lignes)
   - Configuration exacte de l'IA
   - Étapes pour reproduire le problème

2. **Créez une issue GitHub:**
   - https://github.com/Yoann81/Jeedom-AI/issues
   - Décrivez le problème clairement
   - Joignez les informations collectées

3. **Forum Jeedom:**
   - https://community.jeedom.com
   - Allez dans la section Plugins
   - Cherchez "AI Connector"

---

**Prochaines étapes:**
- [API JavaScript](06_API_JAVASCRIPT.md)
- [FAQ](09_FAQ.md)
