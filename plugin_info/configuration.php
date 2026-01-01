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
        <div class="form-group">
            <label class="col-lg-4 control-label">Sensibilité Picovoice (0.0 - 1.0)</label>
            <div class="col-lg-8">
                <input class="configKey form-control" data-l1key="porcupine_sensitivity" type="number" min="0" max="1" step="0.01" value="0.95" />
                <small>Augmentez pour une meilleure détection (0.95 par défaut). Diminuez si trop de faux positifs.</small>
            </div>
        </div>
    </fieldset>
</form>