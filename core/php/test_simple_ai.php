<?php
/**
 * Test simple de l'IA sans équipements
 * Pour vérifier que l'API IA fonctionne correctement
 */

require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

echo "<h1>🧪 Test Simple - API IA</h1>\n";
echo "<pre>\n";

// Récupérer l'équipement IA
$aiEqs = eqLogic::byType('ai_connector');

if (count($aiEqs) == 0) {
    echo "❌ Aucun équipement IA trouvé\n";
    exit;
}

$aiEq = $aiEqs[0];

echo "Équipement IA: " . $aiEq->getName() . " (ID: " . $aiEq->getId() . ")\n";
echo "Engine: " . $aiEq->getConfiguration('engine', 'gemini') . "\n\n";

// Tests de messages simples
$tests = [
    "Bonjour",
    "Quel est ton nom?",
    "Fais un court résumé sur l'IA",
];

foreach ($tests as $i => $message) {
    echo "=== Test " . ($i + 1) . " ===\n";
    echo "Message: " . $message . "\n";
    
    try {
        $response = $aiEq->processMessage($message);
        
        if (empty($response)) {
            echo "❌ Réponse vide\n";
        } elseif (strlen($response) > 500) {
            echo "✓ Réponse reçue (" . strlen($response) . " caractères):\n";
            echo "   " . substr($response, 0, 200) . "...\n";
        } else {
            echo "✓ Réponse: " . $response . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "=== Tests terminés ===\n";

// Afficher les logs
echo "\n=== LOGS RÉCENTS ===\n";
$logFile = dirname(__FILE__) . '/../../../../log/ai_connector';
if (file_exists($logFile)) {
    $lines = array_slice(file($logFile), -15);
    foreach ($lines as $line) {
        echo htmlspecialchars(trim($line)) . "\n";
    }
} else {
    echo "Fichier de log non trouvé\n";
}

echo "</pre>\n";
?>
