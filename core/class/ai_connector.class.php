<?php
/* Copyright (c) 2025 Votre Nom
 * Plugin AI Multi-Connect pour Jeedom
 */

class ai_connector extends eqLogic {

    public static function deamon_info() {
        $return = array();
        // Changer le nom du log peut aider à rafraîchir l'état dans Jeedom
        $return['log'] = __CLASS__ . '_daemon'; 
        $return['launchable'] = 'ok';
        
        $pids = exec("pgrep -f ai_connector_daemon.py");
        if (!empty($pids)) {
            $return['state'] = 'ok';
        } else {
            $return['state'] = 'nok';
        }
        
        return $return;
    }

    public static function deamon_start() {
        log::add('ai_connector', 'info', 'Débogage final : Lancement en avant-plan...');
        self::deamon_stop();

        $path = realpath(dirname(__FILE__) . '/../../resources/demond/ai_connector_daemon.py');
        if (!file_exists($path)) {
            log::add('ai_connector', 'error', 'Script Python introuvable : ' . $path);
            return;
        }

        // Commande de base pour tester
        $cmd = "python3 " . escapeshellarg($path) . " --help";
        log::add('ai_connector', 'debug', "Commande de test : " . $cmd);

        // Exécution en avant-plan avec capture de la sortie
        $output = array();
        $return_var = 0;
        exec($cmd . ' 2>&1', $output, $return_var);

        if ($return_var !== 0) {
            log::add('ai_connector', 'error', 'ÉCHEC de l\'exécution de Python. Code de retour : ' . $return_var);
            log::add('ai_connector', 'error', 'Sortie de la commande : ' . implode("\n", $output));
        } else {
            log::add('ai_connector', 'info', 'SUCCÈS de l\'exécution de Python. Le script a répondu.');
            log::add('ai_connector', 'info', 'Sortie de la commande : ' . implode("\n", $output));
        }

        log::add('ai_connector', 'info', 'Débogage final terminé. Si vous ne voyez pas de "SUCCÈS" ou "ÉCHEC" ci-dessus, le problème est très profond.');
    }

    public static function deamon_stop() {
        log::add('ai_connector', 'info', 'Commande d\'arrêt du processus du démon envoyée.');
        exec("pkill -f ai_connector_daemon.py");
    }

    public static function daemon_loop() {
        // Vide pour éviter tout problème avec un cron résiduel.
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

        if (empty($apiKey)) {
            $errorMsg = "La clé API n'est pas configurée pour l'équipement " . $this->getHumanName(true);
            log::add('ai_connector', 'error', $errorMsg);
            return $errorMsg;
        }

        switch ($engine) {
            case 'openai':
                return $this->callOpenAI($prompt, $apiKey, $model);
            case 'mistral':
                return $this->callMistral($prompt, $apiKey, $model);
            case 'gemini':
            default:
                return $this->callGemini($prompt, $apiKey, $model);
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
        $response = $this->sendCurl($url, $data, $headers);
        return $response['choices'][0]['message']['content'] ?? "Erreur OpenAI";
    }

    private function callMistral($prompt, $apiKey, $model) {
        $modelId = (empty($model)) ? 'mistral-small-latest' : $model;
        $url = "https://api.mistral.ai/v1/chat/completions";
        $data = ["model" => $modelId, "messages" => [["role" => "user", "content" => $prompt]]];
        $headers = ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey];
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
        if (empty($prompt)) {
            return; // Ne rien faire si le message est vide
        }

        // Appeler la nouvelle méthode publique sur l'équipement parent
        $response = $eqLogic->processMessage($prompt);

        // Mettre à jour la commande 'reponse' avec le résultat
        $eqLogic->checkAndUpdateCmd('reponse', $response);
    }
}