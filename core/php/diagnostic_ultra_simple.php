<?php
/**
 * Ultra-simple diagnostic - Juste les vérifications essentielles
 * Sans pièges ni erreurs silencieuses
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Diagnostic Ultra-Simple</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .ok { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 10px; margin: 5px 0; border-radius: 3px; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 10px; margin: 5px 0; border-radius: 3px; }
        .warning { background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 10px; margin: 5px 0; border-radius: 3px; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 10px; margin: 5px 0; border-radius: 3px; }
        h1 { color: #333; }
        h2 { color: #555; margin-top: 20px; }
        code { background: #eee; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
<h1>🔍 Diagnostic Ultra-Simple</h1>

<?php

// ÉTAPE 1: Charger Jeedom
echo "<h2>1️⃣ Chargement de Jeedom</h2>";
try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    echo "<div class='ok'>✓ Jeedom chargé avec succès</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</div>";
    die();
}

// ÉTAPE 2: Vérifier la classe ai_connector
echo "<h2>2️⃣ Vérification de la classe ai_connector</h2>";
if (class_exists('ai_connector')) {
    echo "<div class='ok'>✓ Classe ai_connector existe</div>";
} else {
    echo "<div class='error'>❌ Classe ai_connector NON trouvée!</div>";
    die();
}

// ÉTAPE 3: Chercher l'équipement IA
echo "<h2>3️⃣ Recherche de l'équipement IA</h2>";
try {
    $aiEqs = eqLogic::byType('ai_connector');
    
    if (count($aiEqs) == 0) {
        echo "<div class='error'>❌ Aucun équipement IA Connector trouvé</div>";
        echo "<div class='info'>Créez-en un: Plugins → Jeedom-AI → Ajouter</div>";
        die();
    }
    
    $aiEq = $aiEqs[0];
    echo "<div class='ok'>✓ Équipement trouvé: " . htmlspecialchars($aiEq->getName()) . " (ID: " . $aiEq->getId() . ")</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</div>";
    die();
}

// ÉTAPE 4: Vérifier la configuration
echo "<h2>4️⃣ Vérification de la configuration</h2>";
try {
    $engine = $aiEq->getConfiguration('engine', 'gemini');
    $apiKey = $aiEq->getConfiguration('apiKey', '');
    $prompt = $aiEq->getConfiguration('prompt', '');
    
    echo "<div>";
    echo "Engine: <code>" . htmlspecialchars($engine) . "</code><br>";
    echo "API Key: " . (empty($apiKey) ? "<span style='color:red;'>VIDE</span>" : "<span style='color:green;'>Configurée</span>") . "<br>";
    echo "Prompt: " . (empty($prompt) ? "<span style='color:red;'>VIDE</span>" : "<span style='color:green;'>Configuré (" . strlen($prompt) . " chars)</span>") . "<br>";
    echo "</div>";
    
    if (empty($apiKey)) {
        echo "<div class='error'>❌ Clé API manquante - Configurez-la!</div>";
    } else if (empty($prompt)) {
        echo "<div class='error'>❌ Prompt vide - Ajoutez un prompt!</div>";
    } else {
        echo "<div class='ok'>✓ Configuration OK</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// ÉTAPE 5: Vérifier les commandes
echo "<h2>5️⃣ Vérification des commandes</h2>";
try {
    $ask = $aiEq->getCmd(null, 'ask');
    $response = $aiEq->getCmd(null, 'reponse');
    
    if (is_object($ask)) {
        echo "<div class='ok'>✓ Commande 'ask' trouvée (ID: " . $ask->getId() . ")</div>";
    } else {
        echo "<div class='error'>❌ Commande 'ask' NON trouvée</div>";
    }
    
    if (is_object($response)) {
        echo "<div class='ok'>✓ Commande 'reponse' trouvée (ID: " . $response->getId() . ")</div>";
    } else {
        echo "<div class='error'>❌ Commande 'reponse' NON trouvée</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// ÉTAPE 6: Vérifier les équipements disponibles
echo "<h2>6️⃣ Vérification des équipements à contrôler</h2>";
try {
    // Vérifier si la méthode existe
    if (!method_exists('ai_connector', 'getAllEquipments')) {
        echo "<div class='error'>❌ Méthode getAllEquipments() NON TROUVÉE dans ai_connector</div>";
        echo "<div class='info'>Vérifiez le fichier: core/class/ai_connector.class.php</div>";
    } else {
        $equipments = ai_connector::getAllEquipments();
        
        if (count($equipments) == 0) {
            echo "<div class='warning'>⚠️ Aucun équipement à contrôler</div>";
            echo "<div class='info'>Créez des équipements (Lumières, Thermostats, etc.)</div>";
        } else {
            echo "<div class='ok'>✓ " . count($equipments) . " équipement(s) trouvé(s)</div>";
            
            echo "<div style='margin-top: 10px; padding: 10px; background: white; border-radius: 3px;'>";
            echo "Premiers équipements:<br>";
            foreach (array_slice($equipments, 0, 3) as $eq) {
                echo "  • " . htmlspecialchars($eq['humanName']) . " (ID: " . $eq['id'] . ")<br>";
            }
            if (count($equipments) > 3) {
                echo "  ... et " . (count($equipments) - 3) . " autres<br>";
            }
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='warning'>Stack trace: <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></div>";
}

// ÉTAPE 7: Test simple
echo "<h2>7️⃣ Test simple de l'API</h2>";
try {
    if (!empty($apiKey)) {
        echo "<div class='info'>Tentative d'appel IA...</div>";
        $testResponse = $aiEq->processMessage("Bonjour");
        
        if (empty($testResponse)) {
            echo "<div class='warning'>⚠️ Réponse vide</div>";
        } else {
            echo "<div class='ok'>✓ Réponse reçue (" . strlen($testResponse) . " chars): " . htmlspecialchars(substr($testResponse, 0, 100)) . "</div>";
        }
    } else {
        echo "<div class='warning'>⚠️ Impossible de tester - Clé API manquante</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<h2>✅ Diagnostic terminé</h2>";

?>

</body>
</html>
