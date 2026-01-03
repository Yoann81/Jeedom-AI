<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

// Inclure les fonctions de dépendances et démons
require_once dirname(__FILE__) . '/../core/php/ai_connector.inc.php';

function ai_connector_install() {
}

function ai_connector_update() {
}

function ai_connector_remove() {
    require_once dirname(__FILE__) . '/../core/class/ai_connector.class.php';
    ai_connector::deamon_stop();
}

function ai_connector_dependancy_info() {
    $return = array();
    
    $progressFile = dirname(__FILE__) . '/../tmp/ai_connector_dep_in_progress';
    if (file_exists($progressFile)) {
        $return['state'] = 'in_progress';
    } else {
        $daemonPath = dirname(__FILE__) . '/../resources/demond/ai_connector_daemon.py';
        if (file_exists($daemonPath)) {
            $return['state'] = 'ok';
        } else {
            $return['state'] = 'nok';
        }
    }
    
    return $return;
}

function ai_connector_deamon_info() {
    $return = array();
    $return['log'] = 'ai_connector_daemon';
    $return['state'] = 'nok';
    $return['launchable'] = 'ok';
    
    $pidFile = '/tmp/jeedom/ai_connector/daemon.pid';
    if (file_exists($pidFile)) {
        $pid = trim(file_get_contents($pidFile));
        if (!empty($pid) && is_numeric($pid)) {
            if (function_exists('posix_getpgid')) {
                if (@posix_getpgid($pid) !== false) {
                    $return['state'] = 'ok';
                }
            } else {
                $return['state'] = 'ok';
            }
        }
    }
    
    return $return;
}
