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

/**
 * PluginFactory
 */
class PluginFactory {
    
    var $plugin_dao;
    var $retrieved_plugins;
    var $custom_plugins;
    var $name_by_id;
    
    function PluginFactory($plugin_dao) {
        $this->plugin_dao = $plugin_dao;
        $this->retrieved_plugins    = array(
            'by_name'     => array(),
            'by_id'       => array(),
            'available'   => array(),
            'unavailable' => array(),
        );
        $this->name_by_id           = array();
        $this->custom_plugins       = array();
    }
    
    function instance() {
        static $_pluginfactory_instance;
        if (!$_pluginfactory_instance) {
            $dao = new PluginDao(CodendiDataAccess::instance());
            $_pluginfactory_instance = new PluginFactory($dao);
        }
        return $_pluginfactory_instance;
    }
    
    function getPluginById($id) {
        if (!isset($this->retrieved_plugins['by_id'][$id])) {
            $dar = $this->plugin_dao->searchById($id);
            if ($row = $dar->getRow()) {
                $p = $this->_getInstancePlugin($id, $row);
            } else {
                $this->retrieved_plugins['by_id'][$id] = false;
            }
        }
        return $this->retrieved_plugins['by_id'][$id];
    }
    
    function getPluginByName($name) {
        if (!isset($this->retrieved_plugins['by_name'][$name])) {
            $dar = $this->plugin_dao->searchByName($name);
            if ($row = $dar->getRow()) {
                $p = $this->_getInstancePlugin($row['id'], $row);
            } else {
                $this->retrieved_plugins['by_name'][$name] = false;
            }
        }
        return $this->retrieved_plugins['by_name'][$name];
    }
    
    /**
     * create a plugin in the db
     * @return the new plugin or false if there is already the plugin (same name)
     */
    function createPlugin($name) {
        $p = false;
        $dar = $this->plugin_dao->searchByName($name);
        if (!$dar->getRow()) {
            $id = $this->plugin_dao->create($name, 0);
            if (is_int($id)) {
                $p  = $this->_getInstancePlugin($id, array('name' => $name, 'available' => 0));
                if ($p && $p->getScope() === Plugin::SCOPE_PROJECT && $p->isRestrictedByDefault) {
                    $this->plugin_dao->restrictProjectPluginUse($id, true);
                }
            }
        }
        return $p;
    }
    
    function _getInstancePlugin($id, $row) {
        if (!isset($this->retrieved_plugins['by_id'][$id])) {
            $this->retrieved_plugins['by_id'][$id] = false;
            $p = $this->instantiatePlugin($id, $row['name']);
            if ($p) {
                $this->retrieved_plugins['by_id'][$id]            = $p;
                $this->retrieved_plugins['by_name'][$row['name']] = $p;
                $this->retrieved_plugins[($row['available'] ? 'available' : 'unavailable')][$id] = $p;
                $this->name_by_id[$id] = $row['name'];
                if ($p->isCustom()) {
                    $this->custom_plugins[$id] = $p;
                }
            }
        }
        return $this->retrieved_plugins['by_id'][$id];
    }

    public function instantiatePlugin($id, $name) {
        $plugin_class_info = $this->_getClassNameForPluginName($name);
        $plugin_class      = $plugin_class_info['class'];
        if (! $plugin_class) {
            return null;
        }

        $plugin = new $plugin_class($id);
        if ($plugin_class_info['custom']) {
            $plugin->setIsCustom(true);
        }
        return $plugin;
    }

    function _getClassNameForPluginName($name) {
        $class_name = $name."Plugin";
        $custom     = false;
        if (!class_exists($class_name)) {
            $file_name = '/'.$name.'/include/'.$class_name.'.class.php';
            //Custom ?
            if ($this->loadClass($this->_getCustomPluginsRoot().$file_name)) {
                $custom = true;
            } else {
                // Official !!!
                $this->loadClass($this->_getOfficialPluginsRoot().$file_name);
            }
        }
        if (!class_exists($class_name)) {
            $class_name = false;
        }
        return array('class' => $class_name, 'custom' => $custom);
    }

    private function loadClass($class_path) {
        if ($this->includeIfExists($class_path)) {
            $autoload_path = dirname($class_path) . DIRECTORY_SEPARATOR . 'autoload.php';
            $this->includeIfExists($autoload_path);
            return true;
        }
        return false;
    }

    private function includeIfExists($file_path) {
        if (file_exists($file_path)) {
            require_once($file_path);
            return true;
        }
        return false;
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
     * @return array of enabled or disabled plugins depends on parameters
     */
    function _getAvailableOrUnavailablePlugins($map, $criteria) {
         $dar = $this->plugin_dao->searchByAvailable($criteria);
         while($row = $dar->getRow()) {
             $p = $this->_getInstancePlugin($row['id'], $row);
         }
         return $this->retrieved_plugins[$map];
    }
    /**
     * @return array of unavailable plugins
     */
    function getUnavailablePlugins() {
         return $this->_getAvailableOrUnavailablePlugins('unavailable', 0);
    }
    /**
     * @return array of enabled plugins
     */
    function getAvailablePlugins() {
         return $this->_getAvailableOrUnavailablePlugins('available', 1);
    }
    /**
     * @return array of all plugins
     */
    function getAllPlugins() {
        $all_plugins = array();
        $dar = $this->plugin_dao->searchAll();
        while($row = $dar->getRow()) {
            if ($p = $this->_getInstancePlugin($row['id'], $row)) {
                $all_plugins[] = $p;
            } 
        }
        return $all_plugins;
    }
    /**
     * @return true if the plugin is enabled
     */
    function isPluginAvailable($plugin) {
        return isset($this->retrieved_plugins['available'][$plugin->getId()]);
    }
    
    /**
     * available plugin
     */
    function availablePlugin($plugin) {
        if (!$this->isPluginAvailable($plugin)) {
            $this->plugin_dao->updateAvailableByPluginId('1', $plugin->getId());
            $this->retrieved_plugins['available'][$plugin->getId()] = $plugin;
            unset($this->retrieved_plugins['unavailable'][$plugin->getId()]);
        }
    }
    /**
     * unavailable plugin
     */
    function unavailablePlugin($plugin) {
        if ($this->isPluginAvailable($plugin)) {
            $this->plugin_dao->updateAvailableByPluginId('0', $plugin->getId());
            $this->retrieved_plugins['unavailable'][$plugin->getId()] = $plugin;
            unset($this->retrieved_plugins['available'][$plugin->getId()]);
        }
    }
    
    function getNotYetInstalledPlugins() {
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
        $dar = $this->plugin_dao->searchByName($name);
        return ($dar->rowCount() > 0);
    }
        
    function removePlugin($plugin) {
        $id =  $plugin->getId();
        unset($this->retrieved_plugins['by_id'][$id]);
        unset($this->retrieved_plugins['by_name'][$this->name_by_id[$id]]);
        unset($this->retrieved_plugins['available'][$id]);
        unset($this->retrieved_plugins['unavailable'][$id]);
        unset($this->name_by_id[$id]);
        return $this->plugin_dao->removeById($plugin->getId());
    }
    
    function getNameForPlugin($plugin) {
        $name = '';
        $id = $plugin->getId();
        if (isset($this->name_by_id[$id])) {
            $name = $this->name_by_id[$id];
        } else {
            if ($dar = $this->plugin_dao->searchById($id)) {
                if ($row = $dar->getRow()) {
                    $name = $row['name'];
                }
            }
        }
        return $name;
    }
    
    function pluginIsCustom($plugin) {
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
