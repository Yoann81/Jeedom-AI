<?php
/**
 * Outils de diagnostic et test pour AI Connector
 */

// Charger Jeedom
$jeedom_loaded = false;
$core_inc_path = dirname(__FILE__) . '/../../../core/php/core.inc.php';

if (file_exists($core_inc_path)) {
    require_once $core_inc_path;
    $jeedom_loaded = true;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Connector - Outils de diagnostic</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .content {
            padding: 30px;
        }
        
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .tool-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .tool-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }
        
        .tool-card.recommended {
            border: 2px solid #4CAF50;
            background: #f8fff8;
        }
        
        .tool-card.recommended .badge {
            background: #4CAF50;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .tool-card h3 {
            margin-bottom: 10px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .tool-card p {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .tool-card .features {
            list-style: none;
            margin-bottom: 15px;
            font-size: 13px;
        }
        
        .tool-card .features li {
            padding: 4px 0;
            color: #555;
        }
        
        .tool-card .features li:before {
            content: "✓ ";
            color: #4CAF50;
            font-weight: bold;
            margin-right: 5px;
        }
        
        .tool-card a {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .tool-card a:hover {
            background: #764ba2;
            text-decoration: none;
            color: white;
        }
        
        .info-section {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .info-section h3 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .info-section p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 8px;
        }
        
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .status.success {
            background: #d4edda;
            color: #155724;
        }
        
        .status.error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .icon {
            font-size: 20px;
            width: 20px;
        }
        
        .footer {
            background: #f5f5f5;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #999;
            border-top: 1px solid #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 AI Connector - Outils de diagnostic</h1>
            <p>Diagnostiquez et testez votre installation AI Connector</p>
        </div>
        
        <div class="content">
            <?php if ($jeedom_loaded): ?>
                <div class="status success">✓ Jeedom chargé avec succès</div>
            <?php else: ?>
                <div class="status error">✗ Erreur: Jeedom non trouvé</div>
            <?php endif; ?>
            
            <h2 style="margin: 30px 0 20px; color: #333;">Outils disponibles</h2>
            
            <div class="tools-grid">
                <!-- Diagnostic Ultra-Simple (Recommandé) -->
                <div class="tool-card recommended">
                    <div class="badge">⭐ RECOMMANDÉ</div>
                    <h3>
                        <span class="icon">🚀</span>
                        Diagnostic Ultra-Simple
                    </h3>
                    <p>Le meilleur point de départ pour diagnostiquer votre installation.</p>
                    <ul class="features">
                        <li>7 tests automatisés</li>
                        <li>Vérification Jeedom</li>
                        <li>Vérification équipements IA</li>
                        <li>Test API IA</li>
                        <li>Résultats clairs et actionables</li>
                    </ul>
                    <a href="diagnostic_ultra_simple.php" target="_blank">
                        Lancer le diagnostic →
                    </a>
                </div>
                
                <!-- Débogage Détaillé -->
                <div class="tool-card">
                    <h3>
                        <span class="icon">🐛</span>
                        Débogage détaillé
                    </h3>
                    <p>Pour investiguer les erreurs et récupérer les stack traces complètes.</p>
                    <ul class="features">
                        <li>Traces d'erreurs complètes</li>
                        <li>Vérification équipements</li>
                        <li>Test méthode getAllEquipments</li>
                        <li>Test API IA</li>
                        <li>Détails fichiers</li>
                    </ul>
                    <a href="debug.php" target="_blank">
                        Accéder au débogage →
                    </a>
                </div>
                
                <!-- Diagnostic Complet -->
                <div class="tool-card">
                    <h3>
                        <span class="icon">⚙️</span>
                        Diagnostic complet
                    </h3>
                    <p>Diagnostic exhaustif avec toutes les informations système.</p>
                    <ul class="features">
                        <li>Infos système détaillées</li>
                        <li>Tous les équipements</li>
                        <li>Toutes les commandes</li>
                        <li>Configuration PHP</li>
                        <li>Permissions</li>
                    </ul>
                    <a href="diagnostic.php" target="_blank">
                        Lancer le diagnostic complet →
                    </a>
                </div>
                
                <!-- Test API Endpoints -->
                <div class="tool-card">
                    <h3>
                        <span class="icon">📡</span>
                        Test des endpoints AJAX
                    </h3>
                    <p>Teste tous les endpoints AJAX de l'API.</p>
                    <ul class="features">
                        <li>getAllEquipments()</li>
                        <li>getEquipmentCommands()</li>
                        <li>executeCommand()</li>
                        <li>getJeedomContext()</li>
                    </ul>
                    <a href="test_ajax_endpoints.php" target="_blank">
                        Tester les endpoints →
                    </a>
                </div>
                
                <!-- Test Simple IA -->
                <div class="tool-card">
                    <h3>
                        <span class="icon">🤖</span>
                        Test API IA
                    </h3>
                    <p>Teste la connexion directe à l'API IA (Gemini/OpenAI/Mistral).</p>
                    <ul class="features">
                        <li>Test authentification</li>
                        <li>Envoi requête test</li>
                        <li>Réception réponse</li>
                        <li>Mesure de latence</li>
                    </ul>
                    <a href="test_simple_ai.php" target="_blank">
                        Tester l'API IA →
                    </a>
                </div>
                
                <!-- Test Équipements -->
                <div class="tool-card">
                    <h3>
                        <span class="icon">📦</span>
                        Test équipements
                    </h3>
                    <p>Teste la détection et récupération de vos équipements.</p>
                    <ul class="features">
                        <li>Liste équipements</li>
                        <li>Détails équipements</li>
                        <li>Récupération commandes</li>
                        <li>Validation format JSON</li>
                    </ul>
                    <a href="test_equipments.php" target="_blank">
                        Tester les équipements →
                    </a>
                </div>
            </div>
            
            <!-- Information Section -->
            <div class="info-section">
                <h3>📖 Guide d'utilisation</h3>
                <p>
                    <strong>Première fois?</strong> Commencez par le <strong>Diagnostic Ultra-Simple</strong>.
                    Il affichera un rapport clair en 7 étapes.
                </p>
                <p>
                    <strong>Erreurs?</strong> Utilisez le <strong>Débogage détaillé</strong> pour voir les stack traces complètes.
                </p>
                <p>
                    <strong>Configuration?</strong> Allez dans <strong>Plugins → Configuration → AI Connector</strong>.
                </p>
                <p>
                    <strong>Documentation complète?</strong> Consultez le dossier <code>docs/</code> ou lisez <strong>README.md</strong>.
                </p>
            </div>
            
            <!-- Documentation Links -->
            <div class="info-section">
                <h3>📚 Documentation</h3>
                <p>
                    📖 <a href="../../docs/README.md" target="_blank" style="color: #667eea;">Documentation complète</a><br>
                    🚀 <a href="../../docs/01_DEMARRAGE_RAPIDE.md" target="_blank" style="color: #667eea;">Démarrage rapide (30 min)</a><br>
                    🐛 <a href="../../docs/05_DEBOGAGE.md" target="_blank" style="color: #667eea;">Guide dépannage</a><br>
                    ❓ <a href="../../docs/09_FAQ.md" target="_blank" style="color: #667eea;">FAQ (30 questions)</a>
                </p>
            </div>
        </div>
        
        <div class="footer">
            AI Connector © 2026 | <a href="https://github.com/Yoann81/Jeedom-AI" target="_blank" style="color: #667eea;">GitHub</a>
        </div>
    </div>
</body>
</html>
