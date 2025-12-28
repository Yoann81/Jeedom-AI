<?php
/* Copyright (c) 2025 Votre Nom
 * Plugin AI Multi-Connect pour Jeedom
 */

require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class ai_connector extends eqLogic {

    /**
     * post_save : S'exécute lors de la sauvegarde de la CONFIGURATION DU PLUGIN
     */
    public static function post_save() {
        $enable = config::byKey('voice_enable', 'ai_connector', 0);
        if ($enable == 1) {
            log::add('ai_connector', 'info', 'Redémarrage du démon suite à modification de configuration');
            self::deamon_start();
        } else {
            self::deamon_stop();
        }
    }

    /**
     * postSave : Appelé lors de la sauvegarde de l'ÉQUIPEMENT (Robot Gemini, etc.)
     */
    public function postSave() {
        // Commande Action : Poser une question
        $ask = $this->getCmd(null, 'ask');
        if (!is_object($ask)) {
            $ask = new ai_connectorCmd();
            $ask->setLogicalId('ask');
            $ask->setIsVisible(1);
            $ask->setDisplay('generic_type', 'MESSAGE_CONFIRMATION');
        }
        $ask->setName(__('Poser une question', __FILE__));
        $ask->setType('action');
        $ask->setSubType('message'); 
        $ask->setEqLogic_id($this->getId());
        $ask->save();

        // Commande Info : Stocker la réponse
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
     * GESTION DU DÉMON
     */
    public static function deamon_info() {
        $return = array();
        $return['log'] = 'ai_connector_daemon'; // Nom du log sans .log
        
        // Vérification des prérequis pour l'affichage
        $cmdId = config::byKey('voice_cmd_id', 'ai_connector');
        if ($cmdId == '') {
            $return['launchable'] = 'nok';
            $return['launchable_message'] = "{{ID de destination non configuré}}";
        } else {
            $return['launchable'] = 'ok';
        }
        
        // État du process
        $state = exec("pgrep -f ai_connector_daemon.py");
        $return['state'] = ($state != "") ? 'ok' : 'nok';
        
        return $return;
    }

    public static function deamon_start() {
        self::deamon_stop();
        $apikey = config::byKey('api', 'core');
        $cmdId = config::byKey('voice_cmd_id', 'ai_connector');
        
        if ($cmdId == '') {
            log::add('ai_connector', 'error', 'Le démon ne peut pas démarrer : ID de destination non configuré.');
            return;
        }

        $deviceId = config::byKey('voice_device_id', 'ai_connector', '1');
        $path = realpath(dirname(__FILE__) . '/../../resources/demond/ai_connector_daemon.py');
        $log = log::getPathName('ai_connector_daemon');
        
        // Lancement en tâche de fond
        $cmd = "python3 $path $apikey $cmdId $deviceId >> $log 2>&1 &";
        exec($cmd);
    }

    public static function deamon_stop() {
        // Tue le démon proprement
        exec("ps aux | grep ai_connector_daemon.py | grep -v grep | awk '{print $2}' | xargs kill -9 > /dev/null 2>&1");
    }
    /**
     * EXÉCUTION DES COMMANDES
     */
    public function execute($_logicalId, $_options = array()) {
        if ($_logicalId == 'ask') {
            $prompt = $_options['message'];
            $engine = $this->getConfiguration('engine');
            $apiKey = $this->getConfiguration('apiKey');
            $model  = $this->getConfiguration('model');

            if (empty($apiKey)) {
                $err = "Erreur : Clé API absente pour $engine";
                log::add('ai_connector', 'error', $err);
                return $err;
            }

            switch ($engine) {
                case 'gemini':
                    $result = $this->callGemini($prompt, $apiKey, $model);
                    break;
                case 'openai':
                    $result = $this->callOpenAI($prompt, $apiKey, $model);
                    break;
                case 'mistral':
                    $result = $this->callMistral($prompt, $apiKey, $model);
                    break;
                default:
                    $result = "Moteur IA [$engine] non supporté.";
                    break;
            }

            $this->checkAndUpdateCmd('reponse', $result);
            return $result;
        }
    }

    /**
     * MOTEURS IA (APPELS API)
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
}

/**
 * Classe des commandes du plugin
 */
class ai_connectorCmd extends cmd {
    public function execute($_options = array()) {
        $eqLogic = $this->getEqLogic();
        if (!is_object($eqLogic)) {
            throw new Exception(__('Commande non liée à un équipement', __FILE__));
        }
        return $eqLogic->execute($this->getLogicalId(), $_options);
    }
}