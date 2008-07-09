<?php
require_once('PluginInfo.class.php');

require_once('common/collection/Map.class.php');
require_once('PluginManager.class.php');
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * Plugin
 */
class Plugin {
    
    var $id;
    var $pluginInfo;
    var $hooks;
    protected $_scope;
    
    const SCOPE_SYSTEM  = 0;
    const SCOPE_PROJECT = 1;
    const SCOPE_USER    = 2;
    
    public function Plugin($id = -1) {
        $this->id            = $id;
        $this->hooks         = new Map();
        
        $this->_scope = Plugin::SCOPE_SYSTEM;
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'PluginInfo')) {
            $this->pluginInfo =& new PluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    public function getHooks() {
        return $this->hooks->getKeys();
    }
    
    public function getHooksAndCallbacks() {
        return $this->hooks->getValues();
    }
    
    protected function _addHook($hook, $callback = 'CallHook', $recallHook = true) {
        $value = array();
        $value['hook']       = $hook;
        $value['callback']   = $callback;
        $value['recallHook'] = $recallHook;
        $this->hooks->put( $hook, $value);
    }
    
    protected function _removeHook($hook) {
        $this->hooks->removeKey( $hook);
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
        $pm = $this->_getPluginManager();
        return $GLOBALS['sys_custompluginsroot'] . '/' . $pm->getNameForPlugin($this) .'/etc';
    }
    
    public function _getPluginPath() {
        $trace = debug_backtrace();
        trigger_error("Plugin->_getPluginPath() is deprecated. Please use Plugin->getPluginPath() instead in ". $trace[0]['file'] ." at line ". $trace[0]['line'], E_USER_WARNING);
        return $this->getPluginPath();
    }
    public function getPluginPath() {
        $pm = $this->_getPluginManager();
        if (isset($GLOBALS['sys_pluginspath']))
            $path = $GLOBALS['sys_pluginspath'];
        else $path=""; 
        if ($pm->pluginIsCustom($this)) {
            $path = $GLOBALS['sys_custompluginspath'];
        }
        return $path.'/'.$pm->getNameForPlugin($this);
    }
    
    public function _getThemePath() {
        $trace = debug_backtrace();
        trigger_error("Plugin->_getThemePath() is deprecated. Please use Plugin->getThemePath() instead in ". $trace[0]['file'] ." at line ". $trace[0]['line'], E_USER_WARNING);
        return $this->getThemePath();
    }
    public function getThemePath() {
        $pm = $this->_getPluginManager();
        $paths  = array($GLOBALS['sys_custompluginspath'], $GLOBALS['sys_pluginspath']);
        $roots  = array($GLOBALS['sys_custompluginsroot'], $GLOBALS['sys_pluginsroot']);
        $dir    = '/'.$pm->getNameForPlugin($this).'/www/themes/';
        $dirs   = array($dir.$GLOBALS['sys_user_theme'], $dir.'default');
        $dir    = '/'.$pm->getNameForPlugin($this).'/themes/';
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
    
    protected function _getPluginManager() {
        $pm = PluginManager::instance();
        return $pm;
    }
    
    /**
     * Function called when a plugin is set as available or unavailable
     *
     * @param boolean $available true if the plugin is available, false if unavailable
     */
    public function setAvailable($available) {
    }
}
?>