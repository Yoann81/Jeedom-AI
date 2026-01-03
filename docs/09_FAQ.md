# 📚 FAQ - Questions fréquentes

## Installation et configuration

### Q1: Où télécharger le plugin?

**R:** Le plugin est disponible via:

1. **Directement dans Jeedom:**
   - Plugins → Plugins de la communauté
   - Cherchez "AI Connector"
   - Cliquez "Installer"

2. **Via GitHub:**
   - https://github.com/Yoann81/Jeedom-AI
   - Clonez le repository
   - Placez dans plugins/

---

### Q2: Quelle clé API utiliser?

**R:** Trois options principales:

| Moteur | Coût | Inscription | Modèle |
|--------|------|-------------|--------|
| **Gemini** | Gratuit (50k req) | Rapide | gemini-2.5-flash |
| **OpenAI** | Payant (~$0.15/1M tokens) | Moins de 2 min | gpt-4o-mini |
| **Mistral** | Payant (~$0.14/1M tokens) | Moins de 5 min | mistral-small-latest |

**Recommandation:** Commencez avec **Gemini** (gratuit, complet).

---

### Q3: Ma clé API ne fonctionne pas

**R:** Vérifiez:

1. ✓ Clé copiée exactement (pas d'espaces)
2. ✓ Clé pas expirée (regénérez si doute)
3. ✓ Clé correspond au moteur sélectionné
4. ✓ Connexion internet OK (testez `ping api.openai.com`)
5. ✓ Clé pas désactivée sur la console API

**Si toujours pas:** Regénérez une nouvelle clé.

---

### Q4: Comment changer de moteur IA?

**R:** Simple:

1. Allez dans **Plugins → AI Connector → [Votre IA]**
2. Onglet **"Moteur IA"**
3. Sélectionnez un autre moteur
4. Entrez la nouvelle clé API
5. Cliquez **"Sauvegarder"**

---

## Équipements et commandes

### Q5: Comment ajouter mes équipements à l'IA?

**R:** Automatique! Si vous avez:
1. ✓ Créé des équipements dans Jeedom
2. ✓ Coché "Inclure les équipements" dans l'IA
3. ✓ Redémarré le daemon

Alors l'IA les voit automatiquement.

---

### Q6: Pourquoi l'IA ne voit pas mes équipements?

**R:** Checklist:

- [ ] "Inclure les équipements" coché?
- [ ] Au moins 1 équipement créé?
- [ ] Équipement pas marqué "Ne pas exposer à l'IA"?
- [ ] Daemon redémarré (cliquez l'icône de redémarrage)?
- [ ] Rechargez la page navigateur (F5)?

**Dernier recours:** Voir [Dépannage](05_DEBOGAGE.md#aucun-équipement-détecté)

---

### Q7: Comment cacher un équipement de l'IA?

**R:**

1. Allez dans l'équipement
2. Cochez **"Ne pas exposer à l'IA"**
3. Sauvegardez
4. Redémarrez le daemon

---

### Q8: Puis-je avoir plusieurs IA?

**R:** Oui! Vous pouvez créer plusieurs équipements IA avec:
- Moteurs différents (Gemini, OpenAI, Mistral)
- Prompts différents
- Équipements exposés différents (ex: IA1 voit les lumières, IA2 voit thermostat)

---

## Utilisation et commandes

### Q9: Comment poser une question à l'IA?

**R:** Plusieurs façons:

**Méthode 1: Interface Jeedom**
- Allez dans l'équipement IA
- Trouvez la commande "Demander"
- Tapez votre question
- Cliquez "Exécuter"

**Méthode 2: Scénario**
```
AI Connector → Demander
Texte: "Quelle est la température?"
```

**Méthode 3: JavaScript**
```javascript
const aiAPI = new AIConnectorAPI();
const result = await aiAPI.processAIRequest("Allume le salon");
```

---

### Q10: Quel format utiliser pour les commandes?

**R:** L'IA utilise automatiquement:

```
[EXEC_COMMAND: <id>]           # Commande simple
[EXEC_COMMAND: <id> value=X]   # Avec paramètre
```

**Exemples:**
```
"[EXEC_COMMAND: 10]" → Allume lumière
"[EXEC_COMMAND: 20 value=22]" → Thermostat 22°C
"[EXEC_COMMAND: 30 level=50]" → Volets à 50%
```

---

### Q11: Comment exécuter plusieurs commandes?

**R:** L'IA peut générer plusieurs `[EXEC_COMMAND]` dans une réponse:

```
L'IA: "Je vais préparer votre chambre pour la nuit"
[EXEC_COMMAND: 12]   # Éteindre lumière salon
[EXEC_COMMAND: 11]   # Éteindre lumière chambre
[EXEC_COMMAND: 14]   # Armer l'alarme
```

Jeedom les exécute toutes séquentiellement.

---

### Q12: La boucle infinie? Comment ça marche?

**R:** Système de protection:

```
- L'IA poste une réponse
- Hash MD5 généré et vérifié
- Si même réponse dans 5 secondes
- → Commande rejetée (boucle détectée)
```

**Pour déboguer:**
- Attendez 5 secondes
- Changez légèrement votre demande
- Modifiez le prompt système

---

## Performance et optimisation

### Q13: L'IA est trop lente

**R:** Causes courantes:

| Problème | Solution |
|----------|----------|
| API lente | Changez moteur (Gemini > Mistral) |
| Trop d'équipements | Cachez les inutilisés |
| Prompt trop long | Raccourcissez-le |
| Connexion internet | Vérifiez votre connexion |

**Testez:** `core/php/diagnostic_ultra_simple.php` affiche les timings.

---

### Q14: Comment améliorer la réactivité?

**R:** Tips d'optimisation:

1. **Cachez les équipements inutilisés**
   - Chaque équipement ralentit l'IA
   - Exposez seulement ceux nécessaires

2. **Raccourcissez le prompt**
   - Soyez concis
   - Éliminez les redondances

3. **Utilisez le cache**
   - Activez-le dans les paramètres
   - Les réponses identiques sont plus rapides

4. **Augmentez le timeout**
   - Plus de temps = moins de timeouts
   - 30s → 60s si problèmes

---

### Q15: Combien d'appels IA puis-je faire?

**R:** Ça dépend:

| Moteur | Limite | Coût |
|--------|--------|------|
| Gemini | 50k req/jour (gratuit) | Gratuit |
| OpenAI | Selon votre quota | ~$0.15 par 1M tokens |
| Mistral | Selon votre quota | ~$0.14 par 1M tokens |

**Pro-tip:** Utilisez le cache et batch les requêtes.

---

## Sécurité

### Q16: Mes données sont-elles sécurisées?

**R:** Oui, avec caveats:

**Sécurisé:**
- ✓ Données restent sur votre Jeedom
- ✓ Communication chiffrée HTTPS
- ✓ Authentification Jeedom requise

**À noter:**
- ⚠️ Requêtes envoyées à Google/OpenAI/Mistral
- ⚠️ Lisez les conditions de chaque service
- ⚠️ Pas de données sensibles dans les prompts

---

### Q17: Comment empêcher l'accès non autorisé?

**R:** Paramètres de sécurité:

1. **Permissions Jeedom:**
   - Administration → Utilisateurs
   - Attribuez droits correctement

2. **Cacher équipements sensibles:**
   - Cochez "Ne pas exposer à l'IA"

3. **Prompt de sécurité:**
   - Ajoutez confirmation pour actions sensibles
   - Ex: "Avant de désarmer l'alarme → demander confirmation"

---

### Q18: Puis-je limiter les équipements visibles par l'IA?

**R:** Oui, deux méthodes:

**Méthode 1: Équipement par équipement**
- Allez dans chaque équipement
- Cochez "Ne pas exposer à l'IA"

**Méthode 2: Niveau utilisateur**
- Administration → Utilisateurs
- Limitez les permissions

---

## Dépannage

### Q19: "L'IA ne répond pas"

**R:** Suivez le guide: [Dépannage - L'IA ne répond pas](05_DEBOGAGE.md#lia-ne-répond-pas)

**Résumé rapide:**
1. Vérifiez clé API
2. Vérifiez internet
3. Augmentez timeout (30s → 60s)
4. Redémarrez le daemon
5. Consultez les logs

---

### Q20: "Aucun équipement détecté"

**R:** Suivez le guide: [Dépannage - Aucun équipement](05_DEBOGAGE.md#aucun-équipement-détecté)

**Résumé rapide:**
1. Cochez "Inclure les équipements"
2. Créez au moins 1 équipement
3. Redémarrez le daemon
4. Exécutez le diagnostic

---

### Q21: Comment déboguer?

**R:** Outils disponibles:

1. **Diagnostic ultra-simple** (recommandé)
   - `core/php/diagnostic_ultra_simple.php`
   - Teste 7 points clés

2. **Logs Jeedom**
   - Administration → Logs
   - Sélectionnez "ai_connector"
   - Cherchez les erreurs (rouge)

3. **Test AJAX**
   - `core/php/test_ajax_endpoints.php`
   - Teste chaque endpoint

4. **Test IA simple**
   - `core/php/test_simple_ai.php`
   - Vérifie connexion API

---

## Avancé

### Q22: Puis-je modifier le code?

**R:** Oui! C'est du code open-source (GPL):

1. **Fichiers importants:**
   - `core/class/ai_connector.class.php` (Logique principale)
   - `core/ajax/ai_connector.ajax.php` (API)
   - `desktop/js/ai_connector.js` (JavaScript)

2. **Bonnes pratiques:**
   - Faites des modifications étapes par étapes
   - Testez après chaque modif
   - Sauvegardez vos modifications
   - Créez des backups avant expérimentation

3. **Contribuer:**
   - GitHub fork & pull request
   - Créez des issues
   - Proposez des améliorations

---

### Q23: Comment créer un plugin custom?

**R:** Basé sur l'API JavaScript:

```javascript
// Votre plugin peut utiliser:
const aiAPI = new AIConnectorAPI();

// Récupérer équipements
const equipments = await aiAPI.getAllEquipments();

// Exécuter une commande
const result = await aiAPI.executeCommand(10);

// Récupérer le contexte
const context = await aiAPI.getJeedomContext();
```

**Voir:** [API JavaScript](06_API_JAVASCRIPT.md)

---

### Q24: Comment intégrer avec d'autres services?

**R:** Deux approches:

**Approche 1: Via Jeedom**
- Utilisez les plugins Jeedom existants
- Créez des scénarios qui appellent l'IA
- Joignez tout via les commandes

**Approche 2: Via API JavaScript**
- Créez une page HTML custom
- Appelez directement l'API
- Parsez les réponses

**Voir:** [API JavaScript - Intégration](06_API_JAVASCRIPT.md#6-intégration-avec-dautres-plugins)

---

### Q25: Puis-je héberger sur un autre serveur?

**R:** Techniquement possible mais:

1. **Pas recommandé** (complexe)
2. **Configuration requise:**
   - PHP 7.4+
   - Accès à l'API Jeedom
   - Jeedom version 4.0+

3. **Risques:**
   - Problèmes de synchronisation
   - Augmentation latence
   - Complexité maintenance

**Recommandation:** Installez directement sur Jeedom.

---

## Ressources

### Q26: Où trouver de l'aide?

**R:**

1. **Documentation:**
   - 📚 [Documentation complète](README.md) (vous êtes ici)
   - 🔍 [Dépannage](05_DEBOGAGE.md)
   - 💡 [Exemples](08_EXEMPLES.md)

2. **Communauté:**
   - 🌐 [Forum Jeedom](https://community.jeedom.com)
   - 🐙 [GitHub Issues](https://github.com/Yoann81/Jeedom-AI/issues)
   - 💬 [Discussions GitHub](https://github.com/Yoann81/Jeedom-AI/discussions)

3. **Support Moteurs IA:**
   - 🔍 [Google AI Studio](https://aistudio.google.com)
   - 🟢 [OpenAI Help](https://help.openai.com)
   - 🟣 [Mistral Support](https://docs.mistral.ai)

---

### Q27: Comment mettre à jour le plugin?

**R:**

1. **Vérifier les mises à jour:**
   - Plugins → Plugins de la communauté
   - Cherchez "AI Connector"
   - Si mise à jour disponible: cliquez "Mettre à jour"

2. **Mise à jour GitHub:**
   ```bash
   cd plugins/ai_connector
   git pull origin master
   ```

3. **Après mise à jour:**
   - Redémarrez le daemon
   - Videz le cache
   - Testez avec le diagnostic

---

### Q28: Quelle version Jeedom est requise?

**R:** **Jeedom 4.0+**

- ✓ 4.0 - 4.4: Compatible
- ✓ 4.2+: Recommandé
- ✗ Inférieur à 4.0: Non supporté

**Pour vérifier:**
- Administration → Système → Information
- Regardez "Version Jeedom"

---

### Q29: Y a-t-il une limite de questions?

**R:** Non limite technique, mais:

| Facteur | Impact |
|---------|--------|
| **Clé API** | Quota dépend du service |
| **Serveur** | Peut gérer ~100 req/min |
| **Timeout** | 30-60s par requête |

**Bonne pratique:** Utilisez le cache pour réutiliser les réponses identiques.

---

### Q30: Qu'advient-il de mes données?

**R:** Politique de données:

```
Données Jeedom
↓
Reste sur VOTRE serveur Jeedom
(jamais envoyées ailleurs)

Requêtes IA
↓
Envoyées aux serveurs:
- Google (Gemini)
- OpenAI (GPT)
- Mistral (Mistral)

↓
Traitement par le moteur IA
↓
Réponse retournée à Jeedom
↓
Affichée à l'utilisateur
```

**Recommandation:** Lisez les politiques de confidentialité des services utilisés.

---

## Support

Besoin d'aide? Consultez:
- 📖 [Démarrage rapide](01_DEMARRAGE_RAPIDE.md)
- 🔍 [Dépannage](05_DEBOGAGE.md)
- 🐛 [Créez une issue](https://github.com/Yoann81/Jeedom-AI/issues)

---

**Dernière mise à jour:** 03/01/2026
