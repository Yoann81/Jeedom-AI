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
 * Vérification des dépendances du plugin
 */
function ai_connector_dependancy_info() {
    $return = array();
    $return['progress_file'] = dirname(__FILE__) . '/../../tmp/ai_connector_dep_in_progress';
    
    // Vérifie si l'installation est en cours
    if (file_exists($return['progress_file'])) {
        $return['state'] = 'in_progress';
    } else {
        // Vérifie si les dépendances Python sont présentes
        $daemonPath = dirname(__FILE__) . '/../../resources/demond/ai_connector_daemon.py';
        if (file_exists($daemonPath)) {
            $return['state'] = 'ok';
        } else {
            $return['state'] = 'nok';
        }
    }
    
    return $return;
}

/**
 * Vérification de l'état du démon
 */
function ai_connector_deamon_info() {
    $return = array();
    $return['log'] = 'ai_connector_daemon';
    $return['state'] = 'nok';
    $return['launchable'] = 'ok';
    
    try {
        $pidFile = '/tmp/jeedom/ai_connector/daemon.pid';
        if (file_exists($pidFile)) {
            $pid = trim(file_get_contents($pidFile));
            if (!empty($pid) && is_numeric($pid)) {
                if (function_exists('posix_getpgid')) {
                    if (@posix_getpgid($pid) !== false) {
                        $return['state'] = 'ok';
                    }
                } else {
                    // Fallback pour systèmes sans posix
                    $return['state'] = 'ok';
                }
            }
        }
    } catch (Exception $e) {
        $return['state'] = 'nok';
    }
    
    return $return;
}

?>
