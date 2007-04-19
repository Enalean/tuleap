<?php
require_once('common/event/EventManager.class.php');

require_once('common/plugin/PluginFactory.class.php');
require_once('common/plugin/PluginHookPriorityManager.class.php');

require_once('common/dao/DBTablesDao.class.php');
require_once('common/dao/CodexDataAccess.class.php');

require_once('common/include/String.class.php');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: PluginManager.class.php 5199 2007-03-06 12:18:47 +0000 (Tue, 06 Mar 2007) nterray $
 *
 * PluginManager
 */
class PluginManager {
    
    var $plugins_loaded;
    var $pluginHookPriorityManager;
    
    function PluginManager() {
        $this->plugins_loaded = false;
    }
    
    function loadPlugins() {
        $plugin_factory =& $this->_getPluginFactory();
        $event_manager  =& $this->_getEventManager();
        
        $col_available_plugins =& $plugin_factory->getAvailablePlugins();
        $available_plugins =& $col_available_plugins->iterator();
        $priority_manager =& $this->_getPluginHookPriorityManager();
        while($available_plugins->valid()) {
            $plugin =& $available_plugins->current();
            $hooks =& $plugin->getHooksAndCallbacks();
            $iter =& $hooks->iterator();
            while($iter->valid()) {
                $hook =& $iter->current();
                $priority = $priority_manager->getPriorityForPluginHook($plugin, $hook['hook']);
                $event_manager->addListener($hook['hook'], $plugin, $hook['callback'], $hook['recallHook'], $priority);
                $iter->next();
            }
            $available_plugins->next();
        }
        $this->plugins_loaded = true;
    }
    
    function &_getPluginFactory() {
        return PluginFactory::instance();
    }
    
    function &_getEventManager() {
        return EventManager::instance();
    }
    
    function &_getPluginHookPriorityManager() {
        if (!is_a($this->pluginHookPriorityManager, 'PluginHookPriorityManager')) {
            $this->pluginHookPriorityManager =& new PluginHookPriorityManager();
        }
        return $this->pluginHookPriorityManager;
    }
    
    function isPluginsLoaded() {
        return $this->plugins_loaded;
    }
    
    function &instance() {
        static $_pluginmanager_instance;
        if (!$_pluginmanager_instance) {
            $_pluginmanager_instance = new PluginManager();
        }
        return $_pluginmanager_instance;
    }
    
    function &getAllPlugins() {
        $plugin_factory =& $this->_getPluginFactory();
        return $plugin_factory->getAllPlugins();
    }
    
    function isPluginAvailable(&$plugin) {
        $plugin_factory =& $this->_getPluginFactory();
        return $plugin_factory->isPluginAvailable($plugin);
    }
    
    function availablePlugin(&$plugin) {
        $plugin_factory =& $this->_getPluginFactory();
        $plugin_factory->availablePlugin($plugin);
    }
    function unavailablePlugin(&$plugin) {
        $plugin_factory =& $this->_getPluginFactory();
        $plugin_factory->unavailablePlugin($plugin);
    }
    
    function &installPlugin($name) {
        $plugin = false;
        if ($this->isNameValid($name)) {
            $plugin_factory =& $this->_getPluginFactory();
            if (!$plugin_factory->isPluginInstalled($name)) {
                if (!$this->_executeSqlStatements('install', $name)) {
                    $plugin_factory =& $this->_getPluginFactory();
                    $plugin =& $plugin_factory->createPlugin($name);
                    $this->_createEtc($name);
                } else {
                    $GLOBALS['Response']->addFeedback('error', 'DB may be corrupted');
                }
            }
        }
        return $plugin;
    }
    
    function uninstallPlugin(&$plugin) {
        $plugin_factory =& $this->_getPluginFactory();
        $name = $plugin_factory->getNameForPlugin($plugin);
        if (!$this->_executeSqlStatements('uninstall', $name)) {
            $phpm =& $this->_getPluginHookPriorityManager();
            $phpm->removePlugin($plugin);
            $plugin_factory =& $this->_getPluginFactory();
            return $plugin_factory->removePlugin($plugin);
        } else {
            return false;
        }
    }
    function getPostInstall($name) {
        $path_to_file = '/'.$name.'/POSTINSTALL.txt';
        return file_exists($GLOBALS['sys_pluginsroot'].$path_to_file) ? 
            file_get_contents($GLOBALS['sys_pluginsroot'].$path_to_file) : 
            false;
    }
    function getReadme($name) {
        $path_to_file = '/'.$name.'/README.txt';
        return file_exists($GLOBALS['sys_pluginsroot'].$path_to_file) ? 
            file_get_contents($GLOBALS['sys_pluginsroot'].$path_to_file) : 
            false;
    }
    function _createEtc($name) {
        if (!is_dir($GLOBALS['sys_custompluginsroot'] .'/'. $name)) {
            mkdir($GLOBALS['sys_custompluginsroot'] .'/'. $name, 0700);
        }
        if (is_dir($GLOBALS['sys_pluginsroot'] .'/'. $name .'/etc')) {
            if (!is_dir($GLOBALS['sys_custompluginsroot'] .'/'. $name .'/etc')) {
                mkdir($GLOBALS['sys_custompluginsroot'] .'/'. $name . '/etc', 0700);
            }
            $etcs = glob($GLOBALS['sys_pluginsroot'] .'/'. $name .'/etc/*');
            foreach($etcs as $etc) {
                copy($etc, $GLOBALS['sys_custompluginsroot'] .'/'. $name . '/etc/' . basename($etc));
            }
            $incdists = glob($GLOBALS['sys_custompluginsroot'] .'/'. $name .'/etc/*.dist');
            foreach($incdists as $incdist) {
                rename($incdist,  $GLOBALS['sys_custompluginsroot'] .'/'. $name . '/etc/' . basename($incdist, '.dist'));
            }
        }
    }
        
    function _executeSqlStatements($file, $name) {
        $db_corrupted = false;
        $path_found   = false;
        $path_to_file = '/'.$name.'/db/'.$file.'.sql';
        $possible_file_names = array(   $GLOBALS['sys_pluginsroot'].$path_to_file, 
                                        $GLOBALS['sys_custompluginsroot'].$path_to_file);
        while(!$path_found && (list(,$sql_filename) = each($possible_file_names))) {
            if (file_exists($sql_filename)) {
                $dbtables =& new DBTablesDAO(CodexDataAccess::instance());
                if (!$dbtables->updateFromFile($sql_filename)) {
                    $db_corrupted = true;
                }
            }
        }
        return $db_corrupted;
    }
    function &getNotYetInstalledPlugins() {
        $plugin_factory =& $this->_getPluginFactory();
        return $plugin_factory->getNotYetInstalledPlugins(); 
    }
    
    function isNameValid($name) {
        return (0 === preg_match('/[^a-zA-Z0-9_-]/', $name));
    }
    
    function &getPluginByName($name) {
        $plugin_factory =& $this->_getPluginFactory();
        $p =& $plugin_factory->getPluginByName($name);
        return $p;
    }
    function &getPluginById($id) {
        $plugin_factory =& $this->_getPluginFactory();
        $p =& $plugin_factory->getPluginById($id);
        return $p;
    }
    function pluginIsCustom(&$plugin) {
        $plugin_factory =& $this->_getPluginFactory();
        $p = $plugin_factory->pluginIsCustom($plugin);
        return $p;
    }
    
    var $plugins_name;
    function getNameForPlugin(&$plugin) {
        if (!$this->plugins_name) {
            $this->plugins_name = array();
        }
        if (!isset($this->plugins_name[$plugin->getId()])) {
            $plugin_factory =& $this->_getPluginFactory();
            $this->plugins_name[$plugin->getId()] = $plugin_factory->getNameForPlugin($plugin);
        }
        return $this->plugins_name[$plugin->getId()];
    }

    function getAllowedProjects($plugin) {
        $prjIds = null;
        //if($plugin->getScope() == $plugin->SCOPE_PROJECT) {
        $plugin_factory =& $this->_getPluginFactory();
        $prjIds = $plugin_factory->getProjectsByPluginId($plugin);
        //}
        return $prjIds;
    }
    
    function _updateProjectForPlugin($action, $plugin, $projectIds) {
        $plugin_factory =& $this->_getPluginFactory();
        
        $success     = true;
        $successOnce = false;
        
        if(is_array($projectIds)) {
            foreach($projectIds as $prjId) {
                switch($action){
                case 'add':
                    $success = $success && $plugin_factory->addProjectForPlugin($plugin, $prjId);
                    break;
                case 'del':
                    $success = $success && $plugin_factory->delProjectForPlugin($plugin, $prjId);
                    break;
                }
                
                if($success === true)
                    $successOnce = true;
            }
        }
        elseif(is_numeric($projectIds)) {
            switch($action){
            case 'add':
                $success = $success && $plugin_factory->addProjectForPlugin($plugin, $prjId);
                break;
            case 'del':
                $success = $success && $plugin_factory->delProjectForPlugin($plugin, $prjId);
                break;
            }
            $successOnce = $success;
        }
        
        if($successOnce && ($action == 'add')) {
            $plugin_factory->restrictProjectPluginUse($plugin, true);
        }
    }

    function addProjectForPlugin($plugin, $projectIds) {
        $this->_updateProjectForPlugin('add', $plugin, $projectIds);
    }

    function delProjectForPlugin($plugin, $projectIds) {
        $this->_updateProjectForPlugin('del', $plugin, $projectIds);
    }

    function isProjectPluginRestricted($plugin) {
        $plugin_factory =& $this->_getPluginFactory();
        return $plugin_factory->isProjectPluginRestricted($plugin);
    }

    function updateProjectPluginRestriction($plugin, $restricted) {
        $plugin_factory =& $this->_getPluginFactory();
        $plugin_factory->restrictProjectPluginUse($plugin, $restricted);
        if($restricted == false) {
            $plugin_factory->truncateProjectPlugin($plugin);
        }
    }

    function isPluginAllowedForProject($plugin, $projectId) {
        if($this->isProjectPluginRestricted($plugin)) {
            $plugin_factory =& $this->_getPluginFactory();
            return $plugin_factory->isPluginAllowedForProject($plugin, $projectId);
        }
        else {
            return true;
        }
    }
}
?>
