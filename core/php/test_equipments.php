<?php
/**
 * Interface web pour tester la récupération des équipements
 * Belle mise en page HTML avec CSS
 */

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', '0');

// Vérifier que les fonctions sont accessibles
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

// Récupérer les données
$equipments = ai_connector::getAllEquipments();
$aiEq = eqLogic::byType('ai_connector');
$context = (count($aiEq) > 0) ? $aiEq[0]->getJeedomContextForAI() : '';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Équipements - AI Connector</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 {
            color: white;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .section h2 {
            color: #667eea;
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
            margin-bottom: 20px;
            font-size: 1.8em;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-number { font-size: 2.5em; font-weight: bold; }
        .stat-label { font-size: 0.9em; opacity: 0.9; }
        
        .equipments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .equipment-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .equipment-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }
        .equipment-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        .equipment-name {
            font-weight: bold;
            font-size: 1.1em;
            color: #333;
        }
        .equipment-id {
            background: #667eea;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
        }
        .equipment-info {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 10px;
        }
        .commands-list {
            border-top: 1px solid #dee2e6;
            padding-top: 15px;
            margin-top: 15px;
        }
        .commands-count {
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        .command-item {
            display: flex;
            align-items: center;
            padding: 8px 0;
            font-size: 0.9em;
            border-bottom: 1px solid #f0f0f0;
        }
        .command-item:last-child { border-bottom: none; }
        .command-icon { margin-right: 8px; font-size: 1.2em; }
        .command-name {
            flex: 1;
            color: #333;
        }
        .command-id {
            background: #e9ecef;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            color: #666;
        }
        
        .context-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            max-height: 500px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
            color: #333;
        }
        
        .empty-equipment {
            text-align: center;
            color: #999;
            padding: 20px;
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .equipments-grid {
                grid-template-columns: 1fr;
            }
            h1 { font-size: 1.8em; }
            .section { padding: 20px; }
        }
    </style>
</head>
<body>

<div class="container">
    <h1>🔧 Test Équipements Jeedom</h1>
    
    <!-- Section 1: Équipements -->
    <div class="section">
        <h2>📊 Récupération des Équipements</h2>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($equipments); ?></div>
                <div class="stat-label">Équipements Totaux</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($equipments, fn($e) => $e['isEnable'])); ?></div>
                <div class="stat-label">Équipements Actifs</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php 
                    $totalCommands = 0;
                    foreach ($equipments as $eq) {
                        $cmds = ai_connector::getEquipmentCommands($eq['id']);
                        $totalCommands += count($cmds);
                    }
                    echo $totalCommands;
                ?></div>
                <div class="stat-label">Commandes Totales</div>
            </div>
        </div>
        
        <div class="equipments-grid">
            <?php foreach ($equipments as $eq): ?>
                <div class="equipment-card">
                    <div class="equipment-header">
                        <div class="equipment-name">📱 <?php echo htmlspecialchars($eq['humanName']); ?></div>
                        <div class="equipment-id">ID: <?php echo $eq['id']; ?></div>
                    </div>
                    <div class="equipment-info">
                        <strong>Statut:</strong> <?php echo $eq['isEnable'] ? '✅ Actif' : '❌ Inactif'; ?><br>
                        <strong>Type:</strong> <?php echo htmlspecialchars($eq['type']); ?>
                    </div>
                    
                    <?php 
                    $commands = ai_connector::getEquipmentCommands($eq['id']);
                    if (count($commands) > 0):
                    ?>
                    <div class="commands-list">
                        <div class="commands-count"><?php echo count($commands); ?> commande(s):</div>
                        <?php foreach (array_slice($commands, 0, 5) as $cmd): ?>
                            <div class="command-item">
                                <div class="command-icon"><?php echo $cmd['type'] === 'action' ? '🔘' : 'ℹ️'; ?></div>
                                <div class="command-name"><?php echo htmlspecialchars($cmd['name']); ?></div>
                                <div class="command-id"><?php echo $cmd['id']; ?></div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (count($commands) > 5): ?>
                            <div style="text-align: center; color: #999; font-size: 0.85em; margin-top: 8px;">
                                ... et <?php echo count($commands) - 5; ?> autre(s)
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-equipment">Aucune commande visible</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Section 2: Contexte IA -->
    <div class="section">
        <h2>🤖 Contexte IA Généré</h2>
        <div class="context-box"><?php echo htmlspecialchars($context); ?></div>
    </div>
    
</div>

</body>
</html>
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
