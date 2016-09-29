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
        $this->addHook('site_admin_option_hook', 'siteAdminHooks', true);
        $this->addHook('cssfile', 'cssFile', false);
        $this->addHook(Event::IS_IN_SITEADMIN);
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

    function siteAdminHooks($hook, $params)
    {
        $site_url  = $this->getPluginPath() . '/';
        $site_name = $GLOBALS['Language']->getText('plugin_pluginsadministration', 'descriptor_name');
        echo '<li><a href="', $site_url, '">', $site_name, '</a></li>';
    }

    function cssFile($params) {
        // Only show the stylesheet if we're actually in the PluginsAdministration pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }

    function process() {
        require_once('PluginsAdministration.class.php');
        $controler = new PluginsAdministration();
        $controler->process();
    }
}
?>
