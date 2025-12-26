<?php
/* Copyright (c) 2025 Votre Nom
 * Plugin AI Multi-Connect pour Jeedom
 */

require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class ai_connector extends eqLogic {

    /**
     * postSave : Appelé lors de la sauvegarde de l'équipement
     * Crée automatiquement les commandes Question et Réponse
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
     * execute : Cœur de l'exécution des commandes
     */
    public function execute($_options = array()) {
    // On doit récupérer l'ID de la commande qui a été réellement appelée
    // Si l'appel vient de ai_connectorCmd, l'ID est dans les options ou via une autre méthode
    // Mais le plus simple dans votre structure est de vérifier quel LogicalId est appelé
    log::add('ai_connector', 'debug', 'DEBUG EXECUTE - LogicalID de l\'objet : ' . $this->getLogicalId());
    
    $cmd = null;
    if (isset($_options['proxy'])) {
        $cmd = $_options['proxy'];
    }

    // Si on ne trouve pas l'ID de commande, on ne peut pas savoir quoi faire
    // On va donc tester le LogicalID de la commande directement
    if ($this->getLogicalId() == 'ask') {
        $prompt = $_options['message'];
        $eqLogic = $this->getEqLogic(); // On récupère l'équipement parent
        
        $engine = $eqLogic->getConfiguration('engine');
        $apiKey = $eqLogic->getConfiguration('apiKey');
        $model  = $eqLogic->getConfiguration('model');
        
        log::add('ai_connector', 'debug', 'Tentative d\'envoi vers ' . $engine . ' avec le prompt : ' . $prompt);

        if (empty($apiKey)) {
            $msg = "Erreur : Clé API absente pour l'équipement " . $eqLogic->getName();
            log::add('ai_connector', 'error', $msg);
            return $msg;
        }

        switch ($engine) {
            case 'gemini':
                $result = $this->getEqLogic()->callGemini($prompt, $apiKey, $model);
                break;
            case 'openai':
                $result = $this->getEqLogic()->callOpenAI($prompt, $apiKey, $model);
                break;
            case 'mistral':
                $result = $this->getEqLogic()->callMistral($prompt, $apiKey, $model);
                break;
            default:
                $result = "Moteur IA [$engine] non supporté.";
                break;
        }

        log::add('ai_connector', 'debug', 'Réponse reçue : ' . $result);
        $eqLogic->checkAndUpdateCmd('reponse', $result);
        
        return $result;
    }
}
    /**
     * GOOGLE GEMINI
     */
    private function callGemini($prompt, $apiKey, $model) {
        $modelId = (empty($model)) ? 'gemini-1.5-flash' : $model;
        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . $modelId . ":generateContent?key=" . $apiKey;
        
        $data = [
            "contents" => [
                ["parts" => [["text" => $prompt]]]
            ]
        ];
        
        $response = $this->sendCurl($url, $data);
        
        // Debug pour voir la structure reçue dans le log ai_connector_debug
        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            return $response['candidates'][0]['content']['parts'][0]['text'];
        } elseif (isset($response['error'])) {
            return "Erreur API : " . $response['error']['message'];
        }
        
        return "Erreur : Structure de réponse inconnue. Vérifiez vos logs.";
    }

    /**
     * OPENAI (ChatGPT)
     */
    private function callOpenAI($prompt, $apiKey, $model) {
        $modelId = (empty($model)) ? 'gpt-4o-mini' : $model;
        $url = "https://api.openai.com/v1/chat/completions";
        
        $data = [
            "model" => $modelId,
            "messages" => [["role" => "user", "content" => $prompt]]
        ];
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ];

        $response = $this->sendCurl($url, $data, $headers);
        return $response['choices'][0]['message']['content'] ?? "Erreur OpenAI : " . ($response['error']['message'] ?? json_encode($response));
    }

    /**
     * MISTRAL AI
     */
    private function callMistral($prompt, $apiKey, $model) {
        $modelId = (empty($model)) ? 'mistral-tiny' : $model;
        $url = "https://api.mistral.ai/v1/chat/completions";
        
        $data = [
            "model" => $modelId,
            "messages" => [["role" => "user", "content" => $prompt]]
        ];
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ];

        $response = $this->sendCurl($url, $data, $headers);
        return $response['choices'][0]['message']['content'] ?? "Erreur Mistral : " . ($response['error']['message'] ?? json_encode($response));
    }

    /**
     * Utilitaire CURL générique
     */
    private function sendCurl($url, $data, $headers = ['Content-Type: application/json']) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Évite de bloquer Jeedom si l'IA est lente
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Sécurité SSL
        
        $rawResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $jsonResponse = json_decode($rawResponse, true);

        if ($httpCode !== 200) {
            log::add('ai_connector', 'error', "Erreur API $url (Code $httpCode) : " . $rawResponse);
        }

        return $jsonResponse;
    }
}

/**
 * Classe des commandes du plugin
 */
class ai_connectorCmd extends cmd {
    public function execute($_options = array()) {
        // On passe la commande elle-même dans les options pour que eqLogic sache qui l'appelle
        $_options['proxy'] = $this; 
        
        // On vérifie si c'est bien la commande 'ask'
        if ($this->getLogicalId() == 'ask') {
             // On execute la logique ici ou on redirige vers l'eqLogic proprement
             return $this->getEqLogic()->execute($_options);
        }
    }
}