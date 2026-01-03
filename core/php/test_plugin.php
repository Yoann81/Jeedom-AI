<?php
/**
 * Test de vérification du plugin AI Connector
 * Point de contrôle rapide pour vérifier que tout fonctionne
 */

// Définir le répertoire racine de Jeedom pour les tests
define('JEEDOM_ROOT', dirname(__FILE__) . '/../../..');

// Charger le contexte Jeedom
try {
    require_once JEEDOM_ROOT . '/core/php/core.inc.php';
} catch (Exception $e) {
    echo "⚠️ Erreur: Impossible de charger Jeedom\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "\nVérifiez que ce script est exécuté depuis un serveur avec Jeedom installé.\n";
    exit(1);
}

echo "=" . str_repeat("=", 70) . "\n";
echo "✓ Test du Plugin AI Connector\n";
echo "=" . str_repeat("=", 70) . "\n\n";

// Test 1: Charger la classe
echo "📦 TEST 1: Chargement de la classe ai_connector\n";
echo "-" . str_repeat("-", 69) . "\n";

try {
    require_once dirname(__FILE__) . '/../class/ai_connector.class.php';
    echo "✅ Classe ai_connector chargée avec succès\n";
} catch (Exception $e) {
    echo "❌ Erreur lors du chargement de la classe:\n";
    echo "   " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Récupérer les équipements
echo "\n📦 TEST 2: Récupération des équipements\n";
echo "-" . str_repeat("-", 69) . "\n";

try {
    $equipments = ai_connector::getAllEquipments();
    echo "✅ Équipements récupérés: " . count($equipments) . "\n";
    
    if (count($equipments) > 0) {
        echo "\n   Premiers équipements:\n";
        for ($i = 0; $i < min(3, count($equipments)); $i++) {
            $eq = $equipments[$i];
            echo "   • " . $eq['name'] . " (ID: " . $eq['id'] . ", Type: " . $eq['type'] . ")\n";
            echo "     Status: " . (strlen($eq['status']) > 40 ? substr($eq['status'], 0, 40) . "..." : $eq['status']) . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Erreur lors de la récupération des équipements:\n";
    echo "   " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
}

// Test 3: Vérifier la fonction toSafeString
echo "\n📦 TEST 3: Fonction toSafeString() - Gestion des types\n";
echo "-" . str_repeat("-", 69) . "\n";

// On va utiliser la réflexion pour tester la méthode privée
try {
    $reflection = new ReflectionClass('ai_connector');
    $method = $reflection->getMethod('toSafeString');
    $method->setAccessible(true);
    
    // Tester différents types
    $tests = [
        ['value' => null, 'name' => 'null'],
        ['value' => 'string', 'name' => 'string'],
        ['value' => 123, 'name' => 'integer'],
        ['value' => true, 'name' => 'boolean true'],
        ['value' => false, 'name' => 'boolean false'],
        ['value' => ['a' => 1, 'b' => 2], 'name' => 'array'],
    ];
    
    foreach ($tests as $test) {
        $result = $method->invokeArgs(null, [$test['value']]);
        $type = gettype($test['value']);
        echo "✅ " . sprintf("%-20s", $test['name']) . " → " . (strlen($result) > 40 ? substr($result, 0, 40) . "..." : $result) . "\n";
    }
    
} catch (Exception $e) {
    echo "⚠️  Impossible de tester toSafeString: " . $e->getMessage() . "\n";
}

// Résumé
echo "\n" . "=" . str_repeat("=", 70) . "\n";
echo "✓ Tests complétés\n";
echo "=" . str_repeat("=", 70) . "\n";
echo "\n💡 Prochaines étapes:\n";
echo "   1. Ouvrir http://192.168.1.17/plugins/ai_connector/core/php/debug.php\n";
echo "   2. Vérifier que 26 équipements s'affichent\n";
echo "   3. Vérifier qu'il n'y a pas d'erreurs PHP\n";
echo "\n";

?>
