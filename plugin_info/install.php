<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function ai_connector_install() {
    // Vide. Le démon est géré via l'interface du plugin.
}

function ai_connector_update() {
    // Vide. Le démon est géré via l'interface du plugin.
}

function ai_connector_remove() {
    require_once dirname(__FILE__) . '/../core/class/ai_connector.class.php';
    ai_connector::deamon_stop();
}
// Fonction de vérification des dépendances
function ai_connector_dependancy_info() {
    $return = array();
    $return['progress_file'] = dirname(__FILE__) . '/../../tmp/ai_connector_dep_in_progress';
    
    // Vérifie si l'installation est en cours
    if (file_exists($return['progress_file'])) {
        $return['state'] = 'in_progress';
    } else {
        // Vérifie si les dépendances sont présentes
        $daemonPath = dirname(__FILE__) . '/../resources/demond/ai_connector_daemon.py';
        if (file_exists($daemonPath)) {
            $return['state'] = 'ok';
        } else {
            $return['state'] = 'nok';
        }
    }
    
    $return['version'] = '1.0.0';
    return $return;
}

// Fonction de vérification du démon
function ai_connector_deamon_info() {
    $return = array();
    $return['log'] = 'ai_connector';
    $return['state'] = 'nok';
    
    try {
        $pidFile = dirname(__FILE__) . '/../../resources/demond/ai_connector.pid';
        if (file_exists($pidFile)) {
            $pid = trim(file_get_contents($pidFile));
            if (!empty($pid) && is_numeric($pid)) {
                if (function_exists('posix_kill')) {
                    if (@posix_kill($pid, 0)) {
                        $return['state'] = 'ok';
                        $return['pid'] = $pid;
                    }
                } else {
                    // Fallback pour Windows
                    $return['state'] = 'ok';
                    $return['pid'] = $pid;
                }
            }
        }
    } catch (Exception $e) {
        $return['state'] = 'nok';
    }
    
    return $return;
}