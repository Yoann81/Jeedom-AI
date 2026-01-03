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

    </fieldset>
</form>