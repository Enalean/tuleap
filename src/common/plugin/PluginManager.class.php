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
 * PluginManager
 */
class PluginManager {
    
    var $plugins_loaded;
    var $pluginHookPriorityManager;
    
    function PluginManager() {
        $this->plugins_loaded = false;
    }
    
    function loadPlugins() {
        $plugin_factory = $this->_getPluginFactory();
        $event_manager  = $this->_getEventManager();
        
        $priority_manager = $this->_getPluginHookPriorityManager();
        $priority_manager->cacheAllPrioritiesForPluginHook();
        foreach($plugin_factory->getAvailablePlugins() as $plugin) {
            $hooks = $plugin->getHooksAndCallbacks();
            $iter = $hooks->iterator();
            while($iter->valid()) {
                $hook = $iter->current();
                $priority = $priority_manager->getPriorityForPluginHook($plugin, $hook['hook']);
                $event_manager->addListener($hook['hook'], $plugin, $hook['callback'], $hook['recallHook'], $priority);
                $iter->next();
            }
            $plugin->loaded();
        }
        $this->plugins_loaded = true;
    }

    public function getAvailablePlugins() {
        return $this->_getPluginFactory()->getAvailablePlugins();
    }

    function _getPluginFactory() {
        return PluginFactory::instance();
    }
    
    function _getEventManager() {
        return EventManager::instance();
    }
    
    function _getPluginHookPriorityManager() {
        if (!is_a($this->pluginHookPriorityManager, 'PluginHookPriorityManager')) {
            $this->pluginHookPriorityManager = new PluginHookPriorityManager();
        }
        return $this->pluginHookPriorityManager;
    }

    function _getForgeUpgradeConfig() {
        return new ForgeUpgradeConfig();
    }

    function isPluginsLoaded() {
        return $this->plugins_loaded;
    }
    
    function instance() {
        static $_pluginmanager_instance;
        if (!$_pluginmanager_instance) {
            $_pluginmanager_instance = new PluginManager();
        }
        return $_pluginmanager_instance;
    }
    
    function getAllPlugins() {
        $plugin_factory = $this->_getPluginFactory();
        return $plugin_factory->getAllPlugins();
    }
    
    function isPluginAvailable($plugin) {
        $plugin_factory = $this->_getPluginFactory();
        return $plugin_factory->isPluginAvailable($plugin);
    }
    
    function availablePlugin($plugin) {
        if ($plugin->canBeMadeAvailable()) {
            $plugin_factory = $this->_getPluginFactory();
            $plugin_factory->availablePlugin($plugin);
        
            $plugin->setAvailable(true);
            $this->getSiteCache()->invalidatePluginBasedCaches();
        }
    }
    function unavailablePlugin($plugin) {
        $plugin_factory = $this->_getPluginFactory();
        $plugin_factory->unavailablePlugin($plugin);
        
        $plugin->setAvailable(false);
        $this->getSiteCache()->invalidatePluginBasedCaches();
    }

    /**
     * @return SiteCache
     */
    protected function getSiteCache() {
        return new SiteCache();
    }

    function installPlugin($name) {
        $plugin = false;
        if ($this->isNameValid($name)) {
            $plugin_factory = $this->_getPluginFactory();
            if (!$plugin_factory->isPluginInstalled($name)) {
                if (!$this->_executeSqlStatements('install', $name)) {
                    $plugin_factory = $this->_getPluginFactory();
                    $plugin = $plugin_factory->createPlugin($name);
                    if ($plugin instanceof Plugin) {
                        $this->_createEtc($name);
                        $this->configureForgeUpgrade($name);
                        $plugin->postInstall();
                    } else {
                        $GLOBALS['Response']->addFeedback('error', 'Unable to create plugin');
                    }
                } else {
                    $GLOBALS['Response']->addFeedback('error', 'DB may be corrupted');
                }
            }
        }
        return $plugin;
    }

    function uninstallPlugin($plugin) {
        $plugin_factory = $this->_getPluginFactory();
        $name = $plugin_factory->getNameForPlugin($plugin);
        if (!$this->_executeSqlStatements('uninstall', $name)) {
            $phpm = $this->_getPluginHookPriorityManager();
            $phpm->removePlugin($plugin);
            $this->uninstallForgeUpgrade($name);
            $plugin_factory = $this->_getPluginFactory();
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
    
    function getInstallReadme($name) {
        return $GLOBALS['sys_pluginsroot'] .'/'. $name .'/README';
    }
    
    /**
     * Format the readme file of a plugin
     *
     * Use markdown formatter if installed and if README.mkd exists
     * Otherwise assume text/plain and put it in <pre> tags
     * If README file doesn't exist, return empty string.
     *
     * For Markdown, the following is needed:
     * <code>
     * pear channel-discover pear.michelf.com
     * pear install michelf/package
     * </code>
     *
     * @return string html
     */
    function fetchFormattedReadme($file) {
        if (is_file("$file.mkd")) {
            $content = file_get_contents("$file.mkd");
            if (@include_once "markdown.php") {
                return Markdown($content);
            }
            return $this->getEscapedReadme($content);
        }
        
        if (is_file("$file.txt")) {
            return $this->getEscapedReadme(file_get_contents("$file.txt"));
        }
        
        if (is_file($file)) {
            return $this->getEscapedReadme(file_get_contents($file));
        }
        
        return '';
    }

    private function getEscapedReadme($content) {
        return '<pre>'.Codendi_HTMLPurifier::instance()->purify($content).'</pre>';
    }

    /**
     * Initialize ForgeUpgrade configuration for given plugin
     *
     * Add in configuration and record existing migration scripts as 'skipped'
     * because the 'install.sql' script is up-to-date with latest DB modif.
     *
     * @param String $name Plugin's name
     */
    protected function configureForgeUpgrade($name) {
        $fuc = $this->_getForgeUpgradeConfig();
        try {
            $fuc->loadDefaults();
            $fuc->addPath($GLOBALS['sys_pluginsroot'].$name);
            $fuc->execute('record-only');
        } catch (Exception $e) {
            $GLOBALS['Response']->addFeedback('warning', "ForgeUpgrade configuration update failed: ".$e->getMessage());
        }
    }

    /**
     * Remove plugin from ForgeUpgrade configuration
     *
     * Keep migration scripts in DB, it doesn't matter.
     *
     * @param String $name Plugin's name
     */
    protected function uninstallForgeUpgrade($name) {
        $fuc = new ForgeUpgradeConfig();
        try {
            $fuc->loadDefaults();
            $fuc->removePath($GLOBALS['sys_pluginsroot'].$name);
        } catch (Exception $e) {
            $GLOBALS['Response']->addFeedback('warning', "ForgeUpgrade configuration update failed: ".$e->getMessage());
        }
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
                if(is_dir($etc)) {
                    $this->copyDirectory($etc, $GLOBALS['sys_custompluginsroot'] .'/'. $name . '/etc/' . basename($etc));
                } else {
                    copy($etc, $GLOBALS['sys_custompluginsroot'] .'/'. $name . '/etc/' . basename($etc));
                }
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
                $dbtables = new DBTablesDAO(CodendiDataAccess::instance());
                if (!$dbtables->updateFromFile($sql_filename)) {
                    $db_corrupted = true;
                }
            }
        }
        return $db_corrupted;
    }
    function getNotYetInstalledPlugins() {
        $plugin_factory = $this->_getPluginFactory();
        return $plugin_factory->getNotYetInstalledPlugins(); 
    }
    
    function isNameValid($name) {
        return (0 === preg_match('/[^a-zA-Z0-9_-]/', $name));
    }
    
    function getPluginByName($name) {
        $plugin_factory = $this->_getPluginFactory();
        $p = $plugin_factory->getPluginByName($name);
        return $p;
    }
    function getAvailablePluginByName($name) {
        $plugin = $this->getPluginByName($name);
        if ($plugin && $this->isPluginAvailable($plugin)) {
            return $plugin;
        }
    }
    function getPluginById($id) {
        $plugin_factory = $this->_getPluginFactory();
        $p = $plugin_factory->getPluginById($id);
        return $p;
    }
    function pluginIsCustom($plugin) {
        $plugin_factory = $this->_getPluginFactory();
        $p = $plugin_factory->pluginIsCustom($plugin);
        return $p;
    }
    
    var $plugins_name;
    function getNameForPlugin($plugin) {
        if (!$this->plugins_name) {
            $this->plugins_name = array();
        }
        if (!isset($this->plugins_name[$plugin->getId()])) {
            $plugin_factory = $this->_getPluginFactory();
            $this->plugins_name[$plugin->getId()] = $plugin_factory->getNameForPlugin($plugin);
        }
        return $this->plugins_name[$plugin->getId()];
    }

    function getAllowedProjects($plugin) {
        $prjIds = null;
        //if($plugin->getScope() == Plugin::SCOPE_PROJECT) {
        $plugin_factory = $this->_getPluginFactory();
        $prjIds = $plugin_factory->getProjectsByPluginId($plugin);
        //}
        return $prjIds;
    }
    
    function _updateProjectForPlugin($action, $plugin, $projectIds) {
        $plugin_factory = $this->_getPluginFactory();
        
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
        $plugin_factory = $this->_getPluginFactory();
        return $plugin_factory->isProjectPluginRestricted($plugin);
    }

    function updateProjectPluginRestriction($plugin, $restricted) {
        $plugin_factory = $this->_getPluginFactory();
        $plugin_factory->restrictProjectPluginUse($plugin, $restricted);
        if($restricted == false) {
            $plugin_factory->truncateProjectPlugin($plugin);
        }
    }

    function isPluginAllowedForProject($plugin, $projectId) {
        if($this->isProjectPluginRestricted($plugin)) {
            $plugin_factory = $this->_getPluginFactory();
            return $plugin_factory->isPluginAllowedForProject($plugin, $projectId);
        }
        else {
            return true;
        }
    }

    /**
     * This method instantiate a plugin that should not be used outside
     * of installation use case. It bypass all caches and do not check availability
     * of the plugin.
     *
     * @param string $name The name of the plugin (docman, tracker, …)
     * @return Plugin
     */
    public function getPluginDuringInstall($name) {
        return $this->_getPluginFactory()->instantiatePlugin(0, $name);
    }

    private function copyDirectory($source, $destination) {

        if(!is_dir($destination)) {
            if(!mkdir($destination)) {
                return false;
            }
        }

        $iterator = new DirectoryIterator($source);
        foreach($iterator as $file) {
            if($file->isFile()) {
                copy($file->getRealPath(), "$destination/" . $file->getFilename());
            } else if(!$file->isDot() && $file->isDir()) {
                $this->copyDirectory($file->getRealPath(), "$destination/$file");
            }
        }
    }

    /** @return ServiceManager */
    private function getServiceManager() {
        return ServiceManager::instance();
    }
}
?>
