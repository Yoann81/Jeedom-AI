<?php
/* Copyright (c) 2025 Votre Nom
 * Plugin AI Multi-Connect pour Jeedom
 */

class ai_connector extends eqLogic {

    public static function deamon_info() {
        $return = array();
        $return['log'] = 'ai_connector_daemon';
        $return['launchable'] = 'ok';
        $return['state'] = 'nok';

        $pid_file = '/tmp/jeedom/ai_connector/daemon.pid';

        if (file_exists($pid_file)) {
            $pid = trim(file_get_contents($pid_file));
            if (posix_getpgid($pid)) { // Check if process exists
                 $return['state'] = 'ok';
            } else {
                log::add('ai_connector', 'warning', 'Fichier PID trouvé mais processus ' . $pid . ' inexistant. Nettoyage.');
                unlink($pid_file);
            }
        }
        return $return;
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
        $sttEngine = $listeningEqLogic->getConfiguration('stt_engine', 'whisper');
        $googleApiKey = $listeningEqLogic->getConfiguration('google_api_key', '');
        $sttLanguage = $listeningEqLogic->getConfiguration('stt_language', 'fr-FR');
        $porcupineSensitivity = $listeningEqLogic->getConfiguration('porcupine_sensitivity', '0.95');
        
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
                
                if (!empty($porcupineWakewordNames)) {
                    $cmd .= " --porcupine_wakeword_names " . escapeshellarg($porcupineWakewordNames);
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
            if ($eq->getType() === 'ai_connector') continue; // Exclure les équipements IA
            
            $equipments[] = [
                'id' => $eq->getId(),
                'name' => $eq->getName(),
                'logicalId' => $eq->getLogicalId(),
                'object_id' => $eq->getObject_id(),
                'type' => $eq->getType(),
                'humanName' => $eq->getHumanName(),
                'isEnable' => $eq->getIsEnable(),
                'status' => $eq->getStatus()
            ];
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
        foreach ($eqLogic->getCmd() as $cmd) {
            $commands[] = [
                'id' => $cmd->getId(),
                'name' => $cmd->getName(),
                'logicalId' => $cmd->getLogicalId(),
                'type' => $cmd->getType(),
                'subType' => $cmd->getSubType(),
                'isVisible' => $cmd->getIsVisible(),
                'value' => $cmd->execCmd(),
                'unit' => $cmd->getUnite(),
                'minValue' => $cmd->getMinValue(),
                'maxValue' => $cmd->getMaxValue()
            ];
        }
        return $commands;
    }

    /**
     * Exécute une commande Jeedom
     * @param $cmd_id ID de la commande
     * @param $options Options d'exécution
     * @return string Résultat de l'exécution
     */
    public static function executeJeedomCommand($cmd_id, $options = []) {
        $cmd = cmd::byId($cmd_id);
        if (!is_object($cmd)) {
            return "Erreur: Commande non trouvée";
        }
        
        try {
            $cmd->execute($options);
            return "Commande exécutée avec succès: " . $cmd->getName();
        } catch (Exception $e) {
            return "Erreur lors de l'exécution: " . $e->getMessage();
        }
    }

    /**
     * Formate les équipements et commandes pour le prompt de l'IA
     * @return string Format texte pour le prompt
     */
    public function getJeedomContextForAI() {
        $context = "\n\n=== ÉQUIPEMENTS JEEDOM DISPONIBLES ===\n";
        $equipments = self::getAllEquipments();
        
        foreach ($equipments as $eq) {
            if (!$eq['isEnable']) continue;
            
            $context .= "\n📱 " . $eq['humanName'] . " (ID: " . $eq['id'] . ")\n";
            $context .= "Type: " . $eq['type'] . "\n";
            
            $commands = self::getEquipmentCommands($eq['id']);
            if (!empty($commands)) {
                $context .= "Commandes:\n";
                foreach ($commands as $cmd) {
                    if (!$cmd['isVisible']) continue;
                    $context .= "  - " . $cmd['name'] . " (ID: " . $cmd['id'] . ") [" . $cmd['type'] . "/" . $cmd['subType'] . "]\n";
                    if (!empty($cmd['value'])) {
                        $context .= "    Valeur actuelle: " . $cmd['value'] . ($cmd['unit'] ? ' ' . $cmd['unit'] : '') . "\n";
                    }
                }
            }
        }
        
        $context .= "\n\n=== INSTRUCTIONS ===\n";
        $context .= "Tu peux contrôler les équipements Jeedom. Quand l'utilisateur demande quelque chose:\n";
        $context .= "1. Identifie l'équipement et la commande correspondante\n";
        $context .= "2. Utilise le format: [EXEC_COMMAND: id_commande]\n";
        $context .= "3. Confirme l'action à l'utilisateur\n";
        
        return $context;
    }

    public function processMessage($userMessage) {
        $engine = $this->getConfiguration('engine', 'gemini');
        $apiKey = $this->getConfiguration('apiKey');
        $model = $this->getConfiguration('model');
        $systemPrompt = $this->getConfiguration('prompt', ''); // Get the system prompt from equipment configuration
        $includeEquipments = $this->getConfiguration('include_equipments', 1); // Inclure équipements par défaut

        if (empty($apiKey)) {
            $errorMsg = "La clé API n'est pas configurée pour l'équipement " . $this->getHumanName(true);
            log::add('ai_connector', 'error', $errorMsg);
            return $errorMsg;
        }

        // If neither system prompt nor user message is provided, error out
        if (empty($systemPrompt) && empty($userMessage)) {
            $errorMsg = "Aucun prompt système ni message utilisateur n'est fourni pour l'équipement " . $this->getHumanName(true);
            log::add('ai_connector', 'error', $errorMsg);
            return $errorMsg;
        }

        // Ajouter le contexte des équipements au prompt si activé
        $finalSystemPrompt = $systemPrompt;
        if ($includeEquipments) {
            $finalSystemPrompt .= $this->getJeedomContextForAI();
        }

        // Traiter les commandes d'exécution potentielles
        $response = $this->callAIEngine($finalSystemPrompt, $userMessage, $apiKey, $model, $engine);
        
        // Vérifier et exécuter les commandes au format [EXEC_COMMAND: id]
        $response = $this->processAICommands($response);
        
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
     * Traite les commandes générées par l'IA au format [EXEC_COMMAND: id]
     */
    private function processAICommands($response) {
        $pattern = '/\[EXEC_COMMAND:\s*(\d+)\]/i';
        $matches = [];
        
        if (preg_match_all($pattern, $response, $matches)) {
            foreach ($matches[1] as $cmd_id) {
                log::add('ai_connector', 'info', 'Exécution de la commande Jeedom ID: ' . $cmd_id);
                $result = self::executeJeedomCommand($cmd_id);
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
        
        // Éviter les boucles : vérifier si le même prompt a été traité récemment
        // Pour les appels manuels, être moins restrictif (30 secondes au lieu de 2 pour éviter les boucles)
        $is_manual_call = !isset($_options['source']) || $_options['source'] !== 'stt_daemon';
        $timeout_seconds = $is_manual_call ? 30 : 10; // 30s pour manuel, 10s pour STT
        
        $cache_key = 'ai_connector_last_prompt_' . $eqLogic->getId();
        $last_prompt = cache::byKey($cache_key)->getValue('');
        $last_time = cache::byKey($cache_key . '_time')->getValue(0);
        $current_time = time();
        
        if ($prompt === $last_prompt && ($current_time - $last_time) < $timeout_seconds) {
            log::add('ai_connector', 'warning', 'Prompt dupliqué ignoré pour éviter la boucle (' . $timeout_seconds . 's): ' . $prompt);
            return;
        }
        
        // Mettre à jour le cache
        cache::set($cache_key, $prompt, 300); // 5 minutes
        cache::set($cache_key . '_time', $current_time, 300);
        
        log::add('ai_connector', 'info', 'Exécution commande avec prompt: ' . $prompt);

        // Appeler la nouvelle méthode publique sur l'équipement parent
        $response = $eqLogic->processMessage($prompt);
        log::add('ai_connector', 'info', 'Réponse IA: ' . $response);

        // Mettre à jour la commande 'reponse' avec le résultat
        $eqLogic->checkAndUpdateCmd('reponse', $response);
        log::add('ai_connector', 'debug', 'Commande réponse mise à jour avec: ' . substr($response, 0, 50));

        // Si TTS activé, parler la réponse
        if ($eqLogic->getConfiguration('tts_enable', 0) == 1) {
            $googleApiKey = $eqLogic->getConfiguration('google_api_key');
            $ttsLanguage = $eqLogic->getConfiguration('tts_language', 'fr-FR');
            $ttsVoice = $eqLogic->getConfiguration('tts_voice', 'fr-FR-Neural2-A');
            $ttsAudioDevice = $eqLogic->getConfiguration('tts_audio_device', 'hw:0,0');
            try {
                $eqLogic->speakWithGoogleTTS($response, $googleApiKey, $ttsLanguage, $ttsVoice, $ttsAudioDevice);
            } catch (Exception $e) {
                log::add('ai_connector', 'error', 'TTS Exception: ' . $e->getMessage());
            }
        }
    }
}