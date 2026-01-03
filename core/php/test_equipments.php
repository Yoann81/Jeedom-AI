<?php
/**
 * Script de test pour vérifier la récupération et l'exécution des équipements
 * À lancer depuis la console Jeedom ou via curl
 */

// Vérifier que les fonctions sont accessibles
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

echo "=== TEST RÉCUPÉRATION ÉQUIPEMENTS ===\n\n";

// Test 1: Récupérer tous les équipements
echo "1. Récupération de tous les équipements:\n";
$equipments = ai_connector::getAllEquipments();
echo "   Total: " . count($equipments) . " équipement(s)\n";
foreach ($equipments as $eq) {
    echo "   - " . $eq['humanName'] . " (ID: " . $eq['id'] . ", Type: " . $eq['type'] . ", Activé: " . ($eq['isEnable'] ? 'OUI' : 'NON') . ")\n";
}

// Test 2: Récupérer les commandes de chaque équipement
echo "\n2. Récupération des commandes:\n";
foreach ($equipments as $eq) {
    if (!$eq['isEnable']) continue;
    
    $commands = ai_connector::getEquipmentCommands($eq['id']);
    echo "   " . $eq['humanName'] . " (" . count($commands) . " commande(s)):\n";
    
    foreach ($commands as $cmd) {
        if (!$cmd['isVisible']) continue;
        
        $cmdType = $cmd['type'] === 'action' ? '🔘' : 'ℹ️';
        echo "      $cmdType " . $cmd['name'] . " (ID: " . $cmd['id'] . ")\n";
        
        if ($cmd['type'] === 'action') {
            echo "         Type: " . $cmd['subType'] . "\n";
            if ($cmd['subType'] === 'slider' && $cmd['minValue'] !== null && $cmd['maxValue'] !== null) {
                echo "         Plage: " . $cmd['minValue'] . " - " . $cmd['maxValue'] . "\n";
            }
        } elseif ($cmd['type'] === 'info') {
            echo "         Valeur: " . htmlspecialchars($cmd['value']) . " " . $cmd['unit'] . "\n";
        }
    }
}

// Test 3: Vérifier le contexte IA
echo "\n3. Contexte IA généré:\n";
$aiEq = eqLogic::byType('ai_connector');
if (count($aiEq) > 0) {
    $context = $aiEq[0]->getJeedomContextForAI();
    echo $context . "\n";
} else {
    echo "   ⚠️  Aucun équipement IA trouvé\n";
}

// Test 4: Vérifier que les commandes d'action existent
echo "\n4. Commandes d'action disponibles:\n";
$actionCommands = [];
foreach ($equipments as $eq) {
    if (!$eq['isEnable']) continue;
    
    $commands = ai_connector::getEquipmentCommands($eq['id']);
    foreach ($commands as $cmd) {
        if ($cmd['type'] === 'action' && $cmd['isVisible']) {
            $actionCommands[] = [
                'cmd_id' => $cmd['id'],
                'name' => $cmd['name'],
                'eq_name' => $eq['humanName'],
                'subType' => $cmd['subType']
            ];
        }
    }
}

echo "   Total: " . count($actionCommands) . " commande(s) d'action\n";
if (count($actionCommands) > 0) {
    echo "   Exemples:\n";
    foreach (array_slice($actionCommands, 0, 5) as $cmd) {
        echo "      - " . $cmd['eq_name'] . " → " . $cmd['name'] . " (ID: " . $cmd['cmd_id'] . ")\n";
    }
}

echo "\n=== TEST TERMINÉ ===\n";
?>
