<?php
/**
 * Définitions et enregistrement automatique du plugin AI Connector
 * 
 * Ce fichier est chargé automatiquement par Jeedom lors du démarrage
 * et enregistre les chemins des fichiers du plugin pour l'autoloading.
 */

// Enregistrer les chemins pour l'autoloading Jeedom
if (!isset($GLOBALS['jeedom_plugin_paths'])) {
    $GLOBALS['jeedom_plugin_paths'] = [];
}

$pluginPath = realpath(dirname(__FILE__) . '/../..');
$GLOBALS['jeedom_plugin_paths']['ai_connector'] = [
    'class' => $pluginPath . '/core/class/',
    'cmd' => $pluginPath . '/core/class/',
    'template' => $pluginPath . '/core/template/',
];

// S'assurer que la classe ai_connector est disponible
require_once $pluginPath . '/core/class/ai_connector.class.php';
