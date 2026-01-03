<?php
/**
 * Debug détaillé - Vérifier pourquoi les équipements ne remontent pas
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Debug Équipements - AI Connector</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; }
        h2 { color: #555; margin-top: 30px; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
        .section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #667eea; border-radius: 4px; }
        .ok { background: #d4edda; border-left-color: #28a745; }
        .error { background: #f8d7da; border-left-color: #dc3545; }
        .warning { background: #fff3cd; border-left-color: #ffc107; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        tr:hover { background: #f5f5f5; }
        code { background: #eee; padding: 2px 5px; border-radius: 3px; font-family: monospace; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .status { display: inline-block; padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .status-ok { background: #d4edda; color: #155724; }
        .status-error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔧 Debug Équipements - AI Connector</h1>

<?php

// Charger Jeedom
echo "<div class='section'>";
echo "<h2>1️⃣ Chargement de Jeedom</h2>";
try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    echo "<div class='ok'>✓ Jeedom chargé</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Erreur: " . htmlspecialchars($e->getMessage()) . "</div>";
    die();
}
echo "</div>";

// Vérifier ai_connector
echo "<div class='section'>";
echo "<h2>2️⃣ Classe ai_connector</h2>";
if (class_exists('ai_connector')) {
    echo "<div class='ok'>✓ Classe trouvée</div>";
} else {
    echo "<div class='error'>✗ Classe NON trouvée</div>";
    die();
}
echo "</div>";

// Lister tous les équipements Jeedom (bruts)
echo "<div class='section'>";
echo "<h2>3️⃣ Tous les équipements Jeedom (bruts)</h2>";
try {
    $allEqs = eqLogic::all();
    echo "<div class='ok'>✓ Total: " . count($allEqs) . " équipement(s)</div>";
    
    echo "<table>";
    echo "<thead><tr>";
    echo "<th>ID</th>";
    echo "<th>Nom</th>";
    echo "<th>getType()?</th>";
    echo "<th>Type</th>";
    echo "<th>Classe</th>";
    echo "<th>Actif</th>";
    echo "</tr></thead>";
    echo "<tbody>";
    
    foreach ($allEqs as $eq) {
        $hasGetType = method_exists($eq, 'getType') ? '✓' : '✗';
        $type = 'N/A';
        if (method_exists($eq, 'getType')) {
            try {
                $type = $eq->getType();
            } catch (Exception $e) {
                $type = 'ERROR: ' . $e->getMessage();
            }
        }
        
        $active = $eq->getIsEnable() ? '✓' : '✗';
        $class = get_class($eq);
        
        echo "<tr>";
        echo "<td>" . $eq->getId() . "</td>";
        echo "<td>" . htmlspecialchars($eq->getName()) . "</td>";
        echo "<td>" . $hasGetType . "</td>";
        echo "<td><code>" . htmlspecialchars($type) . "</code></td>";
        echo "<td><code>" . $class . "</code></td>";
        echo "<td>" . $active . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div class='error'>✗ Exception: " . htmlspecialchars($e->getMessage()) . "</div>";
}
echo "</div>";

// Appel getAllEquipments()
echo "<div class='section'>";
echo "<h2>4️⃣ Test de getAllEquipments()</h2>";
try {
    $equipments = ai_connector::getAllEquipments();
    echo "<div class='ok'>✓ Retourné: " . count($equipments) . " équipement(s)</div>";
    
    if (count($equipments) > 0) {
        echo "<table>";
        echo "<thead><tr>";
        echo "<th>ID</th>";
        echo "<th>Nom</th>";
        echo "<th>Type</th>";
        echo "<th>Actif</th>";
        echo "</tr></thead>";
        echo "<tbody>";
        
        foreach ($equipments as $eq) {
            echo "<tr>";
            echo "<td>" . $eq['id'] . "</td>";
            echo "<td>" . htmlspecialchars($eq['humanName']) . "</td>";
            echo "<td><code>" . htmlspecialchars($eq['type']) . "</code></td>";
            echo "<td>" . ($eq['isEnable'] ? '✓' : '✗') . "</td>";
            echo "</tr>";
        }
        
        echo "</tbody>";
        echo "</table>";
    } else {
        echo "<div class='warning'>⚠️ Aucun équipement retourné</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>✗ Exception: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
echo "</div>";

// Analyse des types
echo "<div class='section'>";
echo "<h2>5️⃣ Analyse des types d'équipements</h2>";
try {
    $allEqs = eqLogic::all();
    $types = [];
    $typesCount = [];
    
    foreach ($allEqs as $eq) {
        if (method_exists($eq, 'getType')) {
            try {
                $type = $eq->getType();
                $typesCount[$type] = ($typesCount[$type] ?? 0) + 1;
            } catch (Exception $e) {
                $typesCount['ERROR'] = ($typesCount['ERROR'] ?? 0) + 1;
            }
        } else {
            $typesCount['NO_GETTYPE'] = ($typesCount['NO_GETTYPE'] ?? 0) + 1;
        }
    }
    
    echo "<table>";
    echo "<thead><tr><th>Type</th><th>Nombre</th></tr></thead>";
    echo "<tbody>";
    
    foreach ($typesCount as $type => $count) {
        $excluded = $type === 'ai_connector' ? ' (EXCLU)' : '';
        echo "<tr>";
        echo "<td><code>" . htmlspecialchars($type) . "</code></td>";
        echo "<td>" . $count . $excluded . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div class='error'>✗ Erreur: " . htmlspecialchars($e->getMessage()) . "</div>";
}
echo "</div>";

// Recommandations
echo "<div class='section'>";
echo "<h2>📋 Recommandations</h2>";

$allEqs = eqLogic::all();
$equipmentsViaMethod = ai_connector::getAllEquipments();

if (count($allEqs) > 0 && count($equipmentsViaMethod) == 0) {
    echo "<div class='error'>✗ PROBLÈME DÉTECTÉ:</div>";
    echo "<ul>";
    echo "<li>Total équipements Jeedom: " . count($allEqs) . "</li>";
    echo "<li>Équipements remontés par getAllEquipments(): " . count($equipmentsViaMethod) . "</li>";
    echo "<li><strong>Cause probable:</strong> Tous les équipements sont probablement du type 'ai_connector' ou mal filtrés</li>";
    echo "</ul>";
} elseif (count($equipmentsViaMethod) > 0) {
    echo "<div class='ok'>✓ Équipements détectés correctement</div>";
} else {
    echo "<div class='warning'>ℹ️ Créez des équipements dans Jeedom pour les rendre disponibles à l'IA</div>";
}

echo "</div>";

echo "</div>"; // Fermer container

?>

</body>
</html>
