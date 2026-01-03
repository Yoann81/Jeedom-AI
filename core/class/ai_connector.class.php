<?php
/* Copyright (c) 2025 Votre Nom
 * Plugin AI Multi-Connect pour Jeedom
 */

// Charge les fonctions de vérification des dépendances et du démon
require_once dirname(__FILE__) . '/../php/ai_connector.inc.php';

class ai_connector extends eqLogic {

    /**
     * Convertit une valeur en string de manière sécurisée
     * Gère les cas : array, null, object, etc.
     */
    private static function toSafeString($value) {
        if ($value === null) {
            return '';
        }
        if (is_array($value)) {
            return json_encode($value);
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_object($value)) {
            return get_class($value);
        }
        return (string)$value;
    }

    public static function deamon_info() {
        return ai_connector_deamon_info();
    }

    public static function dependancy_info() {
        return ai_connector_dependancy_info();
    }

    public static function deamon_start() {
        self::deamon_stop();
        log::add('ai_connector', 'info', 'Lancement du démon Python en arrière-plan.');

        $listeningEqLogic = null;
        $activeListeners = [];
        foreach (eqLogic::byType('ai_connector', true) as $eqLogic) {
            if ($eqLogic->getConfiguration('voice_enable', 0) == 1) {
                $activeListeners[] = $eqLogic;
            }
        }

        if (empty($activeListeners)) {
            log::add('ai_connector', 'error', "Aucun équipement 'AI Connector' activé avec l'écoute vocale activée trouvé. Le démon ne peut pas démarrer.");
            return;
        } elseif (count($activeListeners) > 1) {
            log::add('ai_connector', 'warning', "Plusieurs équipements 'AI Connector' ont l'écoute vocale activée. Seul le premier trouvé ('" . $activeListeners[0]->getHumanName() . "') sera utilisé par le démon.");
        }
        $listeningEqLogic = $activeListeners[0];

        $apikey = config::byKey('api', 'core');
        $askCmd = $listeningEqLogic->getCmd(null, 'ask');
        if (!is_object($askCmd)) {
            log::add('ai_connector', 'error', 'Commande "Poser une question" introuvable pour l\'équipement d\'écoute (' . $listeningEqLogic->getHumanName() . ').');
            return;
        }
        $cmdId = $askCmd->getId();
        $deviceId = $listeningEqLogic->getConfiguration('voice_device_id', '1');
        $porcupineEnable = $listeningEqLogic->getConfiguration('porcupine_enable', 0);
        $porcupineAccessKey = $listeningEqLogic->getConfiguration('porcupine_access_key', '');
        $porcupineWakewordNames = $listeningEqLogic->getConfiguration('porcupine_wakeword_names', '');
        $porcupineMode = $listeningEqLogic->getConfiguration('porcupine_mode', 'default');
        $porcupineCustomFile = $listeningEqLogic->getConfiguration('porcupine_custom_file', '');
        $sttEngine = $listeningEqLogic->getConfiguration('stt_engine', 'whisper');
        $googleApiKey = $listeningEqLogic->getConfiguration('google_api_key', '');
        $sttLanguage = $listeningEqLogic->getConfiguration('stt_language', 'fr-FR');
        $porcupineSensitivity = $listeningEqLogic->getConfiguration('porcupine_sensitivity', '0.95');
        
        // DEBUG
        log::add('ai_connector', 'debug', 'DEBUG Picovoice: porcupine_enable=' . $porcupineEnable . ', porcupine_sensitivity=' . $porcupineSensitivity . ', mode=' . $porcupineMode . ', access_key=' . (empty($porcupineAccessKey) ? 'EMPTY' : 'SET'));
        
        $path = realpath(dirname(__FILE__) . '/../../resources/demond/ai_connector_daemon.py');
        if (!file_exists($path)) {
            log::add('ai_connector', 'error', 'Script Python introuvable : ' . $path);
            return;
        }

        $log_file = dirname(__FILE__) . '/../../../../log/ai_connector_daemon';

        touch($log_file);
        chown($log_file, 'www-data');

        $cmd = "nohup /var/www/html/plugins/ai_connector/resources/python_venv/bin/python3 " . escapeshellarg($path) . " --apikey " . escapeshellarg($apikey) . " --cmd_id " . escapeshellarg($cmdId) . " --device_id " . escapeshellarg($deviceId) . " --stt_engine " . escapeshellarg($sttEngine) . " --google_api_key " . escapeshellarg($googleApiKey) . " --stt_language " . escapeshellarg($sttLanguage);
        
        if ($porcupineEnable) {
            if (empty($porcupineAccessKey)) {
                log::add('ai_connector', 'error', 'Cle Picovoice manquante pour ' . $listeningEqLogic->getHumanName());
                // Optionnel : on peut forcer le mode sans wakeword ici
            } else {
                $cmd .= " --porcupine_enable 1";
                $cmd .= " --porcupine_access_key " . escapeshellarg($porcupineAccessKey);
                $cmd .= " --porcupine_sensitivity " . escapeshellarg($porcupineSensitivity);
                $cmd .= " --porcupine_mode " . escapeshellarg($porcupineMode);
                
                // Mode personnalisé avec fichier
                if ($porcupineMode === 'custom' && !empty($porcupineCustomFile)) {
                    $cmd .= " --porcupine_custom_file " . escapeshellarg($porcupineCustomFile);
                    log::add('ai_connector', 'info', 'Mode Picovoice personnalisé activé avec fichier: ' . $porcupineCustomFile);
                } elseif ($porcupineMode === 'default') {
                    // Mode par défaut
                    if (!empty($porcupineWakewordNames)) {
                        $cmd .= " --porcupine_wakeword_names " . escapeshellarg($porcupineWakewordNames);
                    }
                    log::add('ai_connector', 'info', 'Mode Picovoice par défaut activé');
                }
            }
        }
        }
        
        $full_cmd = $cmd . " >> " . $log_file . " 2>&1 &";
        
        log::add('ai_connector', 'debug', "Commande de lancement : " . $full_cmd);
        log::add('ai_connector', 'debug', "COMMANDE REELLE ENVOYEE : " . $full_cmd);
        exec($full_cmd);
        
        sleep(2);
        
        $pids = exec("pgrep -f ai_connector_daemon.py");
        if (empty($pids)) {
            log::add('ai_connector', 'error', 'Échec critique: Le processus Python est introuvable après le lancement. Vérifiez le log du démon.');
            $log_content = file_exists($log_file) ? file_get_contents($log_file) : "Fichier de log introuvable.";
            log::add('ai_connector', 'error', 'Contenu du log du démon : ' . $log_content);
        } else {
            log::add('ai_connector', 'info', 'Succès ! Le démon est lancé. PID(s) : ' . $pids);
        }
    }

    public static function deamon_stop() {
        log::add('ai_connector', 'info', 'Commande d\'arrêt du processus du démon envoyée.');
        // On récupère le PID dans le fichier pour un arrêt plus ciblé
        $pid_file = '/tmp/jeedom/ai_connector/daemon.pid';
        if (file_exists($pid_file)) {
            $pid = trim(file_get_contents($pid_file));
            exec("kill " . $pid);
            unlink($pid_file);
        }
        // Fallback au cas où le fichier pid n'existerait pas
        exec("pkill -f ai_connector_daemon.py");
    }

    public static function daemon_loop() {
        // Vide.
    }

    public function postSave() {
        $ask = $this->getCmd(null, 'ask');
        if (!is_object($ask)) {
            $ask = new ai_connectorCmd();
            $ask->setLogicalId('ask');
            $ask->setIsVisible(1);
        }
        $ask->setName(__('Poser une question', __FILE__));
        $ask->setType('action');
        $ask->setSubType('message'); 
        $ask->setEqLogic_id($this->getId());
        $ask->save();

        $response = $this->getCmd(null, 'reponse');
        if (!is_object($response)) {
            $response = new ai_connectorCmd();
            $response->setLogicalId('reponse');
            $response->setIsVisible(1);
        }
        $response->setName(__('Dernière réponse', __FILE__));
        $response->setType('info');
        $response->setSubType('string');
        $response->setEqLogic_id($this->getId());
        $response->save();
    }

    /**
     * Récupère tous les équipements Jeedom disponibles
     * @return array Liste des équipements avec leurs informations
     */
    public static function getAllEquipments() {
        $equipments = [];
        foreach (eqLogic::all() as $eq) {
            try {
                // Récupérer le type de manière safe
                $type = 'unknown';
                if (method_exists($eq, 'getType')) {
                    $type = $eq->getType();
                }
                
                // Exclure les équipements IA
                if ($type === 'ai_connector') continue;
                
                // Récupérer l'humanName de manière safe
                $humanName = 'Unknown';
                if (method_exists($eq, 'getHumanName')) {
                    $humanName = $eq->getHumanName();
                } elseif (method_exists($eq, 'getName')) {
                    $humanName = $eq->getName();
                }
                
                // S'assurer que les valeurs sont des strings ou des nombres, pas null/array
                $status = $eq->getStatus();
                
                $equipments[] = [
                    'id' => (int)$eq->getId(),
                    'name' => (string)$eq->getName(),
                    'logicalId' => (string)($eq->getLogicalId() ?? ''),
                    'object_id' => (int)($eq->getObject_id() ?? 0),
                    'type' => (string)$type,
                    'humanName' => (string)$humanName,
                    'isEnable' => (bool)$eq->getIsEnable(),
                    'status' => self::toSafeString($status)
                ];
            } catch (Exception $e) {
                // Ignorer les équipements problématiques et continuer
                continue;
            }
        }
        return $equipments;
    }

    /**
     * Récupère les commandes d'un équipement
     * @param $eq_id ID de l'équipement
     * @return array Liste des commandes
     */
    public static function getEquipmentCommands($eq_id) {
        $eqLogic = eqLogic::byId($eq_id);
        if (!is_object($eqLogic)) {
            return [];
        }

        $commands = [];
        try {
            foreach ($eqLogic->getCmd() as $cmd) {
                try {
                    // Récupérer les infos de manière safe
                    $cmdType = 'info';
                    if (method_exists($cmd, 'getType')) {
                        $cmdType = $cmd->getType();
                    }
                    
                    $cmdValue = '';
                    if ($cmdType === 'info' && method_exists($cmd, 'getLastValue')) {
                        $lastVal = $cmd->getLastValue();
                        $cmdValue = $lastVal !== null ? (string)$lastVal : '';
                    }
                    
                    $commands[] = [
                        'id' => (int)$cmd->getId(),
                        'name' => (string)$cmd->getName(),
                        'logicalId' => (string)($cmd->getLogicalId() ?? ''),
                        'type' => (string)$cmdType,
                        'subType' => (string)(method_exists($cmd, 'getSubType') ? ($cmd->getSubType() ?? '') : ''),
                        'isVisible' => (bool)(method_exists($cmd, 'getIsVisible') ? $cmd->getIsVisible() : true),
                        'value' => (string)$cmdValue,
                        'unit' => (string)(method_exists($cmd, 'getUnite') ? ($cmd->getUnite() ?? '') : ''),
                        'minValue' => method_exists($cmd, 'getMinValue') ? $cmd->getMinValue() : null,
                        'maxValue' => method_exists($cmd, 'getMaxValue') ? $cmd->getMaxValue() : null
                    ];
                } catch (Exception $e) {
                    // Ignorer les commandes problématiques et continuer
                    continue;
                }
            }
        } catch (Exception $e) {
            // Silencieusement retourner les commandes récupérées jusqu'à présent
        }
        
        return $commands;
    }

    /**
     * Exécute une commande Jeedom
     * @param $cmd_id ID de la commande
     * @param $options Options d'exécution (peut contenir 'value' pour les sliders)
     * @return string Résultat de l'exécution
     */
    public static function executeJeedomCommand($cmd_id, $options = []) {
        $cmd = cmd::byId($cmd_id);
        if (!is_object($cmd)) {
            $msg = "Erreur: Commande ID " . $cmd_id . " non trouvée";
            log::add('ai_connector', 'error', $msg);
            return $msg;
        }
        
        try {
            // Vérifier que la commande est visible et activée
            if (!$cmd->getIsVisible()) {
                $msg = "Erreur: Commande " . $cmd->getName() . " n'est pas visible";
                log::add('ai_connector', 'warning', $msg);
                return $msg;
            }
            
            $eqLogic = $cmd->getEqLogic();
            if (!is_object($eqLogic) || !$eqLogic->getIsEnable()) {
                $msg = "Erreur: Équipement de la commande n'existe pas ou n'est pas activé";
                log::add('ai_connector', 'error', $msg);
                return $msg;
            }
            
            // Exécuter la commande avec les options
            if ($cmd->getType() === 'action') {
                // Pour les actions, passer les options directement
                $cmd->execute($options);
                $msg = "✓ Action exécutée: " . $cmd->getName();
                log::add('ai_connector', 'info', $msg);
                return $msg;
            } else {
                $msg = "Erreur: Seules les commandes d'action peuvent être exécutées (type détecté: " . $cmd->getType() . ")";
                log::add('ai_connector', 'warning', $msg);
                return $msg;
            }
            
        } catch (Exception $e) {
            $msg = "Erreur lors de l'exécution de " . $cmd->getName() . ": " . $e->getMessage();
            log::add('ai_connector', 'error', $msg);
            return $msg;
        }
    }

    /**
     * Formate les équipements et commandes pour le prompt de l'IA
     * @return string Format texte pour le prompt
     */
    public function getJeedomContextForAI() {
        $context = "\n\n=== ÉQUIPEMENTS JEEDOM DISPONIBLES ===\n";
        $equipments = self::getAllEquipments();
        
        if (empty($equipments)) {
            $context .= "Aucun équipement disponible.\n";
        } else {
            foreach ($equipments as $eq) {
                if (!$eq['isEnable']) continue;
                
                $context .= "\n📱 " . $eq['humanName'] . " (ID: " . $eq['id'] . ")\n";
                $context .= "   Type: " . $eq['type'] . "\n";
                
                $commands = self::getEquipmentCommands($eq['id']);
                if (!empty($commands)) {
                    $context .= "   Commandes:\n";
                    foreach ($commands as $cmd) {
                        if (!$cmd['isVisible']) continue;
                        
                        $cmdDesc = "     • " . $cmd['name'] . " (ID: " . $cmd['id'] . ")";
                        
                        // Ajouter des infos selon le type
                        if ($cmd['type'] === 'action') {
                            $cmdDesc .= " [ACTION";
                            if ($cmd['subType'] === 'slider' || $cmd['subType'] === 'color') {
                                $cmdDesc .= " - Paramétrable";
                                if ($cmd['minValue'] !== null && $cmd['maxValue'] !== null) {
                                    $cmdDesc .= " (" . $cmd['minValue'] . "-" . $cmd['maxValue'] . ")";
                                }
                            }
                            $cmdDesc .= "]";
                        } elseif ($cmd['type'] === 'info') {
                            $cmdDesc .= " [INFO";
                            if (!empty($cmd['value'])) {
                                $cmdDesc .= " = " . htmlspecialchars($cmd['value']);
                                if (!empty($cmd['unit'])) {
                                    $cmdDesc .= " " . $cmd['unit'];
                                }
                            }
                            $cmdDesc .= "]";
                        }
                        
                        $context .= $cmdDesc . "\n";
                    }
                }
            }
        }
        
        $context .= "\n=== INSTRUCTIONS POUR L'IA ===\n";
        $context .= "Tu peux VOIR et CONTRÔLER les équipements Jeedom listés ci-dessus.\n";
        $context .= "Pour exécuter une action:\n";
        $context .= "  1. Identifie l'équipement et la commande correspondante\n";
        $context .= "  2. Utilise le format: [EXEC_COMMAND: id_de_la_commande]\n";
        $context .= "  3. Pour les commandes paramétrables (slider, color), ajoute la valeur: [EXEC_COMMAND: id value=75]\n";
        $context .= "  4. Confirme l'action à l'utilisateur\n";
        $context .= "Ne confonds pas les actions (commandes ID) avec les informations (lectures).\n";
        
        return $context;
    }

    public function processMessage($userMessage) {
        log::add('ai_connector', 'debug', '>>> DÉBUT processMessage');
        
        $engine = $this->getConfiguration('engine', 'gemini');
        $apiKey = $this->getConfiguration('apiKey');
        $model = $this->getConfiguration('model');
        $systemPrompt = $this->getConfiguration('prompt', '');
        $includeEquipments = $this->getConfiguration('include_equipments', 1);

        log::add('ai_connector', 'debug', 'Engine: ' . $engine . ', Model: ' . $model . ', Include equipments: ' . $includeEquipments);

        if (empty($apiKey)) {
            $errorMsg = "❌ La clé API n'est pas configurée pour l'équipement " . $this->getHumanName(true);
            log::add('ai_connector', 'error', $errorMsg);
            return $errorMsg;
        }

        if (empty($systemPrompt) && empty($userMessage)) {
            $errorMsg = "❌ Aucun prompt système ni message utilisateur fourni";
            log::add('ai_connector', 'error', $errorMsg);
            return $errorMsg;
        }

        // Ajouter le contexte des équipements au prompt si activé
        $finalSystemPrompt = $systemPrompt;
        if ($includeEquipments) {
            log::add('ai_connector', 'debug', 'Ajout du contexte des équipements');
            $finalSystemPrompt .= $this->getJeedomContextForAI();
        }

        log::add('ai_connector', 'debug', 'Appel du moteur IA: ' . $engine);
        // Traiter les commandes d'exécution potentielles
        $response = $this->callAIEngine($finalSystemPrompt, $userMessage, $apiKey, $model, $engine);
        log::add('ai_connector', 'debug', 'Réponse brute du moteur IA: ' . substr($response, 0, 200));
        
        // Vérifier et exécuter les commandes au format [EXEC_COMMAND: id]
        $response = $this->processAICommands($response);
        log::add('ai_connector', 'debug', 'Réponse après traitement des commandes: ' . substr($response, 0, 200));
        
        log::add('ai_connector', 'debug', '<<< FIN processMessage');
        return $response;
    }

    /**
     * Appelle le moteur IA approprié
     */
    private function callAIEngine($systemPrompt, $userMessage, $apiKey, $model, $engine) {
        switch ($engine) {
            case 'openai':
                return $this->callOpenAI($systemPrompt, $userMessage, $apiKey, $model);
            case 'mistral':
                return $this->callMistral($systemPrompt, $userMessage, $apiKey, $model);
            case 'gemini':
            default:
                return $this->callGemini($systemPrompt, $userMessage, $apiKey, $model);
        }
    }

    /**
     * Traite les commandes générées par l'IA au format [EXEC_COMMAND: id] ou [EXEC_COMMAND: id value=X]
     */
    private function processAICommands($response) {
        // Motif pour capturer [EXEC_COMMAND: id] ou [EXEC_COMMAND: id value=X]
        $pattern = '/\[EXEC_COMMAND:\s*(\d+)(?:\s+(.+?))?\]/i';
        $matches = [];
        
        if (preg_match_all($pattern, $response, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $cmd_id = $match[1];
                $params = !empty($match[2]) ? $match[2] : '';
                
                log::add('ai_connector', 'info', 'Exécution de la commande Jeedom ID: ' . $cmd_id . ' avec paramètres: ' . $params);
                
                // Parser les paramètres (ex: "value=75" ou "value=on")
                $options = [];
                if (!empty($params)) {
                    // Simple parser pour value=X
                    if (preg_match('/value\s*=\s*(["\']?)(.+?)\1(?:\s|$)/i', $params, $paramMatch)) {
                        $options['value'] = trim($paramMatch[2]);
                    }
                }
                
                $result = self::executeJeedomCommand($cmd_id, $options);
                log::add('ai_connector', 'info', 'Résultat: ' . $result);
            }
            // Supprimer les balises de commande de la réponse visible
            $response = preg_replace($pattern, '', $response);
        }
        
        return trim($response);
    }

    /**
     * MOTEURS IA (APPELS API) - Maintenant BIEN DANS LA CLASSE
     */
    private function callGemini($systemPrompt, $userMessage, $apiKey, $model) {
        $finalPrompt = '';
        if (!empty($systemPrompt)) {
            $finalPrompt .= $systemPrompt;
        }
        if (!empty($userMessage)) {
            if (!empty($finalPrompt)) {
                $finalPrompt .= "\n\n"; // Add a separator if both are present
            }
            $finalPrompt .= $userMessage;
        }

        if (empty($finalPrompt)) return "Le message est vide."; // Should not happen with previous check

        $modelId = (empty($model)) ? 'gemini-1.5-flash' : str_replace(' ', '-', trim($model));
        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . $modelId . ":generateContent?key=" . $apiKey;
        $data = ["contents" => [["parts" => [["text" => $finalPrompt]]]]];
        log::add('ai_connector', 'debug', 'Sending to Gemini URL: ' . $url . ' with data: ' . json_encode($data)); // Add this line
        $response = $this->sendCurl($url, $data);
        log::add('ai_connector', 'debug', 'Gemini response received: ' . json_encode($response));
        
        // Vérifier les erreurs d'API (quota, authentification, etc.)
        if (isset($response['error'])) {
            $errorMessage = "Erreur API Gemini: " . json_encode($response['error']);
            log::add('ai_connector', 'error', $errorMessage);
            return "Erreur Gemini : " . ($response['error']['message'] ?? json_encode($response['error']));
        }
        
        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            return $response['candidates'][0]['content']['parts'][0]['text'];
        }
        
        // Si la structure n'est pas celle attendue, logger l'erreur
        $errorMessage = "Structure de réponse Gemini inattendue: " . json_encode($response);
        log::add('ai_connector', 'error', $errorMessage);
        return "Erreur Gemini : Structure inconnue";
    }

    private function callOpenAI($systemPrompt, $userMessage, $apiKey, $model) {
        $modelId = (empty($model)) ? 'gpt-4o-mini' : $model;
        $url = "https://api.openai.com/v1/chat/completions";
        
        $messages = [];
        if (!empty($systemPrompt)) {
            $messages[] = ["role" => "system", "content" => $systemPrompt];
        }
        if (!empty($userMessage)) {
            $messages[] = ["role" => "user", "content" => $userMessage];
        }
        
        $data = [
            "model" => $modelId,
            "messages" => $messages
        ];
        $headers = ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey];
        log::add('ai_connector', 'debug', 'Sending to OpenAI URL: ' . $url . ' with data: ' . json_encode($data));
        $response = $this->sendCurl($url, $data, $headers);
        
        // Vérifier les erreurs d'API
        if (isset($response['error'])) {
            $errorMessage = "Erreur API OpenAI: " . json_encode($response['error']);
            log::add('ai_connector', 'error', $errorMessage);
            return "Erreur OpenAI : " . ($response['error']['message'] ?? json_encode($response['error']));
        }
        
        return $response['choices'][0]['message']['content'] ?? "Erreur OpenAI: Structure inconnue";
    }

    private function callMistral($systemPrompt, $userMessage, $apiKey, $model) {
        $modelId = (empty($model)) ? 'mistral-small-latest' : $model;
        $url = "https://api.mistral.ai/v1/chat/completions";
        
        $messages = [];
        if (!empty($systemPrompt)) {
            $messages[] = ["role" => "system", "content" => $systemPrompt];
        }
        if (!empty($userMessage)) {
            $messages[] = ["role" => "user", "content" => $userMessage];
        }
        
        $data = ["model" => $modelId, "messages" => $messages];
        $headers = ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey];
        log::add('ai_connector', 'debug', 'Sending to Mistral URL: ' . $url . ' with data: ' . json_encode($data));
        $response = $this->sendCurl($url, $data, $headers);
        
        // Vérifier les erreurs d'API
        if (isset($response['error'])) {
            $errorMessage = "Erreur API Mistral: " . json_encode($response['error']);
            log::add('ai_connector', 'error', $errorMessage);
            return "Erreur Mistral : " . ($response['error']['message'] ?? json_encode($response['error']));
        }
        
        return $response['choices'][0]['message']['content'] ?? "Erreur Mistral: Structure inconnue";
    }

    private function sendCurl($url, $data, $headers = ['Content-Type: application/json']) {
        log::add('ai_connector', 'debug', 'CURL: Envoi de la requête à ' . $url);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Augmenté à 60s pour les APIs lentes (Gemini, OpenAI, etc)
        
        $rawResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        
        if ($curlError) {
            log::add('ai_connector', 'error', 'CURL Error: ' . $curlError);
            curl_close($ch);
            return [];
        }
        
        log::add('ai_connector', 'debug', 'CURL HTTP Code: ' . $httpCode);
        log::add('ai_connector', 'debug', 'CURL Raw response: ' . substr($rawResponse, 0, 500));
        
        curl_close($ch);
        return json_decode($rawResponse, true);
    }

    private function findAudioDevice() {
        // Recherche dynamique du périphérique audio comme dans le démon Python
        $defaultDevice = 'hw:0,0';
        $aplayOutput = shell_exec('aplay -l 2>/dev/null');
        if ($aplayOutput) {
            $lines = explode("\n", $aplayOutput);
            foreach ($lines as $line) {
                // Recherche de Headphones ou bcm2835
                if (preg_match('/card (\d+):.*?(Headphones|bcm2835).*?, device (\d+):/', $line, $matches)) {
                    return 'hw:' . $matches[1] . ',' . $matches[3];
                }
            }
        }
        // Si non trouvé, utiliser le périphérique par défaut
        return $defaultDevice;
    }

    public function speakWithGoogleTTS($text, $apiKey, $language, $voice, $audioDevice = 'hw:0,0') {
        try {
            if (empty($apiKey) || empty($text)) {
                log::add('ai_connector', 'warning', 'TTS: Clé API ou texte vide');
                return;
            }

            // Recherche dynamique du périphérique audio
            $audioDevice = $this->findAudioDevice();

            // Tronquer le texte à 4000 caractères pour respecter la limite Google TTS
            $text = substr($text, 0, 4000);

            $url = "https://texttospeech.googleapis.com/v1/text:synthesize?key=" . $apiKey;
            $data = [
                "input" => ["text" => $text],
                "voice" => [
                    "languageCode" => $language ?: "fr-FR",
                    "name" => $voice ?: "fr-FR-Neural2-A"
                ],
                "audioConfig" => [
                    "audioEncoding" => "MP3"
                ]
            ];

            $response = $this->sendCurl($url, $data);
            
            // Vérifier les erreurs d'API TTS
            if (isset($response['error'])) {
                log::add('ai_connector', 'error', 'Erreur API Google TTS: ' . json_encode($response['error']));
                return;
            }
            
            if (isset($response['audioContent'])) {
                $audioData = base64_decode($response['audioContent']);
                $audioFile = '/tmp/ai_tts.mp3';
                $bytesWritten = file_put_contents($audioFile, $audioData);
                if ($bytesWritten === false) {
                    log::add('ai_connector', 'error', 'TTS: Échec écriture fichier audio');
                    return;
                }
                
                // Jouer l'audio avec mpg123
                if (!file_exists('/usr/bin/mpg123')) {
                    log::add('ai_connector', 'error', 'TTS: mpg123 non trouvé');
                    return;
                }
                
                $cmd = "/usr/bin/mpg123 -a " . escapeshellarg($audioDevice) . " " . escapeshellarg($audioFile) . " > /dev/null 2>&1 &";
                exec($cmd);
                log::add('ai_connector', 'debug', 'TTS: Audio en cours de lecture');
            } else {
                log::add('ai_connector', 'error', 'Erreur réponse TTS Google: structure inconnue');
            }
        } catch (Exception $e) {
            log::add('ai_connector', 'error', 'TTS Exception: ' . $e->getMessage());
        }
    }
} // <--- L'accolade de fin de classe doit être ICI

class ai_connectorCmd extends cmd {
    public function execute($_options = array()) {
        $eqLogic = $this->getEqLogic();
        if (!is_object($eqLogic)) {
            throw new Exception(__('Commande non liée à un équipement', __FILE__));
        }
        
        $prompt = $_options['message'] ?? '';
        
        if (empty($prompt)) {
            log::add('ai_connector', 'warning', 'Prompt vide reçu');
            return;
        }
        
        // Génère un hash unique du prompt + timestamp pour éviter les doublons
        $prompt_hash = md5($prompt);
        $cache_key = 'ai_connector_last_hash_' . $eqLogic->getId();
        $last_hash = cache::byKey($cache_key)->getValue('');
        $last_time = cache::byKey($cache_key . '_time')->getValue(0);
        $current_time = time();
        
        // Si c'est le MÊME hash dans les 5 secondes, c'est probablement un doublon
        if ($prompt_hash === $last_hash && ($current_time - $last_time) < 5) {
            log::add('ai_connector', 'debug', 'Prompt dupliqué ignoré (même dans les 5s): ' . substr($prompt, 0, 50));
            return;
        }
        
        // Mettre à jour le cache avec le nouveau hash
        cache::set($cache_key, $prompt_hash, 3600);
        cache::set($cache_key . '_time', $current_time, 3600);
        
        log::add('ai_connector', 'info', 'Début traitement prompt: ' . substr($prompt, 0, 100));

        try {
            // Appeler la méthode publique sur l'équipement parent
            $response = $eqLogic->processMessage($prompt);
            
            if (empty($response)) {
                log::add('ai_connector', 'warning', 'Réponse IA vide');
                $response = 'Désolé, je n\'ai pas pu traiter votre demande.';
            }
            
            log::add('ai_connector', 'info', 'Réponse IA: ' . substr($response, 0, 200));

            // Mettre à jour la commande 'reponse' avec le résultat
            $eqLogic->checkAndUpdateCmd('reponse', $response);
            log::add('ai_connector', 'debug', 'Commande réponse mise à jour');

            // Si TTS activé, parler la réponse
            if ($eqLogic->getConfiguration('tts_enable', 0) == 1) {
                $googleApiKey = $eqLogic->getConfiguration('google_api_key');
                $ttsLanguage = $eqLogic->getConfiguration('tts_language', 'fr-FR');
                $ttsVoice = $eqLogic->getConfiguration('tts_voice', 'fr-FR-Neural2-A');
                $ttsAudioDevice = $eqLogic->getConfiguration('tts_audio_device', 'hw:0,0');
                
                if (!empty($googleApiKey)) {
                    log::add('ai_connector', 'debug', 'Démarrage TTS');
                    $eqLogic->speakWithGoogleTTS($response, $googleApiKey, $ttsLanguage, $ttsVoice, $ttsAudioDevice);
                } else {
                    log::add('ai_connector', 'warning', 'TTS activé mais pas de clé Google API');
                }
            }
        } catch (Exception $e) {
            $errorMsg = 'Erreur pendant le traitement: ' . $e->getMessage();
            log::add('ai_connector', 'error', $errorMsg);
            $eqLogic->checkAndUpdateCmd('reponse', $errorMsg);
        }
    }
}