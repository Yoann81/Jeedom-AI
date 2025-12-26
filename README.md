AI Multi-Connect (ai_connector)
Ce plugin pour Jeedom permet de centraliser et d'utiliser les principaux moteurs d'Intelligence Artificielle du marché via une interface unique. Posez des questions à vos IA préférées directement depuis vos scénarios et utilisez leurs réponses pour enrichir vos interactions domotiques.

🌟 Moteurs Supportés
Google Gemini (1.5 Flash / Pro)

OpenAI (ChatGPT gpt-4o-mini / gpt-4)

Mistral AI (Tiny / Small / Medium)

Vertex AI (Google Cloud) - En cours d'implémentation

Azure OpenAI - En cours d'implémentation

🚀 Fonctionnalités
Multi-équipement : Créez autant d'équipements que vous le souhaitez (un pour Gemini, un pour OpenAI, etc.).

Commandes simples : Une commande "Action" pour envoyer votre prompt et une commande "Info" pour stocker la réponse.

Compatibilité Scénarios : Intégrez l'IA dans vos automatisations (résumé de journée, alertes intelligentes, analyse de données).

Sécurisé : Les clés API sont stockées localement sur votre Jeedom.

⚙️ Configuration
1. Installation
Téléchargez/Installez le plugin.

Activez le plugin dans Gestion des plugins.

2. Obtention des clés API
Chaque moteur nécessite sa propre clé API :

Gemini : Google AI Studio

OpenAI : OpenAI Platform

Mistral : Mistral Console

3. Création d'un équipement
Rendez-vous dans Plugins > Communication > AI Multi-Connect.

Ajoutez un nouvel équipement.

Sélectionnez le Moteur d'IA souhaité dans la liste déroulante.

Renseignez votre Clé API.

Sauvegardez. Les commandes s'ajouteront automatiquement.

📖 Utilisation (Exemples)
Dans un scénario
Vous pouvez envoyer un message dynamique à l'IA :

Action : #[Salon][Mon IA][Poser une question]#

Message : "La température extérieure est de #[Extérieur][Sonde][Température]#°C. Donne-moi un conseil vestimentaire court pour sortir."

Récupération de la réponse
La réponse est instantanément stockée dans la commande #[Salon][Mon IA][Dernière réponse]#. Vous pouvez l'utiliser dans un bloc d'action suivant pour l'envoyer par SMS, Telegram ou via une synthèse vocale (TTS).

🛠 Maintenance et Logs
En cas de souci de connexion avec une API, consultez les logs du plugin : Analyse > Logs > ai_connector.

Auteur : Yoann81

Version : 1.0.0 (Bêta)

Licence : AGPL