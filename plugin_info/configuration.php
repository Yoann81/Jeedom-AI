<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
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

        <hr>
        <legend><i class="fas fa-cogs"></i> État du système</legend>
        
        <div class="form-group">
            <label class="col-lg-4 control-label">Dépendances</label>
            <div class="col-lg-8">
                <div id="div_dependancy">
                    <span class="label label-warning">
                        <i class="fas fa-refresh"></i> Vérification en cours...
                    </span>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label class="col-lg-4 control-label">Démons</label>
            <div class="col-lg-8">
                <div id="div_daemon">
                    <span class="label label-warning">
                        <i class="fas fa-refresh"></i> Vérification en cours...
                    </span>
                </div>
            </div>
        </div>

    </fieldset>
</form>

<script>
$(document).ready(function() {
    // Vérifier les dépendances via AJAX
    $.ajax({
        url: '/plugins/ai_connector/core/ajax/ai_connector.ajax.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'dependancy_info'
        },
        success: function(data) {
            if (data.state === 'ok') {
                $('#div_dependancy').html('<span class="label label-success"><i class="fas fa-check"></i> OK</span>');
            } else if (data.state === 'in_progress') {
                $('#div_dependancy').html('<span class="label label-info"><i class="fas fa-cog fa-spin"></i> Installation en cours...</span>');
            } else {
                $('#div_dependancy').html('<span class="label label-danger"><i class="fas fa-times"></i> Erreur</span>');
            }
        },
        error: function() {
            $('#div_dependancy').html('<span class="label label-danger"><i class="fas fa-times"></i> Erreur de vérification</span>');
        }
    });

    // Vérifier les démons via AJAX
    $.ajax({
        url: '/plugins/ai_connector/core/ajax/ai_connector.ajax.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'deamon_info'
        },
        success: function(data) {
            if (data.state === 'ok') {
                $('#div_daemon').html('<span class="label label-success"><i class="fas fa-check"></i> Démon actif</span>');
            } else if (data.state === 'nok') {
                $('#div_daemon').html('<span class="label label-danger"><i class="fas fa-times"></i> Démon arrêté</span>');
            } else {
                $('#div_daemon').html('<span class="label label-warning"><i class="fas fa-question"></i> Statut inconnu</span>');
            }
        },
        error: function() {
            $('#div_daemon').html('<span class="label label-warning"><i class="fas fa-question"></i> Vérification impossible</span>');
        }
    });
});
</script>