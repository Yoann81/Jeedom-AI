<?php
/**
 * Diagnostic - Vérifier pourquoi l'IA ne répond pas
 * 
 * À lancer depuis: http://votre-jeedom/plugins/ai_connector/core/php/diagnostic.php
 */

require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

echo "<h1>🔍 Diagnostic AI Connector</h1>\n";
echo "<pre>\n";

// 1. Vérifier les équipements IA
echo "=== 1. ÉQUIPEMENTS IA ===\n";
$aiEqs = eqLogic::byType('ai_connector');
echo "Trouvés: " . count($aiEqs) . " équipement(s) IA\n\n";

if (count($aiEqs) == 0) {
    echo "❌ ERREUR: Aucun équipement IA! Créez-en un d'abord.\n";
} else {
    foreach ($aiEqs as $aiEq) {
        echo "ID: " . $aiEq->getId() . "\n";
        echo "Nom: " . $aiEq->getName() . "\n";
        echo "Activé: " . ($aiEq->getIsEnable() ? 'OUI ✓' : 'NON ❌') . "\n";
        
        // 2. Configuration
        echo "\n=== 2. CONFIGURATION ===\n";
        $engine = $aiEq->getConfiguration('engine', 'gemini');
        $apiKey = $aiEq->getConfiguration('apiKey', '');
        $model = $aiEq->getConfiguration('model', '');
        $prompt = $aiEq->getConfiguration('prompt', '');
        $includeEq = $aiEq->getConfiguration('include_equipments', 1);
        
        echo "Engine: " . $engine . "\n";
        echo "API Key: " . (empty($apiKey) ? "❌ MANQUANTE" : "✓ Configurée (" . strlen($apiKey) . " chars)") . "\n";
        echo "Model: " . (empty($model) ? "(défaut)" : $model) . "\n";
        echo "Prompt: " . (empty($prompt) ? "❌ VIDE" : "✓ " . strlen($prompt) . " chars") . "\n";
        echo "Include Equipments: " . ($includeEq ? "✓ OUI" : "❌ NON") . "\n";
        
        // 3. Commandes
        echo "\n=== 3. COMMANDES ===\n";
        $ask = $aiEq->getCmd(null, 'ask');
        if (is_object($ask)) {
            echo "✓ Commande 'ask' trouvée (ID: " . $ask->getId() . ")\n";
        } else {
            echo "❌ Commande 'ask' NON trouvée\n";
        }
        
        $response = $aiEq->getCmd(null, 'reponse');
        if (is_object($response)) {
            echo "✓ Commande 'reponse' trouvée (ID: " . $response->getId() . ")\n";
        } else {
            echo "❌ Commande 'reponse' NON trouvée\n";
        }
        
        // 4. Équipements
        echo "\n=== 4. ÉQUIPEMENTS DISPONIBLES ===\n";
        $equipments = ai_connector::getAllEquipments();
        echo "Total: " . count($equipments) . "\n";
        if (count($equipments) == 0) {
            echo "⚠️  Aucun équipement à contrôler\n";
        } else {
            echo "Premiers 3:\n";
            foreach (array_slice($equipments, 0, 3) as $eq) {
                echo "  - " . $eq['humanName'] . " (ID: " . $eq['id'] . ")\n";
            }
        }
        
        // 5. Tester un appel IA
        echo "\n=== 5. TEST API IA ===\n";
        echo "Tentative d'appel à l'API " . $engine . "...\n";
        
        try {
            $testPrompt = "Bonjour, réponds très brièvement en 1-2 mots.";
            $testResponse = $aiEq->processMessage($testPrompt);
            
            if (empty($testResponse)) {
                echo "❌ Réponse vide\n";
            } elseif (strpos($testResponse, 'Erreur') !== false || strpos($testResponse, 'error') !== false) {
                echo "❌ Erreur API: " . substr($testResponse, 0, 200) . "\n";
            } else {
                echo "✓ Réponse reçue: " . substr($testResponse, 0, 100) . "\n";
            }
        } catch (Exception $e) {
            echo "❌ Exception: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
}

// 6. Logs récents
echo "=== 6. LOGS RÉCENTS ===\n";
$logFile = dirname(__FILE__) . '/../../../../log/ai_connector';
if (file_exists($logFile)) {
    $lines = array_slice(file($logFile), -20);
    foreach ($lines as $line) {
        echo htmlspecialchars($line);
    }
} else {
    echo "Fichier de log non trouvé: " . $logFile . "\n";
}

echo "</pre>\n";
?>
