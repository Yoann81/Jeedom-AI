<?php
/**
 * Exemple d'utilisation - Configuration recommandée pour les équipements IA
 * 
 * Ce fichier montre comment configurer votre équipement IA pour contrôler
 * vos équipements Jeedom via une intelligence artificielle.
 */

// EXEMPLE 1: Configuration basique d'un assistant IA domestique
$exampleConfig_1 = [
    'name' => 'Assistant Maison',
    'engine' => 'gemini', // ou 'openai', 'mistral'
    'apiKey' => 'VOTRE_CLE_API', // À remplir
    'model' => 'gemini-1.5-flash',
    'prompt' => 'Tu es un assistant IA pour contrôler ma maison Jeedom. Tu dois:
1. Écouter les demandes de l\'utilisateur
2. Identifier les équipements et commandes correspondantes
3. Exécuter les actions demandées en utilisant le format [EXEC_COMMAND: id_commande]
4. Confirmer les actions exécutées
Sois courtois et utile. Si tu ne comprends pas une demande, demande une clarification.
Ne modifie pas les équipements critiques sans confirmation.',
    'include_equipments' => 1, // Très important !
    'voice_enable' => 0, // 1 pour écoute vocale
    'tts_enable' => 1, // Réponder vocalement
    'tts_language' => 'fr-FR',
    'tts_voice' => 'fr-FR-Neural2-A'
];

// EXEMPLE 2: Assistant multilingue avec contexte enrichi
$exampleConfig_2 = [
    'name' => 'Assistant Multilingue',
    'engine' => 'openai',
    'apiKey' => 'sk-...',
    'model' => 'gpt-4',
    'prompt' => 'Tu es un assistant IA intelligent pour la domotique Jeedom en français.
Tu DOIS suivre ces règles:

ÉQUIPEMENTS:
- Identifie les équipements par leur nom d\'affichage (humanName)
- Vérifie que l\'équipement est activé avant d\'agir
- Utilise l\'ID de commande exact fourni

EXÉCUTION DE COMMANDES:
- Format obligatoire: [EXEC_COMMAND: numero_id]
- Une seule commande par action
- Attends la confirmation avant d\'exécuter la suivante

SÉCURITÉ:
- Demande confirmation pour les actions sensibles (chauffage > 25°C, verrouillage, etc.)
- Refuse les commandes répétées trop rapidement
- Signale les équipements désactivés

COMMUNICATION:
- Réponds en français naturel
- Sois précis et concis
- Confirme chaque action exécutée',
    'include_equipments' => 1,
    'voice_enable' => 1,
    'voice_device_id' => '1',
    'stt_engine' => 'whisper',
    'stt_language' => 'fr-FR',
    'porcupine_enable' => 0,
    'tts_enable' => 1,
    'tts_language' => 'fr-FR',
    'tts_voice' => 'fr-FR-Neural2-A'
];

// EXEMPLE 3: Assistant spécialisé énergie/consommation
$exampleConfig_3 = [
    'name' => 'Gestionnaire Énergie',
    'engine' => 'mistral',
    'apiKey' => 'VOTRE_CLE_MISTRAL',
    'model' => 'mistral-large-latest',
    'prompt' => 'Tu es un assistant spécialisé dans la gestion de l\'énergie domestique.
Tu dois:

1. ANALYSER la consommation:
   - Consulter les mesures de consommation (équipements type "energy")
   - Identifier les équipements énergivores
   - Proposer des optimisations

2. CONTRÔLER les équipements:
   - Éteindre les appareils inutilisés
   - Optimiser le chauffage/climatisation
   - Utiliser le format [EXEC_COMMAND: id]

3. RAPPORTER:
   - Sommes régulières de consommation
   - Recommandations d\'économie
   - Actions exécutées

Priorité: Réduire la consommation sans sacrifier le confort.',
    'include_equipments' => 1,
    'tts_enable' => 0 // Sans voix, mode texte
];

// EXEMPLE 4: Intégration via webhooks
$webhookExample = <<<'PHP'
<?php
// Exemple: Ajouter un endpoint webhook pour intégrer avec des services externes

// URL: http://votreip/plugins/ai_connector/core/php/webhook.php
// POST data: {"message": "Allume le salon", "eq_id": 1}

$message = $_POST['message'] ?? '';
$eq_id = $_POST['eq_id'] ?? '';

if (!empty($message) && !empty($eq_id)) {
    $eqLogic = eqLogic::byId($eq_id);
    if ($eqLogic && $eqLogic->getType() === 'ai_connector') {
        $response = $eqLogic->processMessage($message);
        echo json_encode(['status' => 'success', 'response' => $response]);
    }
}
?>
PHP;

// EXEMPLE 5: Utilisation via JavaScript dans un script personnalisé
$jsExample = <<<'JS'
// Récupérer tous les équipements
jeedom.ajax.loadData({
    type: 'POST',
    global: false,
    url: 'plugins/ai_connector/core/ajax/ai_connector.ajax.php',
    data: {action: 'getAllEquipments'},
    success: function(data) {
        console.log('Équipements disponibles:', data);
        // data est un array d'équipements
    }
});

// Exécuter une commande
jeedom.ajax.loadData({
    type: 'POST',
    global: false,
    url: 'plugins/ai_connector/core/ajax/ai_connector.ajax.php',
    data: {
        action: 'executeCommand',
        cmd_id: 42,
        options: JSON.stringify({value: 75}) // Pour les sliders
    },
    success: function(data) {
        console.log('Résultat:', data);
    }
});

// Récupérer le contexte IA
jeedom.ajax.loadData({
    type: 'POST',
    global: false,
    url: 'plugins/ai_connector/core/ajax/ai_connector.ajax.php',
    data: {
        action: 'getJeedomContext',
        eq_id: 1 // ID de votre équipement IA
    },
    success: function(data) {
        console.log('Contexte IA:', data);
    }
});
JS;

// EXEMPLE 6: Chaines de commandes (scénarios IA)
$scenarioExample = <<<'SCENARIO'
/* Scénario: Soirée cinéma
   Demande à l'IA: "Prépare le mode cinéma"
   
   L'IA devrait:
   - Éteindre les lumières du salon [EXEC_COMMAND: 43]
   - Mettre les lumières de la chambre à 20% [EXEC_COMMAND: 82]
   - Fermer les volets [EXEC_COMMAND: 105]
   - Mettre le système audio en surround [EXEC_COMMAND: 67]
*/

/* Scénario: Je pars
   Demande: "Je pars de la maison"
   
   L'IA devrait:
   - Éteindre toutes les lumières
   - Fermer les portes intelligentes
   - Baisser le chauffage
   - Mettre la caméra en mode surveillance
*/
SCENARIO;

?>
