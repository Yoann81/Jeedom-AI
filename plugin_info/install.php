<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

// Fonction exécutée automatiquement après l'installation ou l'activation du plugin
function ai_connector_install() {
    require_once dirname(__FILE__) . '/../core/class/ai_connector.class.php';
    log::add('ai_connector', 'info', 'Fonction _install() appelée. Tentative de démarrage du démon.');
    ai_connector::deamon_start();
}

// Fonction exécutée automatiquement après la mise à jour du plugin
function ai_connector_update() {
    require_once dirname(__FILE__) . '/../core/class/ai_connector.class.php';
    log::add('ai_connector', 'info', 'Fonction _update() appelée. Tentative de redémarrage du démon.');
    ai_connector::deamon_start();
}

// Fonction exécutée automatiquement après la suppression du plugin
function ai_connector_remove() {
    require_once dirname(__FILE__) . '/../core/class/ai_connector.class.php';
    log::add('ai_connector', 'info', 'Fonction _remove() appelée. Tentative d\'arrêt du démon.');
    ai_connector::deamon_stop();
}