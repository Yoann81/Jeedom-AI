<?php
/**
 * Test des endpoints AJAX
 * Simule les appels AJAX pour vérifier que les endpoints répondent correctement
 */

// À lancer comme: php test_ajax_endpoints.php

require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

echo "=== TEST DES ENDPOINTS AJAX ===\n\n";

// Simulation des tests AJAX
$tests = [
    [
        'name' => 'getAllEquipments',
        'action' => 'getAllEquipments',
        'params' => []
    ],
    [
        'name' => 'getAllEquipmentsWithCommands',
        'action' => 'getAllEquipmentsWithCommands',
        'params' => []
    ]
];

// Ajouter les tests spécifiques si des équipements existent
$equipments = ai_connector::getAllEquipments();
if (count($equipments) > 0) {
    $eq = $equipments[0];
    tests[] = [
        'name' => 'getEquipmentCommands (ID: ' . $eq['id'] . ')',
        'action' => 'getEquipmentCommands',
        'params' => ['eq_id' => $eq['id']]
    ];
}

// Ajouter test du contexte si IA existe
$aiEqs = eqLogic::byType('ai_connector');
if (count($aiEqs) > 0) {
    $aiEq = $aiEqs[0];
    tests[] = [
        'name' => 'getJeedomContext (AI ID: ' . $aiEq->getId() . ')',
        'action' => 'getJeedomContext',
        'params' => ['eq_id' => $aiEq->getId()]
    ];
}

// Exécuter les tests
foreach ($tests as $test) {
    echo "Test: " . $test['name'] . "\n";
    
    // Simuler l'appel AJAX
    $_GET['action'] = $test['action'];
    foreach ($test['params'] as $key => $value) {
        $_GET[$key] = $value;
    }
    
    echo "  Action: " . $test['action'] . "\n";
    echo "  Paramètres: " . json_encode($test['params']) . "\n";
    
    try {
        // Exécuter l'action
        if ($test['action'] === 'getAllEquipments') {
            $result = ai_connector::getAllEquipments();
            echo "  ✓ Résultat: " . count($result) . " équipement(s)\n";
        } elseif ($test['action'] === 'getAllEquipmentsWithCommands') {
            $result = ai_connector::getAllEquipments();
            $count = 0;
            foreach ($result as $eq) {
                $cmds = ai_connector::getEquipmentCommands($eq['id']);
                $count += count($cmds);
            }
            echo "  ✓ Résultat: " . count($result) . " équipement(s) avec " . $count . " commande(s) total\n";
        } elseif ($test['action'] === 'getEquipmentCommands') {
            $result = ai_connector::getEquipmentCommands($test['params']['eq_id']);
            echo "  ✓ Résultat: " . count($result) . " commande(s)\n";
        } elseif ($test['action'] === 'getJeedomContext') {
            $aiEq = eqLogic::byId($test['params']['eq_id']);
            if ($aiEq && $aiEq->getType() === 'ai_connector') {
                $result = $aiEq->getJeedomContextForAI();
                echo "  ✓ Résultat: contexte généré (" . strlen($result) . " caractères)\n";
            } else {
                echo "  ✗ Équipement IA non trouvé\n";
            }
        }
    } catch (Exception $e) {
        echo "  ✗ Erreur: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "=== TESTS TERMINÉS ===\n";
?>
