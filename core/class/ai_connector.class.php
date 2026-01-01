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

    public function processMessage($userMessage) {
        $engine = $this->getConfiguration('engine', 'gemini');
        $apiKey = $this->getConfiguration('apiKey');
        $model = $this->getConfiguration('model');
        $systemPrompt = $this->getConfiguration('prompt', ''); // Get the system prompt from equipment configuration

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
        
        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            return $response['candidates'][0]['content']['parts'][0]['text'];
        }
        return "Erreur Gemini : " . ($response['error']['message'] ?? "Structure inconnue");
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
        log::add('ai_connector', 'debug', 'Sending to OpenAI URL: ' . $url . ' with data: ' . json_encode($data)); // Add this line
        $response = $this->sendCurl($url, $data, $headers);
        return $response['choices'][0]['message']['content'] ?? "Erreur OpenAI";
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
        log::add('ai_connector', 'debug', 'Sending to Mistral URL: ' . $url . ' with data: ' . json_encode($data)); // Add this line
        $response = $this->sendCurl($url, $data, $headers);
        return $response['choices'][0]['message']['content'] ?? "Erreur Mistral";
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

    private function speakWithGoogleTTS($text, $apiKey, $language, $voice, $audioDevice = 'hw:0,0') {
        try {
            log::add('ai_connector', 'debug', 'TTS: Démarrage génération audio, apiKey présent: ' . (!empty($apiKey) ? 'oui' : 'non') . ', texte longueur: ' . strlen($text));
            if (empty($apiKey) || empty($text)) {
                log::add('ai_connector', 'warning', 'TTS: Clé API ou texte vide');
                return;
            }

            // Recherche dynamique du périphérique audio comme pour la notification
            $audioDevice = $this->findAudioDevice();
            log::add('ai_connector', 'debug', 'TTS: Périphérique audio détecté: ' . $audioDevice);

        // Tronquer le texte à 4000 caractères pour respecter la limite Google TTS
        $text = substr($text, 0, 4000);
        log::add('ai_connector', 'info', 'TTS: Génération audio pour texte tronqué: ' . substr($text, 0, 50) . '...');

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
        if (isset($response['audioContent'])) {
            $audioData = base64_decode($response['audioContent']);
            $audioFile = '/tmp/ai_tts.mp3';
            $bytesWritten = file_put_contents($audioFile, $audioData);
            if ($bytesWritten === false) {
                log::add('ai_connector', 'error', 'TTS: Échec écriture fichier audio');
                return;
            }
            log::add('ai_connector', 'info', 'TTS: Audio généré, fichier: ' . $audioFile . ', taille: ' . $bytesWritten . ' bytes');
            // Play the audio
            if (!file_exists('/usr/bin/mpg123')) {
                log::add('ai_connector', 'error', 'TTS: mpg123 non trouvé à /usr/bin/mpg123');
                return;
            }
            $cmd = "/usr/bin/mpg123 -a " . escapeshellarg($audioDevice) . " " . escapeshellarg($audioFile) . " > /dev/null 2>&1 &";
            log::add('ai_connector', 'debug', 'TTS: Commande de lecture: ' . $cmd);
            exec($cmd);
            log::add('ai_connector', 'debug', 'TTS: Commande mpg123 lancée en arrière-plan');
        } else {
            log::add('ai_connector', 'error', 'Erreur TTS Google: ' . json_encode($response));
        }
        } catch (Exception $e) {
            log::add('ai_connector', 'error', 'Exception TTS: ' . $e->getMessage());
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
            log::add('ai_connector', 'debug', 'TTS activé, génération audio pour la réponse');
            $googleApiKey = $eqLogic->getConfiguration('google_api_key');
            $ttsLanguage = $eqLogic->getConfiguration('tts_language', 'fr-FR');
            $ttsVoice = $eqLogic->getConfiguration('tts_voice', 'fr-FR-Neural2-A');
            $ttsAudioDevice = $eqLogic->getConfiguration('tts_audio_device', 'hw:0,0');
            $eqLogic->speakWithGoogleTTS($response, $googleApiKey, $ttsLanguage, $ttsVoice, $ttsAudioDevice);
        } else {
            log::add('ai_connector', 'debug', 'TTS désactivé');
        }
    }
}