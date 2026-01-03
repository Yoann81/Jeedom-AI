<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}

// Définir les fonctions AVANT que Jeedom les appelle
function ai_connector_dependancy_info() {
    $return = array();
    
    $progressFile = dirname(__FILE__) . '/../tmp/ai_connector_dep_in_progress';
    $daemonPath = dirname(__FILE__) . '/../resources/demond/ai_connector_daemon.py';
    
    // Debug logging
    error_log('ai_connector_dependancy_info called. Daemon path: ' . $daemonPath . ' exists: ' . (file_exists($daemonPath) ? 'YES' : 'NO'));
    
    if (file_exists($progressFile)) {
        $return['state'] = 'in_progress';
    } else {
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
    
    // Debug logging
    error_log('ai_connector_deamon_info called. PID file: ' . $pidFile . ' exists: ' . (file_exists($pidFile) ? 'YES' : 'NO'));
    
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

?>

<form class="form-horizontal">
    <fieldset>
        <legend><i class="fas fa-stethoscope"></i> Outils de diagnostic</legend>
        <div class="form-group">
            <label class="col-lg-4 control-label">Diagnostics</label>
            <div class="col-lg-8">
                <a href="/plugins/ai_connector/core/php/tools.php" target="_blank" class="btn btn-primary btn-sm">
                    <i class="fas fa-tools"></i> Centre d'outils
                </a>
                <a href="/plugins/ai_connector/core/php/diagnostic_ultra_simple.php" target="_blank" class="btn btn-info btn-sm">
                    <i class="fas fa-check-circle"></i> Diagnostic rapide
                </a>
                <a href="/plugins/ai_connector/core/php/debug.php" target="_blank" class="btn btn-warning btn-sm">
                    <i class="fas fa-bug"></i> Débogage
                </a>
                <br><br>
                <small style="color: #666;">
                    <strong>Centre d'outils:</strong> Page d'accueil de tous les diagnostics (recommandé)<br>
                    <strong>Diagnostic rapide:</strong> Test 7 points clés en une minute<br>
                    <strong>Débogage:</strong> Traces d'erreurs complètes
                </small>
            </div>
        </div>

    </fieldset>
</form>