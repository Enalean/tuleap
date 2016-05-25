<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
*
*/

class CLI_ModuleFactory {

    var $root;
    function __construct($root) {
        $this->root = $root;
    }

    function exist($module) {
        $ok = false;
        if (is_dir($this->root . $module) && is_file($this->_getFileName($module))) {
            require_once($this->_getFileName($module));
            $ok = class_exists($this->_getModuleClassName($module));
        }
        return $ok;
    }

    function &getModule($module) {
        $m = null;
        if ($this->exist($module)) {
            $className = $this->_getModuleClassName($module);
            $m = new $className();
        }
        return $m;
    }

    function getAllModules() {
        $modules = array();
        if ($dh = opendir($this->root)) {
            while (($module_name = readdir($dh)) !== false) {
                $m =& $this->getModule($module_name);
                if ($m) {
                    $modules[$module_name] =& $m;
                }
            }
            closedir($dh);
        }
        return $modules;
    }

    /* protected */
    function _getFileName($module) {
        return $this->root . $module.'/'. $module .'.php';
    }
    function _getModuleClassName($module) {
        return 'CLI_Module_'.ucfirst($module);
    }
}
