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
        try {
            $equipments = ai_connector::getAllEquipments();
            echo "Total trouvés: " . count($equipments) . "\n";
            
            if (count($equipments) == 0) {
                echo "⚠️  ATTENTION: Aucun équipement détecté!\n\n";
                
                // Vérifier pourquoi
                echo "Vérification détaillée:\n";
                
                // Tous les équipements
                $allEqs = eqLogic::all();
                echo "  Total équipements dans Jeedom: " . count($allEqs) . "\n";
                
                if (count($allEqs) > 0) {
                    echo "  Types d'équipements:\n";
                    $types = [];
                    foreach ($allEqs as $eq) {
                        $type = $eq->getType();
                        $types[$type] = ($types[$type] ?? 0) + 1;
                    }
                    foreach ($types as $type => $count) {
                        $aiType = ($type === 'ai_connector') ? ' (❌ Exclus)' : '';
                        echo "    - " . $type . ": " . $count . $aiType . "\n";
                    }
                    
                    // Vérifier les équipements non IA
                    $nonAiEqs = [];
                    foreach ($allEqs as $eq) {
                        if ($eq->getType() !== 'ai_connector') {
                            $nonAiEqs[] = $eq;
                        }
                    }
                    
                    if (count($nonAiEqs) > 0) {
                        echo "\n  Équipements non-IA trouvés (" . count($nonAiEqs) . "):\n";
                        foreach (array_slice($nonAiEqs, 0, 5) as $eq) {
                            echo "    • " . $eq->getName() . " (ID: " . $eq->getId() . ", Type: " . $eq->getType() . ", Activé: " . ($eq->getIsEnable() ? 'OUI' : 'NON') . ")\n";
                        }
                        if (count($nonAiEqs) > 5) {
                            echo "    ... et " . (count($nonAiEqs) - 5) . " autres\n";
                        }
                    } else {
                        echo "  ❌ Aucun équipement non-IA trouvé (seulement des IA)\n";
                    }
                } else {
                    echo "  ❌ Aucun équipement du tout dans Jeedom!\n";
                    echo "  Solution: Créez d'abord des équipements (lumières, thermostats, etc.)\n";
                }
            } else {
                echo "Premiers équipements:\n";
                foreach (array_slice($equipments, 0, 5) as $eq) {
                    $cmds = ai_connector::getEquipmentCommands($eq['id']);
                    echo "  • " . $eq['humanName'] . " (ID: " . $eq['id'] . ", Type: " . $eq['type'] . ", Commandes: " . count($cmds) . ")\n";
                }
                if (count($equipments) > 5) {
                    echo "  ... et " . (count($equipments) - 5) . " autres\n";
                }
            }
        } catch (Exception $e) {
            echo "❌ Exception: " . $e->getMessage() . "\n";
            echo $e->getTraceAsString() . "\n";
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
