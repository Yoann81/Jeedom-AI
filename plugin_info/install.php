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
