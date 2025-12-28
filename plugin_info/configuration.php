<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*/

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}
?>

<form class="form-horizontal">
    <fieldset>
        <legend><i class="fas fa-microphone"></i> {{Configuration Vocale Globale}}</legend>
        <div class="form-group">
            <label class="col-sm-3 control-label">{{Activer l'écoute vocale}}</label>
            <div class="col-sm-3">
                <label class="checkbox-inline">
                    <input type="checkbox" class="configKey" data-l1key="voice_enable" /> {{Activer}}
                </label>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-3 control-label">{{Commande de retour (HP)}}</label>
            <div class="col-sm-4">
                <div class="input-group">
                    <input type="text" class="configKey form-control" data-l1key="voice_cmd_id" placeholder="{{Commande de notification}}"/>
                    <span class="input-group-btn">
                        <a class="btn btn-default bt_selectCmdExpression"><i class="fas fa-list-alt"></i></a>
                    </span>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-3 control-label">{{ID Micro (Index)}}</label>
            <div class="col-sm-2">
                <input type="text" class="configKey form-control" data-l1key="voice_device_id" placeholder="1"/>
            </div>
        </div>
    </fieldset>
</form>

<script>
    /* Gestion du bouton de sélection de commande sur la page de config globale */
    $('.bt_selectCmdExpression').off('click').on('click', function () {
        var _this = $(this);
        jeedom.cmd.getSelectModal({
            title: "{{Choisir une commande de message}}",
            resPanel: {
                type: 'action',
                subType: 'message'
            }
        }, function (result) {
            _this.closest('.input-group').find('input').val(result.human);
        });
    });
</script>