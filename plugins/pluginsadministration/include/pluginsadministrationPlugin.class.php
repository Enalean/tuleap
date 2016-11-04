<?php

require_once('common/plugin/Plugin.class.php');

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 *
 *
 * PluginsAdministrationPlugin
 */
class PluginsAdministrationPlugin extends Plugin
{

    public function __construct($id)
    {
        parent::__construct($id);
        $this->addHook('cssfile', 'cssFile', false);
        $this->addHook(Event::IS_IN_SITEADMIN);
        $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);
        $this->addHook(Event::BURNING_PARROT_GET_JAVASCRIPT_FILES);
    }

    /** @see Event::IS_IN_SITEADMIN */
    public function is_in_siteadmin($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $params['is_in_siteadmin'] = true;
        }
    }

    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'PluginsAdministrationPluginInfo')) {
            require_once('PluginsAdministrationPluginInfo.class.php');
            $this->pluginInfo = new PluginsAdministrationPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    function cssFile($params) {
        // Only show the stylesheet if we're actually in the PluginsAdministration pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }

    public function burning_parrot_get_stylesheets($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $variant = $params['variant'];
            $params['stylesheets'][] = $this->getThemePath() .'/css/style-'. $variant->getName() .'.css';
        }
    }

    public function burning_parrot_get_javascript_files(array $params)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $params['javascript_files'][] = '/scripts/tuleap/manage-allowed-projects-on-resource.js';
            $params['javascript_files'][] = $this->getPluginPath() .'/scripts/pluginsadministration.js';
        }
    }

    function process() {
        require_once('PluginsAdministration.class.php');
        $controler = new PluginsAdministration();
        $controler->process();
    }
}
?>
