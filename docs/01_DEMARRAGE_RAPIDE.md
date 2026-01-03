# 🚀 Démarrage rapide

## ⏱️ 30 minutes pour mettre en place l'AI Connector

### Étape 1: Installation (5 min)

1. Allez dans **Plugins → Plugins de la communauté → Installer depuis GitHub**
2. Recherchez **AI Connector** ou copier l'URL: `https://github.com/Yoann81/Jeedom-AI`
3. Cliquez sur **Installer**
4. Attendez la fin de l'installation

### Étape 2: Configuration de base (15 min)

Allez dans **Plugins → Objet connecté → AI Connector**

#### A. Créer un équipement IA

1. Cliquez sur **Ajouter** (bouton bleu)
2. Donnez un nom: ex. "Mon Assistant IA"
3. Cochez **Actif**
4. Cliquez sur **Sauvegarder**

#### B. Configurer l'API IA

Onglet **Moteur IA**:

**Option 1: Gemini (gratuit)**
- Moteur: **Gemini 2.5 Flash**
- Clé API: [Obtenir sur Google AI Studio](https://aistudio.google.com/app/apikey)
- Modèle: Laissez par défaut

**Option 2: OpenAI**
- Moteur: **OpenAI (gpt-4o-mini)**
- Clé API: [Obtenir sur OpenAI](https://platform.openai.com/api-keys)
- Modèle: Laissez par défaut

**Option 3: Mistral**
- Moteur: **Mistral (mistral-small-latest)**
- Clé API: [Obtenir sur Mistral](https://console.mistral.ai/)
- Modèle: Laissez par défaut

#### C. Configurer l'accès aux équipements

Onglet **Paramètres IA**:

- ✅ Cochez **Inclure les équipements Jeedom**
- Ceci permettra à l'IA de voir et contrôler vos équipements

#### D. Sauvegarder

1. Cliquez sur **Sauvegarder**
2. Attendez quelques secondes

### Étape 3: Test simple (10 min)

#### Option A: Interface web

1. Allez dans **Objet → Votre maison → Pièce**
2. Trouvez l'équipement AI
3. Regardez les commandes créées:
   - **Demander**: Pour poser une question à l'IA
   - **Réponse**: Affiche la réponse

#### Option B: Scénario

Créez un nouveau scénario:

```
ÉVÉNEMENT: Manuel
CONDITION: Aucune
ACTION:
  - AI Connector → Demander
  - Contenu: "Quel est la température actuelle?"
  - Puis ATTENDRE 3 secondes
  - LOG: #[Pièce][AI Connector][Réponse]#
```

#### Option C: Tests techniques

Voir [Outils et tests](04_OUTILS_TESTS.md#tests-rapides)

### 🎯 Vous avez réussi! 🎉

Votre IA est maintenant active. Prochaines étapes:

1. **Créer des équipements à contrôler** (thermostats, lumières, etc.)
2. **Tester les commandes** via l'IA
3. **Lire la configuration complète** pour les options avancées
4. **Automiser** avec des scénarios

## ⚠️ Problèmes courants

### "L'IA ne répond pas"
→ [Voir Dépannage](05_DEBOGAGE.md#lia-ne-répond-pas)

### "Je n'ai pas d'équipements disponibles"
→ [Voir Dépannage](05_DEBOGAGE.md#aucun-équipement-détecté)

### "Clé API invalide"
→ Vérifiez que vous avez copié la clé correctement
→ Assurez-vous que la clé n'est pas expirée

## 📚 Prochaines lectures

- [Configuration complète](02_CONFIGURATION.md)
- [Guide équipements IA](03_EQUIPEMENTS_IA.md)
- [Exemples de configuration](08_EXEMPLES.md)

---

**Besoin d'aide?** Allez à [FAQ](09_FAQ.md)
