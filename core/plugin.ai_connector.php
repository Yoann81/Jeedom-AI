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

/**
 * Vérification des dépendances du plugin ai_connector
 * Cette fonction est appelée automatiquement par Jeedom
 */
if (!function_exists('ai_connector_dependancy_info')) {
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
}

/**
 * Vérification du démon ai_connector
 * Cette fonction est appelée automatiquement par Jeedom
 */
if (!function_exists('ai_connector_deamon_info')) {
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
}

?>
