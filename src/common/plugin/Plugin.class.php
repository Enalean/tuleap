<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('PluginInfo.class.php');

require_once('common/collection/Map.class.php');
require_once('PluginManager.class.php');
/**
 * Plugin
 */
class Plugin implements PFO_Plugin {
    
    var $id;
    var $pluginInfo;
    var $hooks;
    protected $_scope;

    /** @var bool */
    private $is_custom = false;
    
    const SCOPE_SYSTEM  = 0;
    const SCOPE_PROJECT = 1;
    const SCOPE_USER    = 2;
    
    /**
     * @var bool True if the plugin should be disabled for all projects on installation
     *
     * Usefull only for plugins with scope == SCOPE_PROJECT
     */
    public $isRestrictedByDefault = false;
    
    /**
     * @var array List of allowed projects
     */
    protected $allowedForProject = array();
    
    public function Plugin($id = -1) {
        $this->id            = $id;
        $this->hooks         = new Map();
        
        $this->_scope = Plugin::SCOPE_SYSTEM;
    }

    /**
     * Callback called when the plugin is loaded
     *
     * @return void
     */
    public function loaded() {
    }

    public function isAllowed($group_id) {
        if(!isset($this->allowedForProject[$group_id])) {
            $this->allowedForProject[$group_id] = PluginManager::instance()->isPluginAllowedForProject($this, $group_id);
        }
        return $this->allowedForProject[$group_id];
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'PluginInfo')) {
            $this->pluginInfo = new PluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    public function getHooks() {
        return $this->hooks->getKeys();
    }
    
    public function getHooksAndCallbacks() {
        return $this->hooks->getValues();
    }
    
    public function addHook($hook, $callback = null, $recallHook = false) {
        $value = array();
        $value['hook']       = $hook;
        $value['callback']   = $callback ? $callback : $hook;
        $value['recallHook'] = $recallHook;
        $this->hooks->put($hook, $value);
    }
    
    /**
     * @deprecated
     * @see addHook()
     */
    protected function _addHook($hook, $callback = null, $recallHook = false) {
        return $this->addHook($hook, $callback, $recallHook);
    }

    public function removeHook($hook) {
        $this->hooks->removeKey($hook);
    }
    
    public function CallHook($hook, $param) {
    }
    
    public function getScope() {
        return $this->_scope;
    }

    public function setScope($s) {
        $this->_scope = $s;
    }

    public function getPluginEtcRoot() {
        return $GLOBALS['sys_custompluginsroot'] . '/' . $this->getName() .'/etc';
    }
    
    public function _getPluginPath() {
        $trace = debug_backtrace();
        trigger_error("Plugin->_getPluginPath() is deprecated. Please use Plugin->getPluginPath() instead in ". $trace[0]['file'] ." at line ". $trace[0]['line'], E_USER_WARNING);
        return $this->getPluginPath();
    }

    /**
     * Return plugin's URL path from the server root
     *
     * Example: /plugins/docman
     *
     * @return String
     */
    public function getPluginPath() {
        $pm = $this->_getPluginManager();
        if (isset($GLOBALS['sys_pluginspath']))
            $path = $GLOBALS['sys_pluginspath'];
        else $path=""; 
        if ($pm->pluginIsCustom($this)) {
            $path = $GLOBALS['sys_custompluginspath'];
        }
        return $path .'/'. $this->getName();
    }

    public function _getThemePath() {
        $trace = debug_backtrace();
        trigger_error("Plugin->_getThemePath() is deprecated. Please use Plugin->getThemePath() instead in ". $trace[0]['file'] ." at line ". $trace[0]['line'], E_USER_WARNING);
        return $this->getThemePath();
    }
    
    public function getThemePath() {
        if (!isset($GLOBALS['sys_user_theme'])) {
            return null;
        }
        
        $pluginName = $this->getName();
        
        $paths  = array($GLOBALS['sys_custompluginspath'], $GLOBALS['sys_pluginspath']);
        $roots  = array($GLOBALS['sys_custompluginsroot'], $GLOBALS['sys_pluginsroot']);
        $dir    = '/'. $pluginName .'/www/themes/';
        $dirs   = array($dir.$GLOBALS['sys_user_theme'], $dir.'default');
        $dir    = '/'. $pluginName .'/themes/';
        $themes = array($dir.$GLOBALS['sys_user_theme'], $dir.'default');
        $found = false;
        while (!$found && (list($kd, $dir) = each($dirs))) {
            reset($roots);
            while (!$found && (list($kr, $root) = each($roots))) {
                if (is_dir($root.$dir)) {
                    $found = $paths[$kr].$themes[$kd];
                }
            }
        }
        return $found;
    }

    /**
     * Returns plugin's path on the server file system
     *
     * Example: /usr/share/codendi/plugins/docman
     *
     * @return String
     */
    public function getFilesystemPath() {
        $pm = $this->_getPluginManager();
        if ($pm->pluginIsCustom($this)) {
            $path = $GLOBALS['sys_custompluginsroot'];
        } else {
            $path = $GLOBALS['sys_pluginsroot'];
        }
        if ($path[strlen($path) -1 ] != '/') {
            $path .= '/';
        }
        return $path . $this->getName();
    }

    /**
     * @return string the short name of the plugin (docman, tracker, …)
     */
    public function getName() {
        return $this->_getPluginManager()->getNameForPlugin($this);
    }

    /**
     * Wrapper for PluginManager
     *
     * @return PluginManager
     */
    protected function _getPluginManager() {
        $pm = PluginManager::instance();
        return $pm;
    }
    
    /**
     * Function called before turning a plugin to available status
     * Allow you to check required things (DB connection, etc...)
     * and to forbid plugin to be made available if requirements are not met.
     *
     * @return boolean true if the plugin can be made available, false if not
     */
    public function canBeMadeAvailable() {
    	return true;
    }

	/**
     * Function called when a plugin is set as available or unavailable
     *
     * @param boolean $available true if the plugin is available, false if unavailable
     */
    public function setAvailable($available) {
    }
    
    /**
     * Function executed after plugin installation
     */
    public function postInstall() {
    }

    /**
     * Returns the content of the README file associated to the plugin
     *
     * @return String
     */
    public function getReadme() {
        return $this->getFilesystemPath().'/README';
    }

    /**
     * @return array of strings (identifier of plugins this one depends on)
     */
    public function getDependencies() {
        return array();
    }

    public function setIsCustom($is_custom) {
        $this->is_custom = $is_custom;
    }

    public function isCustom() {
        return $this->is_custom;
    }
}
?>