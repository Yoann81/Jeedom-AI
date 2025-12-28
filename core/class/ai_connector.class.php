<?php
/* Copyright (c) 2025 Votre Nom
 * Plugin AI Multi-Connect pour Jeedom
 */

class ai_connector extends eqLogic {

    public static function deamon_info() {
        return array('log' => 'THIS_IS_A_TEST', 'launchable' => 'ok', 'state' => 'nok', 'auto' => 0);
    }

    public static function deamon_start() {
        log::add('ai_connector', 'info', 'Lancement du démon AI Connector');
        self::deamon_stop();
        
        // Find an enabled equipment to source the configuration
        $eqLogics = eqLogic::byType('ai_connector', true);
        if (empty($eqLogics)) {
            log::add('ai_connector', 'error', "Aucun équipement 'AI Connector' activé n'a été trouvé. Le démon ne peut pas démarrer sans configuration.");
            return;
        }
        $config_source = $eqLogics[0]; // Use the first enabled one

        $apikey = config::byKey('api', 'core'); // This is the Jeedom Core API key, needed for callbacks. This is correct.
        $cmdId = $config_source->getConfiguration('voice_cmd_id');
        $deviceId = $config_source->getConfiguration('voice_device_id', '1');
        
        if ($cmdId == '') {
            log::add('ai_connector', 'error', 'Échec : ID de commande de retour (HP) non configuré sur l\'équipement ' . $config_source->getHumanName(true));
            return;
        }

        $path = realpath(dirname(__FILE__) . '/../../resources/demond/ai_connector_daemon.py');
        if (!file_exists($path)) {
            log::add('ai_connector', 'error', 'Échec : Script Python introuvable à ' . $path);
            return;
        }

        $cmd = "python3 " . $path . " " . $apikey . " " . $cmdId . " " . $deviceId . " >> " . log::getPathName('ai_connector_daemon') . " 2>&1 &";
        exec($cmd);
    }

    public static function deamon_stop() {
        log::add('ai_connector', 'info', 'Arrêt du démon AI Connector');
        exec("pgrep -f ai_connector_daemon.py | xargs kill -9 > /dev/null 2>&1");
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
        // Ici, il faudra ajouter la logique pour appeler les fonctions callIA
    }
}