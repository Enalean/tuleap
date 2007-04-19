<?php
require_once('common/dao/PluginDao.class.php');
require_once('common/dao/CodexDataAccess.class.php');

require_once('common/collection/Map.class.php');

require_once('common/include/String.class.php');

require_once('Plugin.class.php');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: PluginFactory.class.php 5199 2007-03-06 12:18:47 +0000 (Tue, 06 Mar 2007) nterray $
 *
 * PluginFactory
 */
class PluginFactory {
    
    var $plugin_dao;
    var $retrieved_plugins;
    var $available_plugins;
    var $unavailable_plugins;
    var $custom_plugins;
    
    function PluginFactory(&$plugin_dao) {
        $this->plugin_dao =& $plugin_dao;
        $this->retrieved_plugins    =& new Map();
        $this->available_plugins    =& new Map();
        $this->unavailable_plugins  =& new Map();
        $this->custom_plugins       = array();
    }
    
    function &instance() {
        static $_pluginfactory_instance;
        if (!$_pluginfactory_instance) {
            $dao =& new PluginDao(CodexDataAccess::instance());
            $_pluginfactory_instance = new PluginFactory($dao);
        }
        return $_pluginfactory_instance;
    }
    
    function &getPluginById($id) {
        $p = false;
        $key =& new String($id);
        if ($this->retrieved_plugins->containsKey($key)) {
            $p =& $this->retrieved_plugins->get($key);
        } else {
            $dar =& $this->plugin_dao->searchById($id);
            if ($row = $dar->getRow()) {
                $p =& $this->_getInstancePlugin($id, $row['name']);
            }
        }
        return $p;
    }
    
    function &getPluginByName($name) {
        $retrieved =& $this->retrieved_plugins->getValues();
        $iter =& $retrieved->iterator();
        $not_found = true;
        while($iter->valid() && $not_found) {
            $p =& $iter->current();
            $not_found = ($name != $this->getNameForPlugin($p));
            $iter->next();
        }
        if ($not_found) {
            unset($p);
            $p = false;
            $dar =& $this->plugin_dao->searchByName($name);
            if ($row = $dar->getRow()) {
                $p =& $this->_getInstancePlugin($row['id'], $name);
            }
        }
        return $p;
    }
    
    /**
     * create a plugin in the db
     * @return the new plugin or false if there is already the plugin (same name)
     */
    function &createPlugin($name) {
        $p = false;
        $dar =& $this->plugin_dao->searchByName($name);
        if (!$dar->getRow()) {
            $id = $this->plugin_dao->create($name, 0);
            if (is_int($id)) {
                $p  = $this->_getInstancePlugin($id, $name);
            }
        }
        return $p;
    }
    
    function &_getInstancePlugin($id, $name) {
        $p   = false;
        $key =& new String($id);
        if ($this->retrieved_plugins->containsKey($key)) {
            $p =& $this->retrieved_plugins->get($key);
        } else {
            $plugin_class_info = $this->_getClassNameForPluginName($name);
            $plugin_class      = $plugin_class_info['class'];
            if ($plugin_class) {
                $p =& new $plugin_class($id);
                $this->retrieved_plugins->put(new String($id), $p);
                if ($plugin_class_info['custom']) {
                    $this->custom_plugins[$id] =& $p;
                }
            }
        }
        return $p;
    }
    
    function _getClassNameForPluginName($name) {
        $class_name = $name."Plugin";
        $custom     = false;
        if (!class_exists($class_name)) {
            $file_name = '/'.$name.'/include/'.$class_name.'.class.php';
            //Custom ?
            if (file_exists($this->_getCustomPluginsRoot().$file_name)) {
                require_once($this->_getCustomPluginsRoot().$file_name);
                $custom = true;
            } else {
                // Official !!!
                if (file_exists($this->_getOfficialPluginsRoot().$file_name)) {
                    require_once($this->_getOfficialPluginsRoot().$file_name);
                }
            }
        }
        if (!class_exists($class_name)) {
            $class_name = false;
        }
        return array('class' => $class_name, 'custom' => $custom);
    }
    
    function _getOfficialPluginsRoot() {
        if (isset($GLOBALS['sys_pluginsroot']))
            return $GLOBALS['sys_pluginsroot'];
        else return null;
    }
    
    function _getCustomPluginsRoot() {
        if (isset($GLOBALS['sys_custompluginsroot']))
            return $GLOBALS['sys_custompluginsroot'];
        else return null;
    }
    
    /**
     * @return Collection of enabled or disabled plugins depends on parameters
     */
    function &_getAvailableOrUnavailablePlugins(&$map, $criteria) {
         $dar =& $this->plugin_dao->searchByAvailable($criteria);
         while($row = $dar->getRow()) {
             $p =& $this->_getInstancePlugin($row['id'], $row['name']);
             if ($p) {
                 $key =& new String($row['id']);
                 if (!$map->containsKey($key)) {
                     $map->put($key, $p);
                 }
             }
         }
         return $map->getValues();
    }
    /**
     * @return Collection of unavailable plugins
     */
    function &getUnavailablePlugins() {
         return $this->_getAvailableOrUnavailablePlugins($this->unavailable_plugins, 0);
    }
    /**
     * @return Collection of enabled plugins
     */
    function &getAvailablePlugins() {
         return $this->_getAvailableOrUnavailablePlugins($this->available_plugins, 1);
    }
    /**
     * @return Collection of all plugins
     */
    function &getAllPlugins() {
        $dar =& $this->plugin_dao->searchAll();
        while($row = $dar->getRow()) {
             $p =& $this->_getInstancePlugin($row['id'], $row['name']);
        }
        return $this->retrieved_plugins->getValues();
    }
    /**
     * @return true if the plugin is enabled
     */
    function isPluginAvailable(&$plugin) {
        $this->getAvailablePlugins();
        return $this->available_plugins->containsKey(new String($plugin->getId()));
    }
    
    /**
     * available plugin
     */
    function availablePlugin(&$plugin) {
        if (!$this->isPluginAvailable($plugin)) {
            $this->plugin_dao->updateAvailableByPluginId('1', $plugin->getId());
            $this->available_plugins->put(new String($plugin->getId()), $plugin);
            $this->unavailable_plugins->removeKey(new String($plugin->getId()));
        }
    }
    /**
     * unavailable plugin
     */
    function unavailablePlugin(&$plugin) {
        if ($this->isPluginAvailable($plugin)) {
            $this->plugin_dao->updateAvailableByPluginId('0', $plugin->getId());
            $this->unavailable_plugins->put(new String($plugin->getId()), $plugin);
            $this->available_plugins->removeKey(new String($plugin->getId()));
        }
    }
    
    function &getNotYetInstalledPlugins() {
        $col     = array();
        $paths   = array($this->_getCustomPluginsRoot(), $this->_getOfficialPluginsRoot());
        $exclude = array('.', '..', 'CVS', '.svn');
        foreach($paths as $path) {
            $dir = openDir($path);
            while ($file = readDir($dir)) {
                if (!in_array($file, $exclude) && is_dir($path.'/'.$file)) {
                    if (!$this->isPluginInstalled($file) && !in_array($file, $col)) {
                        $col[] = $file;
                    }
                }
            }
            closeDir($dir);
        }
        return $col;
    }

    function isPluginInstalled($name) {
        $dar =& $this->plugin_dao->searchByName($name);
        return ($dar->rowCount() > 0);
    }
        
    function removePlugin(&$plugin) {
        $id = new String($plugin->getId());
        $this->retrieved_plugins->removeKey($id);
        $this->available_plugins->removeKey($id);
        $this->unavailable_plugins->removeKey($id);
        return $this->plugin_dao->removeById($plugin->getId());
    }
    
    function getNameForPlugin(&$plugin) {
        $name = '';
        if ($dar =& $this->plugin_dao->searchById($plugin->getId())) {
            if ($row = $dar->getRow()) {
                $name = $row['name'];
            }
        }
        return $name;
    }
    
    function pluginIsCustom(&$plugin) {
        return isset($this->custom_plugins[$plugin->getId()]);
    }

    function getProjectsByPluginId($plugin) {
        $projectIds = array();
        $dar = $this->plugin_dao->searchProjectsForPlugin($plugin->getId());
        if($dar && !$dar->isError()) {
            while($row = $dar->getRow()) {
                $projectIds[] = $row['project_id'];
            }
        }
        return $projectIds;
    }

    function addProjectForPlugin($plugin, $projectId) {
        return $this->plugin_dao->bindPluginToProject($plugin->getId(), $projectId);
    }

    function delProjectForPlugin($plugin, $projectId) {
        return $this->plugin_dao->unbindPluginToProject($plugin->getId(), $projectId);
    }

    function restrictProjectPluginUse($plugin, $usage) {
        return $this->plugin_dao->restrictProjectPluginUse($plugin->getId(), $usage);
    }

    function truncateProjectPlugin($plugin) {
        return $this->plugin_dao->truncateProjectPlugin($plugin->getId());
    }

    function isProjectPluginRestricted($plugin) {
        $restricted = false;
        $dar =$this->plugin_dao->searchProjectPluginRestrictionStatus($plugin->getId());
        if($dar && !$dar->isError()) {
            $row = $dar->getRow();
            $restricted = $row['prj_restricted'];
        }
        return $restricted;
    }

    function isPluginAllowedForProject($plugin, $projectId) {
        return $this->plugin_dao->isPluginAllowedForProject($plugin->getId(), $projectId);
    }
}
?>
