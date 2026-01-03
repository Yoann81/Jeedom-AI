<?php
/**
 * Diagnostic SIMPLE et ROBUSTE - Sans erreurs silencieuses
 * 
 * À lancer depuis: http://votre-jeedom/plugins/ai_connector/core/php/diagnostic_simple.php
 */

// Supprimer les warnings des fichiers de cache manquants de Jeedom
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', '0');

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
} catch (Exception $e) {
    die("❌ Erreur lors du chargement de Jeedom: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Diagnostic AI Connector</title>
    <style>
        body { font-family: monospace; background: #f5f5f5; padding: 20px; }
        pre { background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .ok { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        h1 { color: #333; }
        .section { margin: 20px 0; }
    </style>
</head>
<body>
<h1>🔍 Diagnostic AI Connector (Simple)</h1>
<pre>
<?php

echo "=== 1. ÉQUIPEMENTS IA ===\n";
try {
    $aiEqs = eqLogic::byType('ai_connector');
    echo "Trouvés: <span class='ok'>" . count($aiEqs) . "</span> équipement(s) IA\n\n";

    if (count($aiEqs) == 0) {
        echo "<span class='error'>❌ Aucun équipement IA! Créez-en un d'abord.</span>\n";
        die();
    }

    $aiEq = $aiEqs[0];
    echo "ID: " . $aiEq->getId() . "\n";
    echo "Nom: " . $aiEq->getName() . "\n";
    echo "Activé: " . ($aiEq->getIsEnable() ? '<span class="ok">OUI ✓</span>' : '<span class="error">NON ❌</span>') . "\n";

} catch (Exception $e) {
    echo "<span class='error'>❌ Erreur: " . $e->getMessage() . "</span>\n";
    die();
}

echo "\n=== 2. CONFIGURATION ===\n";
try {
    $engine = $aiEq->getConfiguration('engine', 'gemini');
    $apiKey = $aiEq->getConfiguration('apiKey', '');
    $model = $aiEq->getConfiguration('model', '');
    $prompt = $aiEq->getConfiguration('prompt', '');
    $includeEq = $aiEq->getConfiguration('include_equipments', 1);

    echo "Engine: " . $engine . "\n";
    echo "API Key: " . (empty($apiKey) ? "<span class='error'>❌ MANQUANTE</span>" : "<span class='ok'>✓ Configurée (" . strlen($apiKey) . " chars)</span>") . "\n";
    echo "Model: " . (empty($model) ? "(défaut)" : $model) . "\n";
    echo "Prompt: " . (empty($prompt) ? "<span class='error'>❌ VIDE</span>" : "<span class='ok'>✓ " . strlen($prompt) . " chars</span>") . "\n";
    echo "Include Equipments: " . ($includeEq ? "<span class='ok'>✓ OUI</span>" : "<span class='error'>❌ NON</span>") . "\n";

} catch (Exception $e) {
    echo "<span class='error'>❌ Erreur configuration: " . $e->getMessage() . "</span>\n";
}

echo "\n=== 3. COMMANDES ===\n";
try {
    $ask = $aiEq->getCmd(null, 'ask');
    if (is_object($ask)) {
        echo "<span class='ok'>✓ Commande 'ask' trouvée (ID: " . $ask->getId() . ")</span>\n";
    } else {
        echo "<span class='error'>❌ Commande 'ask' NON trouvée</span>\n";
    }

    $response = $aiEq->getCmd(null, 'reponse');
    if (is_object($response)) {
        echo "<span class='ok'>✓ Commande 'reponse' trouvée (ID: " . $response->getId() . ")</span>\n";
    } else {
        echo "<span class='error'>❌ Commande 'reponse' NON trouvée</span>\n";
    }

} catch (Exception $e) {
    echo "<span class='error'>❌ Erreur commandes: " . $e->getMessage() . "</span>\n";
}

echo "\n=== 4. ÉQUIPEMENTS DISPONIBLES ===\n";
try {
    // Vérifier que la méthode existe
    if (!method_exists('ai_connector', 'getAllEquipments')) {
        echo "<span class='error'>❌ Méthode ai_connector::getAllEquipments() non trouvée!</span>\n";
        echo "Vérifiez que core/class/ai_connector.class.php est correct.\n";
    } else {
        $equipments = ai_connector::getAllEquipments();
        echo "Total trouvés: <span class='" . (count($equipments) > 0 ? 'ok' : 'warning') . "'>" . count($equipments) . "</span>\n";

        if (count($equipments) == 0) {
            echo "<span class='warning'>⚠️  Aucun équipement détecté</span>\n\n";

            // Vérification détaillée
            $allEqs = eqLogic::all();
            echo "Total équipements dans Jeedom: " . count($allEqs) . "\n";

            if (count($allEqs) > 0) {
                echo "\nTypes d'équipements:\n";
                $types = [];
                foreach ($allEqs as $eq) {
                    $type = $eq->getType();
                    $types[$type] = ($types[$type] ?? 0) + 1;
                }
                foreach ($types as $type => $count) {
                    $note = ($type === 'ai_connector') ? ' (exclus de la liste IA)' : '';
                    echo "  - " . $type . ": " . $count . $note . "\n";
                }

                // Équipements non-IA
                $nonAiEqs = [];
                foreach ($allEqs as $eq) {
                    if ($eq->getType() !== 'ai_connector') {
                        $nonAiEqs[] = $eq;
                    }
                }

                if (count($nonAiEqs) > 0) {
                    echo "\n<span class='ok'>✓ Équipements non-IA trouvés (" . count($nonAiEqs) . "):</span>\n";
                    foreach (array_slice($nonAiEqs, 0, 5) as $eq) {
                        echo "  • " . $eq->getName() . " (ID: " . $eq->getId() . ", Type: " . $eq->getType() . ")\n";
                    }
                    if (count($nonAiEqs) > 5) {
                        echo "  ... et " . (count($nonAiEqs) - 5) . " autres\n";
                    }
                } else {
                    echo "\n<span class='error'>❌ Aucun équipement non-IA trouvé (seulement des IA)</span>\n";
                }
            } else {
                echo "<span class='error'>❌ Aucun équipement du tout dans Jeedom!</span>\n";
                echo "Créez des équipements d'abord (lumières, thermostats, etc.)\n";
            }
        } else {
            echo "\n<span class='ok'>✓ Premiers équipements:</span>\n";
            foreach (array_slice($equipments, 0, 5) as $eq) {
                echo "  • " . $eq['humanName'] . " (ID: " . $eq['id'] . ", Type: " . $eq['type'] . ")\n";
            }
            if (count($equipments) > 5) {
                echo "  ... et " . (count($equipments) - 5) . " autres\n";
            }
        }
    }

} catch (Exception $e) {
    echo "<span class='error'>❌ Exception: " . $e->getMessage() . "</span>\n";
    echo "Trace:\n";
    echo htmlspecialchars($e->getTraceAsString()) . "\n";
}

echo "\n=== 5. TEST SIMPLE ===\n";
try {
    echo "Tentative d'appel IA simple...\n";
    $testResponse = $aiEq->processMessage("Bonjour");

    if (empty($testResponse)) {
        echo "<span class='error'>❌ Réponse vide</span>\n";
    } elseif (strpos($testResponse, 'Erreur') !== false || strpos($testResponse, 'error') !== false) {
        echo "<span class='error'>❌ Erreur API: " . substr($testResponse, 0, 100) . "</span>\n";
    } else {
        echo "<span class='ok'>✓ Réponse OK: " . substr($testResponse, 0, 100) . "</span>\n";
    }

} catch (Exception $e) {
    echo "<span class='error'>❌ Exception test: " . $e->getMessage() . "</span>\n";
}

echo "\n=== DIAGNOSTIC TERMINÉ ===\n";
?>
</pre>
</body>
</html>
