# \# Documentation AI Multi-Connect

# 

# Ce plugin permet de connecter Jeedom aux principaux services d'Intelligence Artificielle (LLM) du marché via leurs API respectives.

# 

# \## 1. Description

# Le plugin \*\*AI Multi-Connect\*\* centralise vos accès aux IA pour faciliter leur utilisation dans vos scénarios domotiques.

# 

# \### Moteurs disponibles :

# \- \*\*Google Gemini\*\* : Rapide et efficace (idéal pour le Raspberry Pi).

# \- \*\*OpenAI (ChatGPT)\*\* : Le standard du marché.

# \- \*\*Mistral AI\*\* : L'alternative française haute performance.

# \- \*Azure et Vertex AI sont prévus dans les prochaines mises à jour.\*

# 

# \## 2. Configuration du plugin

# Après installation du plugin, il vous suffit de l'activer. Aucune configuration globale n'est nécessaire ; tout se gère au niveau de chaque équipement.

# 

# \## 3. Configuration des équipements

# Rendez-vous dans le menu \*\*Plugins > Communication > AI Multi-Connect\*\*.

# 

# \### Paramètres de l'équipement :

# \- \*\*Nom de l'équipement\*\* : Identifiant de votre IA (ex: "Gemini Assistant").

# \- \*\*Objet parent\*\* : Objet Jeedom auquel l'IA sera rattachée.

# \- \*\*Catégorie\*\* : Communication.

# \- \*\*Activer / Visible\*\* : Cocher pour utiliser l'équipement.

# 

# \### Paramètres spécifiques :

# \- \*\*Moteur d'IA\*\* : Choisissez le fournisseur (Gemini, OpenAI, Mistral).

# \- \*\*Clé API\*\* : Collez ici la clé secrète fournie par le site de l'IA choisie.

# 

# \## 4. Les Commandes

# Dès la sauvegarde, deux commandes sont créées :

# \- \*\*Poser une question\*\* (Action/Message) : C'est la commande que vous utilisez pour envoyer votre texte.

# \- \*\*Dernière réponse\*\* (Info/Autre) : Contient le texte brut renvoyé par l'IA.

# 

# \## 5. Exemples d'utilisation

# 

# \### Scénario : Alerte de sécurité intelligente

# Si une présence est détectée et que l'alarme est mise :

# \- \*\*Action\*\* : `\[Salon]\[Gemini]\[Poser une question]`

# \- \*\*Message\*\* : "Une présence a été détectée dans le jardin à 3h du matin. Rédige un message d'alerte court et urgent pour mon propriétaire."

# \- \*\*Action\*\* : `\[Telegram]\[Moi]\[Envoi]` avec le contenu `#\[Salon]\[Gemini]\[Dernière réponse]#`.

# 

# \### Scénario : Conseil météo

# \- \*\*Action\*\* : `\[Salon]\[Mistral]\[Poser une question]`

# \- \*\*Message\*\* : "Il pleut et il fait 12 degrés. Dois-je prendre un parapluie pour sortir ?"

# \- \*\*Action\*\* : `\[Cuisine]\[Enceinte]\[Dire]` avec le contenu `#\[Salon]\[Mistral]\[Dernière réponse]#`.

# 

# \## 6. FAQ

# \*\*Est-ce que le plugin est payant ?\*\*

# Le plugin lui-même dépend de sa licence sur le Market, mais l'utilisation des IA peut engendrer des coûts selon les quotas gratuits des fournisseurs (ex: Gemini possède un niveau gratuit généreux).

# 

# \*\*Le plugin est-il lent ?\*\*

# Le temps de réponse dépend uniquement de la vitesse de l'API distante et de la complexité de votre question. En général, la réponse arrive en 2 à 5 secondes.

# 

# \## 7. Troubleshooting

# \- \*\*Pas de réponse\*\* : Vérifiez votre clé API et les logs du plugin (`Analyse > Logs > ai\_connector`).

# \- \*\*Erreur SSL\*\* : Assurez-vous que votre Raspberry Pi est à l'heure (`sudo ntpd -q -g`).

