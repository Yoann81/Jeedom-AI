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
        <legend><i class="fas fa-cogs"></i> État du système</legend>
        <div class="form-group">
            <label class="col-lg-4 control-label">Dépendances</label>
            <div class="col-lg-8">
                <?php 
                    $depInfo = ai_connector_dependancy_info();
                    if ($depInfo['state'] === 'ok') {
                        echo '<span class="label label-success"><i class="fas fa-check"></i> OK</span>';
                    } elseif ($depInfo['state'] === 'in_progress') {
                        echo '<span class="label label-warning"><i class="fas fa-spinner fa-spin"></i> Installation en cours...</span>';
                    } else {
                        echo '<span class="label label-danger"><i class="fas fa-times"></i> Non disponible</span>';
                    }
                ?>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">Démon</label>
            <div class="col-lg-8">
                <?php 
                    $daemonInfo = ai_connector_deamon_info();
                    if ($daemonInfo['state'] === 'ok') {
                        echo '<span class="label label-success"><i class="fas fa-check"></i> Actif</span>';
                    } else {
                        echo '<span class="label label-danger"><i class="fas fa-times"></i> Inactif</span>';
                    }
                ?>
            </div>
        </div>
    </fieldset>
</form>

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

<script>
$(document).ready(function() {
    // Remplir les blocs verts de Jeedom via les div standards
    
    // Chercher et remplir le bloc Dépendances
    $.ajax({
        type: 'POST',
        url: '/plugins/ai_connector/core/ajax/ai_connector.ajax.php',
        data: {
            action: 'dependancy_info'
        },
        dataType: 'json',
        success: function(data) {
            console.log('dependancy_info response:', data);
            var html = '';
            var state = data.result ? data.result.state : data.state;
            if (state === 'ok') {
                html = '<span class="label label-success"><i class="fas fa-check"></i> OK</span>';
            } else if (state === 'in_progress') {
                html = '<span class="label label-info"><i class="fas fa-spinner fa-spin"></i> Installation en cours...</span>';
            } else {
                html = '<span class="label label-danger"><i class="fas fa-times"></i> Non disponible</span>';
            }
            
            // Chercher le div parent du bloc Dépendances
            var depBlocs = $('h4:contains("Dépendances")').closest('fieldset').find('.form-group');
            if (depBlocs.length > 0) {
                depBlocs.first().find('div[class*="col-"]').eq(-1).html(html);
            }
        }
    });
    
    // Chercher et remplir le bloc Démon
    $.ajax({
        type: 'POST',
        url: '/plugins/ai_connector/core/ajax/ai_connector.ajax.php',
        data: {
            action: 'deamon_info'
        },
        dataType: 'json',
        success: function(data) {
            console.log('deamon_info response:', data);
            var html = '';
            var state = data.result ? data.result.state : data.state;
            if (state === 'ok') {
                html = '<span class="label label-success"><i class="fas fa-check"></i> Actif</span>';
            } else {
                html = '<span class="label label-danger"><i class="fas fa-times"></i> Inactif</span>';
            }
            
            // Chercher le div parent du bloc Démon
            var daemonBlocs = $('h4:contains("Démon")').closest('fieldset').find('.form-group');
            if (daemonBlocs.length > 0) {
                daemonBlocs.first().find('div[class*="col-"]').eq(-1).html(html);
            }
        }
    });
});
</script>