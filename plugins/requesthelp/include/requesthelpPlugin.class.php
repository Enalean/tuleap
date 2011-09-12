<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('common/plugin/Plugin.class.php');
require_once('common/system_event/SystemEvent.class.php');
require_once('RequestHelp.class.php');

class requesthelpPlugin extends Plugin {

    /**
     * Constructor
     *
     * @param Integer $id id of the plugin
     *
     * @return void
     */
    function __construct($id) {
        parent::__construct($id);
        if (extension_loaded('oci8')) {
            $this->_addHook('cssfile', 'cssFile', false);
            $this->_addHook('site_help', 'redirectToPlugin', false);
            $this->_addHook(Event::LAB_FEATURES_DEFINITION_LIST, 'lab_features_definition_list', false);
        }
    }

    /**
     * Retrieve plugin info
     *
     * @return RequestHelpPluginInfo
     */
    function getPluginInfo() {
        if (!$this->pluginInfo instanceof RequestHelpPluginInfo) {
            include_once('RequestHelpPluginInfo.class.php');
            $this->pluginInfo = new RequestHelpPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    /**
     * Launch the controller
     *
     * @return void
     */
    function process() {
        $controler = new RequestHelp();
        $controler->process();
    }

    /**
     * Set the right style sheet for RequestHelp form
     *
     * @param Array $params params of the hook
     *
     * @return void
     */
    function cssFile($params) {
        echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />'."\n";
    }

    /**
     * Redirects the hook call to plugin path
     *
     * @return void
     */
    function redirectToPlugin() {
        $c = new RequestHelp();
        $c->redirect($this->getPluginPath().'/');
        exit();
    }

    /**
     * Retreive a param config giving its name
     *
     * @param String $name Property name
     *
     * @return String
     */
    public function getProperty($name) {
        $info =$this->getPluginInfo();
        return $info->getPropertyValueForName($name);
    }

    public function lab_features_definition_list($params) {
        $params['lab_features'][] = array(
            'title'       => $GLOBALS['Language']->getText('plugin_requesthelp', 'requesthelp_lab_feature_title'),
            'description' => $GLOBALS['Language']->getText('plugin_requesthelp', 'requesthelp_lab_feature_description'),
            'image'       => $this->getPluginPath().'/lab_feature.png',
        );
    }

}
?>