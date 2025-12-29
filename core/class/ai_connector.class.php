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

        $eqLogics = eqLogic::byType('ai_connector', true);
        if (empty($eqLogics)) {
            log::add('ai_connector', 'error', "Aucun équipement 'AI Connector' activé trouvé.");
            return;
        }
        $config_source = $eqLogics[0];

        $apikey = config::byKey('api', 'core');
        $cmdId = $config_source->getConfiguration('voice_cmd_id');
        $deviceId = $config_source->getConfiguration('voice_device_id', '1');
        
        if (empty($cmdId)) {
            log::add('ai_connector', 'error', 'ID de commande de retour (HP) non configuré.');
            return;
        }

        $path = realpath(dirname(__FILE__) . '/../../resources/demond/ai_connector_daemon.py');
        if (!file_exists($path)) {
            log::add('ai_connector', 'error', 'Script Python introuvable : ' . $path);
            return;
        }

        $log_file = dirname(__FILE__) . '/../../../../log/ai_connector_daemon';

        touch($log_file);
        chown($log_file, 'www-data');

        $cmd = "nohup python3 " . escapeshellarg($path) . " --apikey " . escapeshellarg($apikey) . " --cmd_id " . escapeshellarg($cmdId) . " --device_id " . escapeshellarg($deviceId);
        $full_cmd = $cmd . " >> " . $log_file . " 2>&1 &";
        
        log::add('ai_connector', 'debug', "Commande de lancement : " . $full_cmd);
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

    public function processMessage($prompt) {
        $engine = $this->getConfiguration('engine', 'gemini');
        $apiKey = $this->getConfiguration('apiKey');
        $model = $this->getConfiguration('model');
        $defaultPrompt = $this->getConfiguration('prompt', ''); // Get the default prompt from configuration

        if (empty($apiKey)) {
            $errorMsg = "La clé API n'est pas configurée pour l'équipement " . $this->getHumanName(true);
            log::add('ai_connector', 'error', $errorMsg);
            return $errorMsg;
        }

        // Use the provided prompt, or fallback to the default prompt if the provided one is empty
        $finalPrompt = !empty($prompt) ? $prompt : $defaultPrompt;

        if (empty($finalPrompt)) {
            $errorMsg = "Aucun prompt n'est fourni et aucun prompt par défaut n'est configuré pour l'équipement " . $this->getHumanName(true);
            log::add('ai_connector', 'error', $errorMsg);
            return $errorMsg;
        }

        switch ($engine) {
            case 'openai':
                return $this->callOpenAI($finalPrompt, $apiKey, $model);
            case 'mistral':
                return $this->callMistral($finalPrompt, $apiKey, $model);
            case 'gemini':
            default:
                return $this->callGemini($finalPrompt, $apiKey, $model);
        }
    }

    /**
     * MOTEURS IA (APPELS API) - Maintenant BIEN DANS LA CLASSE
     */
    private function callGemini($prompt, $apiKey, $model) {
        if (empty($prompt)) return "Le message est vide.";
        $modelId = (empty($model)) ? 'gemini-1.5-flash' : str_replace(' ', '-', trim($model));
        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . $modelId . ":generateContent?key=" . $apiKey;
        $data = ["contents" => [["parts" => [["text" => $prompt]]]]];
        log::add('ai_connector', 'debug', 'Sending to Gemini URL: ' . $url . ' with data: ' . json_encode($data)); // Add this line
        $response = $this->sendCurl($url, $data);
        
        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            return $response['candidates'][0]['content']['parts'][0]['text'];
        }
        return "Erreur Gemini : " . ($response['error']['message'] ?? "Structure inconnue");
    }

    private function callOpenAI($prompt, $apiKey, $model) {
        $modelId = (empty($model)) ? 'gpt-4o-mini' : $model;
        $url = "https://api.openai.com/v1/chat/completions";
        $data = [
            "model" => $modelId,
            "messages" => [
                ["role" => "system", "content" => "Assistant domotique Jeedom."],
                ["role" => "user", "content" => $prompt]
            ]
        ];
        $headers = ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey];
        log::add('ai_connector', 'debug', 'Sending to OpenAI URL: ' . $url . ' with data: ' . json_encode($data)); // Add this line
        $response = $this->sendCurl($url, $data, $headers);
        return $response['choices'][0]['message']['content'] ?? "Erreur OpenAI";
    }

    private function callMistral($prompt, $apiKey, $model) {
        $modelId = (empty($model)) ? 'mistral-small-latest' : $model;
        $url = "https://api.mistral.ai/v1/chat/completions";
        $data = ["model" => $modelId, "messages" => [["role" => "user", "content" => $prompt]]];
        $headers = ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey];
        log::add('ai_connector', 'debug', 'Sending to Mistral URL: ' . $url . ' with data: ' . json_encode($data)); // Add this line
        $response = $this->sendCurl($url, $data, $headers);
        return $response['choices'][0]['message']['content'] ?? "Erreur Mistral";
    }

    private function sendCurl($url, $data, $headers = ['Content-Type: application/json']) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $rawResponse = curl_exec($ch);
        log::add('ai_connector', 'debug', 'Raw AI API response: ' . $rawResponse); // Add this line for logging
        curl_close($ch);
        return json_decode($rawResponse, true);
    }
} // <--- L'accolade de fin de classe doit être ICI

class ai_connectorCmd extends cmd {
    public function execute($_options = array()) {
        $eqLogic = $this->getEqLogic();
        if (!is_object($eqLogic)) {
            throw new Exception(__('Commande non liée à un équipement', __FILE__));
        }
        
        $prompt = $_options['message'] ?? '';

        // Appeler la nouvelle méthode publique sur l'équipement parent
        $response = $eqLogic->processMessage($prompt);

        // Mettre à jour la commande 'reponse' avec le résultat
        $eqLogic->checkAndUpdateCmd('reponse', $response);
    }
}