<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * $Id$
 *
 * testsPlugin
 */
require_once('common/plugin/Plugin.class.php');

class testsPlugin extends Plugin {

	function testsPlugin($id) {
		$this->Plugin($id);
        $this->_addHook('site_admin_option_hook', 'siteAdminHooks', false);
        $this->_addHook('cssfile', 'cssFile', false);
	}

    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'testsPluginInfo')) {
            require_once('testsPluginInfo.class.php');
            $this->pluginInfo =& new testsPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    function siteAdminHooks($params)
    {
        $params['plugins'][] = array(
            'label' => 'tests',
            'href'  => $this->getPluginPath() . '/'
        );
    }

    function cssFile($params) {
        // Only show the stylesheet if we're actually in the tests pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }

}

?>
