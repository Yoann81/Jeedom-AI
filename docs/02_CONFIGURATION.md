# 🎛️ Configuration complète

## Configuration détaillée de l'AI Connector

### 1. Onglet "Moteur IA"

#### 1.1 Sélection du moteur

```
Moteur: [Dropdown]
├── Gemini 2.5 Flash          ← Gratuit, excellent
├── OpenAI (gpt-4o-mini)      ← Payant, performant
└── Mistral (mistral-small)   ← Payant, français-friendly
```

**Recommandation selon votre cas:**

| Cas d'usage | Moteur | Raison |
|-----------|--------|--------|
| Test / Découverte | Gemini | Gratuit, crédits inclus |
| Production généraliste | OpenAI | Fiable, support excellent |
| Usage français | Mistral | Conçu pour le français |
| Performance maximale | Gemini | Le plus rapide |
| Budget minimal | Gemini | 50000 requêtes gratuites |

#### 1.2 Clé API

Chaque moteur nécessite une clé:

**Gemini:**
```
1. Allez sur: https://aistudio.google.com/app/apikey
2. Cliquez "Create API key"
3. Créez dans "default project"
4. Copiez la clé
5. Collez dans "Clé API"
```

**OpenAI:**
```
1. Créez compte: https://openai.com
2. Allez: https://platform.openai.com/api-keys
3. Cliquez "Create new secret key"
4. Copiez la clé
5. Collez dans "Clé API"
```

**Mistral:**
```
1. Créez compte: https://console.mistral.ai
2. Allez: https://console.mistral.ai/api-keys
3. Cliquez "Create API key"
4. Copiez la clé
5. Collez dans "Clé API"
```

#### 1.3 Modèle

Laissez les valeurs par défaut sauf besoin spécifique:

```
Modèle: [Texte]
Gemini:      "gemini-2.5-flash" (par défaut)
OpenAI:      "gpt-4o-mini" (par défaut)
Mistral:     "mistral-small-latest" (par défaut)
```

### 2. Onglet "Paramètres IA"

#### 2.1 Prompt système

Le **Prompt système** définit la personnalité et les règles de l'IA.

**Exemple basique:**
```
Tu es un assistant domotique intelligent.
Tu peux contrôler les équipements de la maison.
Sois courtois et utile.
```

**Exemple avancé:**
```
Tu es "Maison Intelligente" (Assistant Domotique Premium).

OBJECTIF: Gérer intelligemment les équipements de la maison.

RÈGLES:
1. Sois courtois et utile
2. Propose des actions intelligentes
3. Demande confirmation pour actions critiques
4. Rapporte toujours les erreurs
5. Réponds en français

CAPACITÉS:
- Contrôler lumières, chauffage, portes
- Consulter capteurs et mesures
- Créer des automatisations
- Exécuter des scénarios

RÉPONSES: Claires et concises.
```

#### 2.2 Inclure les équipements Jeedom

**✅ À cocher** pour activer la fonction principale!

```
☑ Inclure les équipements Jeedom
```

Cette option permet à l'IA de:
- Voir tous vos équipements
- Consulter leurs états
- Exécuter leurs commandes
- Créer des automations

#### 2.3 Délai de timeout

Temps maximal d'attente pour une réponse IA:

```
Délai de timeout: 30 (secondes)
```

**Valeurs recommandées:**
- 15-20s: Pour requêtes simples
- 30s: Standard (par défaut)
- 45-60s: Pour requêtes complexes
- 120s+: Pour modèles lents (Mistral)

#### 2.4 Anti-boucle

Système de prévention contre les boucles infinies:

```
Détection par: Hash MD5 + Timeout 5s
```

**Comment ça fonctionne:**
1. L'IA pose une question
2. La réponse est hashée (MD5)
3. Si même réponse dans 5s → Boucle détectée
4. La commande est rejetée

### 3. Onglet "STT (Reconnaissance vocale)"

Configuration de la reconnaissance vocale.

#### 3.1 Moteur STT

```
Moteur STT: [Dropdown]
├── Aucun                    ← Désactivé
├── Google Speech-to-Text    ← Gratuit, inclus
└── Autre (custom)           ← Personnalisé
```

#### 3.2 Activation STT

```
☑ Activer STT
```

Une fois activé, vous pouvez:
- Parler à l'IA au lieu de taper
- Utiliser les commandes vocales
- Intégrer à des appareils vocaux

### 4. Onglet "TTS (Synthèse vocale)"

Configuration de la synthèse vocale (IA qui vous parle).

#### 4.1 Moteur TTS

```
Moteur TTS: [Dropdown]
├── Aucun                    ← Désactivé
├── Google Text-to-Speech    ← Gratuit, inclus
├── Microsoft Azure          ← Payant, haute qualité
└── Autre (custom)           ← Personnalisé
```

#### 4.2 Activation TTS

```
☑ Activer TTS
```

Une fois activé, l'IA vous répondra en parlant.

#### 4.3 Langue

```
Langue: [Dropdown]
├── Français
├── Anglais
├── Espagnol
└── Autres...
```

### 5. Onglet "Équipements disponibles"

Liste des équipements que l'IA peut voir et contrôler.

#### 5.1 Voir les équipements

```
Équipements disponibles (lecture seule):
- Thermostats (2)
- Lumières (5)
- Portes (1)
- Capteurs (8)
- ...
```

#### 5.2 Gérer les permissions

Si un équipement ne doit pas être visible:
```
1. Allez dans l'équipement concerné
2. Cochez "Ne pas exposer à l'IA"
3. Sauvegardez
```

### 6. Configuration des commandes

Les commandes créées automatiquement:

| Commande | Type | Usage |
|----------|------|-------|
| Demander | Action | Poser une question à l'IA |
| Réponse | Info | Reçoit la réponse |
| Erreur | Info | Reçoit les erreurs |

**Personnaliser une commande:**

1. Allez dans **Objet → Équipement IA → Commandes**
2. Cliquez sur la commande
3. Modifiez:
   - Nom
   - Icône
   - Historique (à cocher pour tracer)
   - Affichage

### 7. Configuration avancée

#### 7.1 Cache des réponses

```
Activer le cache: ☑
Durée: 300 (secondes)
```

Le cache stocke temporairement les réponses identiques.

#### 7.2 Logging

```
Niveau de log: [Dropdown]
├── Aucun
├── Erreurs seulement
├── Warnings + Erreurs
└── Tous les logs (Debug)
```

Pour déboguer, utilisez "Tous les logs".

#### 7.3 Authentification API

Si derrière un proxy/authentification:

```
Proxy: [Texte]
Username: [Texte]
Password: [Mot de passe]
```

## 💾 Profils de configuration

### Profil 1: Assistant simple

**Idéal pour:** Démarrage, tests

```
Moteur: Gemini
Prompt: "Tu es un assistant maison utile"
Inclure équipements: ✓
STT: ✗
TTS: ✗
```

### Profil 2: Assistant complet

**Idéal pour:** Usage quotidien

```
Moteur: OpenAI
Prompt: [Voir section 2.1]
Inclure équipements: ✓
STT: ✓ (Google)
TTS: ✓ (Google)
```

### Profil 3: Assistant premium

**Idéal pour:** Production, haute disponibilité

```
Moteur: OpenAI
Prompt: [Personnalisé selon besoin]
Inclure équipements: ✓
STT: ✓ (Moteur haut-de-gamme)
TTS: ✓ (Azure ou équivalent)
Timeout: 60s
Cache: Activé
```

## 🔒 Sécurité

### Points clés

1. **Clé API**: Ne partagez JAMAIS votre clé API
2. **Équipements critiques**: Exposez-les de façon réfléchie
3. **Authentification**: Utilisez les identifiants Jeedom
4. **Logs**: Désactivez les logs détaillés en production

### Permissions

Seuls les administrateurs Jeedom peuvent:
- Configurer l'AI Connector
- Modifier les équipements visibles
- Créer/supprimer des équipements IA

## ✅ Checklist de configuration

- [ ] Moteur IA sélectionné
- [ ] Clé API configurée et testée
- [ ] Prompt système défini
- [ ] Inclure équipements ✓
- [ ] Timeout configuré (30s standard)
- [ ] STT activé (optionnel)
- [ ] TTS activé (optionnel)
- [ ] Équipement sauvegardé
- [ ] Test basique effectué
- [ ] Logs vérifiés

---

**Prochaine étape:** [Outils et tests](04_OUTILS_TESTS.md)
