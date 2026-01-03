<?php
/**
 * Débogage - Affiche TOUTES les erreurs et avertissements
 * Idéal pour voir ce qui cause le problème
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

// Créer un handler pour afficher toutes les erreurs
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "<div style='background: #ffcccc; padding: 10px; margin: 10px 0; border-left: 5px solid red;'>";
    echo "<strong>Erreur PHP:</strong><br>";
    echo "Type: " . $errno . "<br>";
    echo "Message: " . htmlspecialchars($errstr) . "<br>";
    echo "Fichier: " . htmlspecialchars($errfile) . "<br>";
    echo "Ligne: " . $errline . "<br>";
    echo "</div>";
});

// Créer un handler pour les exceptions
set_exception_handler(function($exception) {
    echo "<div style='background: #ffeecc; padding: 10px; margin: 10px 0; border-left: 5px solid orange;'>";
    echo "<strong>Exception:</strong><br>";
    echo "Message: " . htmlspecialchars($exception->getMessage()) . "<br>";
    echo "Fichier: " . htmlspecialchars($exception->getFile()) . "<br>";
    echo "Ligne: " . $exception->getLine() . "<br>";
    echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
    echo "</div>";
});

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Débogage AI Connector</title>
    <style>
        body { font-family: monospace; background: #f5f5f5; padding: 20px; }
        .section { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>
<h1>🐛 Débogage - Diagnostic détaillé</h1>

<div class="section">
    <h2>1. Chargement de Jeedom</h2>
    <pre>
<?php

echo "Tentative de chargement de core.inc.php...\n";
try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    echo "✓ Jeedom chargé avec succès\n";
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    die();
}

?>
    </pre>
</div>

<div class="section">
    <h2>2. Vérification des équipements IA</h2>
    <pre>
<?php

echo "Recherche des équipements AI Connector...\n";
try {
    $aiEqs = eqLogic::byType('ai_connector');
    echo "✓ Trouvé: " . count($aiEqs) . " équipement(s)\n";

    if (count($aiEqs) > 0) {
        $aiEq = $aiEqs[0];
        echo "\nPremier équipement:\n";
        echo "  ID: " . $aiEq->getId() . "\n";
        echo "  Nom: " . $aiEq->getName() . "\n";
        echo "  Type (si disponible): " . (method_exists($aiEq, 'getType') ? $aiEq->getType() : 'N/A') . "\n";
        echo "  Activé: " . ($aiEq->getIsEnable() ? 'OUI' : 'NON') . "\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

?>
    </pre>
</div>

<div class="section">
    <h2>3. Vérification de la méthode getAllEquipments()</h2>
    <pre>
<?php

echo "Vérification de ai_connector::getAllEquipments()...\n";
try {
    if (class_exists('ai_connector')) {
        echo "✓ Classe ai_connector trouvée\n";

        if (method_exists('ai_connector', 'getAllEquipments')) {
            echo "✓ Méthode getAllEquipments trouvée\n";

            echo "\nTentative d'appel de getAllEquipments()...\n";
            $equipments = ai_connector::getAllEquipments();
            echo "✓ Appel réussi\n";
            echo "  Résultat: " . count($equipments) . " équipement(s)\n";

            if (count($equipments) > 0) {
                echo "\nPremier équipement:\n";
                $eq = $equipments[0];
                foreach ($eq as $key => $value) {
                    echo "  " . $key . ": " . htmlspecialchars($value) . "\n";
                }
            }
        } else {
            echo "❌ Méthode getAllEquipments NON trouvée\n";
            echo "Méthodes disponibles:\n";
            $methods = get_class_methods('ai_connector');
            foreach ($methods as $method) {
                echo "  - " . $method . "\n";
            }
        }
    } else {
        echo "❌ Classe ai_connector NON trouvée\n";
        echo "Classes disponibles:\n";
        echo "  (impossible à lister)\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

?>
    </pre>
</div>

<div class="section">
    <h2>4. Test de l'API IA</h2>
    <pre>
<?php

try {
    $aiEqs = eqLogic::byType('ai_connector');
    if (count($aiEqs) > 0) {
        $aiEq = $aiEqs[0];
        echo "Test d'appel API IA...\n";
        echo "Engine: " . $aiEq->getConfiguration('engine', 'gemini') . "\n";

        $response = $aiEq->processMessage("test");
        echo "✓ Réponse reçue (" . strlen($response) . " chars)\n";
        echo "Contenu: " . substr($response, 0, 200) . "\n";
    } else {
        echo "Aucun équipement IA\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

?>
    </pre>
</div>

<div class="section">
    <h2>5. Fichiers du plugin</h2>
    <pre>
<?php

$pluginPath = dirname(__FILE__) . '/..';
echo "Chemin du plugin: " . $pluginPath . "\n\n";

echo "Fichiers principaux:\n";
$files = [
    'class/ai_connector.class.php',
    'ajax/ai_connector.ajax.php',
    'diagnostic_simple.php',
    'test_simple_ai.php'
];

foreach ($files as $file) {
    $fullPath = $pluginPath . '/' . $file;
    $exists = file_exists($fullPath) ? '✓ Existe' : '❌ Manquant';
    $size = file_exists($fullPath) ? filesize($fullPath) : 0;
    echo "  " . $file . ": " . $exists . " (" . $size . " bytes)\n";
}

?>
    </pre>
</div>

</body>
</html>
