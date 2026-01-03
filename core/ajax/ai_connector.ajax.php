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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

    ajax::init();

    // Récupère tous les équipements Jeedom disponibles
    if (init('action') == 'getAllEquipments') {
        $equipments = ai_connector::getAllEquipments();
        ajax::success($equipments);
    }

    // Récupère les commandes d'un équipement spécifique
    if (init('action') == 'getEquipmentCommands') {
        $eq_id = init('eq_id');
        if (empty($eq_id)) {
            throw new Exception(__('ID équipement manquant', __FILE__));
        }
        $commands = ai_connector::getEquipmentCommands($eq_id);
        ajax::success($commands);
    }

    // Exécute une commande Jeedom
    if (init('action') == 'executeCommand') {
        $cmd_id = init('cmd_id');
        $options = json_decode(init('options', '{}'), true);
        
        if (empty($cmd_id)) {
            throw new Exception(__('ID commande manquant', __FILE__));
        }
        
        $result = ai_connector::executeJeedomCommand($cmd_id, $options);
        ajax::success($result);
    }

    // Récupère le contexte Jeedom formaté pour l'IA d'un équipement
    if (init('action') == 'getJeedomContext') {
        $eq_id = init('eq_id');
        if (empty($eq_id)) {
            throw new Exception(__('ID équipement AI manquant', __FILE__));
        }
        
        $eqLogic = eqLogic::byId($eq_id);
        if (!is_object($eqLogic) || $eqLogic->getType() !== 'ai_connector') {
            throw new Exception(__('Équipement AI non trouvé', __FILE__));
        }
        
        $context = $eqLogic->getJeedomContextForAI();
        ajax::success($context);
    }

    // Récupère la liste formatée de tous les équipements et commandes
    if (init('action') == 'getAllEquipmentsWithCommands') {
        $result = [];
        $equipments = ai_connector::getAllEquipments();
        
        foreach ($equipments as $eq) {
            $eq['commands'] = ai_connector::getEquipmentCommands($eq['id']);
            $result[] = $eq;
        }
        
        ajax::success($result);
    }

    throw new Exception(__('Aucune méthode correspondante à', __FILE__) . ' : ' . init('action'));
    /*     * *********Catch exeption*************** */
}
catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
?>
