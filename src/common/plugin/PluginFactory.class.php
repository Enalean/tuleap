<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015. All Rights Reserved.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * PluginFactory
 */
class PluginFactory {

    /** @var PluginDao */
    private $plugin_dao;

    /** @var array */
    private $retrieved_plugins;

    /** @var array */
    private $custom_plugins;

    /** @var array */
    private $name_by_id;

    /** @var PluginResourceRestrictor */
    private $plugin_restrictor;

    /** @var array */
    private $plugin_class_path = array();
    
    public function __construct(
        PluginDao $plugin_dao,
        PluginResourceRestrictor $plugin_restrictor
    ) {
        $this->plugin_dao        = $plugin_dao;
        $this->plugin_restrictor = $plugin_restrictor;

        $this->retrieved_plugins = array(
            'by_name'     => array(),
            'by_id'       => array(),
            'available'   => array(),
            'unavailable' => array(),
        );
        $this->name_by_id     = array();
        $this->custom_plugins = array();
    }
    
    public static function instance() {
        static $_pluginfactory_instance;
        if (!$_pluginfactory_instance) {
            $plugin_dao              = new PluginDao(CodendiDataAccess::instance());
            $restricted_plugin_dao   = new RestrictedPluginDao();
            $restrictor              = new PluginResourceRestrictor($restricted_plugin_dao);

            $_pluginfactory_instance = new PluginFactory($plugin_dao, $restrictor);
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
        $class_path = '';
        if (!class_exists($class_name)) {
            $file_name = '/'.$name.'/include/'.$class_name.'.class.php';
            //Custom ?
            if ($this->loadClass($this->_getCustomPluginsRoot().$file_name)) {
                $custom = true;
                $class_path = $this->_getCustomPluginsRoot().$file_name;
            } else {
                $class_path = $this->tryPluginPaths($this->getOfficialPluginPaths(), $file_name);
            }
        }
        if (!class_exists($class_name)) {
            $class_name = false;
        } else {
            if ($class_path) {
                $this->plugin_class_path[$name] = array(
                    'class' => $class_name,
                    'path'  => $class_path,
                );
            }
        }
        return array('class' => $class_name, 'custom' => $custom);
    }

    private function getOfficialPluginPaths() {
        return array_merge(
            array_filter(array_map('trim', explode(',', ForgeConfig::get('sys_extra_plugin_path')))),
            array($this->_getOfficialPluginsRoot())
        );
    }

    private function tryPluginPaths(array $potential_paths, $file_name) {
        foreach($potential_paths as $path) {
            $full_path = $path.'/'.$file_name;
            if ($this->loadClass($full_path)) {
                return $full_path;
            }
        }
        return false;
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

    public function getProjectsByPluginId($plugin) {
        $project_ids = array();
        $dar         = $this->plugin_restrictor->searchAllowedProjectsOnPlugin($plugin);

        if ($dar && !$dar->isError()) {
            while($row = $dar->getRow()) {
                $project_ids[] = $row['project_id'];
            }
        }

        return $project_ids;
    }

    public function addProjectForPlugin($plugin, $project_id) {
        return $this->plugin_restrictor->allowProjectOnPlugin(
            $plugin,
            $this->getProject($project_id)
        );
    }

    public function delProjectForPlugin($plugin, $project_id) {
        return $this->plugin_restrictor->revokeProjectsFromPlugin(
            $plugin,
            $this->getProject($project_id)
        );
    }

    function restrictProjectPluginUse($plugin, $usage) {
        return $this->plugin_dao->restrictProjectPluginUse($plugin->getId(), $usage);
    }

    public function truncateProjectPlugin($plugin) {
        return $this->plugin_restrictor->revokeAllProjectsFromPlugin($plugin);
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

    public function isPluginAllowedForProject($plugin, $project_id) {
        return $this->plugin_restrictor->isPluginAllowedForProject(
            $plugin,
            $project_id
        );
    }

    /** @return Project */
    private function getProject($project_id) {
        return ProjectManager::instance()->getProject($project_id);
    }

    public function getClassPath($name) {
        return $this->plugin_class_path[$name]['path'];
    }

    public function getClassName($name) {
        return $this->plugin_class_path[$name]['class'];
    }
}