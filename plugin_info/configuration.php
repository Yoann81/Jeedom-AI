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
            <label class="col-lg-4 control-label">{{Gestion du démon}}</label>
            <div class="col-lg-8">
                <span class="label" id="deamon_status" style="font-size:1em;"></span>
                <a class="btn btn-success deamon_start" style="position:relative;top:2px;"><i class="fa fa-play"></i> {{Lancer/Relancer}}</a>
            </div>
        </div>
    </fieldset>
</form>