<?php
/**
 * @copyright Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 *
 * SalomeTMFPluginsManager
 */

require_once('SalomeTMFPluginSalome.class.php');
require_once('PluginSalomeActivatedPluginsDao.class.php');

class SalomeTMFPluginsManager {
    
    /**
     * Array of activated plugins for a given project
     * [group_id] => {plugin1, plugin2, plugin3, ...}
     */
    var $_activated_plugins;
    var $_controler;
    
    function SalomeTMFPluginsManager($controler) {
        $this->_activated_plugins = array();
        $this->_controler = $controler;
    }
    
    /**
     * Returns the plugins directory 
     * 
     * @param string $mode_base : 'j' for JDBC or 's' for SOAP
     * @return string the plugins directory
     */
    function getPluginsDirectory($mode_base) {
        return dirname(__FILE__) .'/../www/' . $mode_base . '/plugins';
    }
    
    
    /**
     * Return all the SalomeTMF available (installed) plugins.
     * A SalomeTMF plugin is available if it has been properly installed,
     * that means there is a directory with the plugin name in the directory
     * {salome install reprtory}/plugins/
     * 
     * @param string $mode_base : 'j' for JDBC or 's' for SOAP
     * @return array of {SalomeTMFPlugin}
     */
    function getAvailablePlugins($mode_base) {
        
        $salome_plugins_directory = $this->getPluginsDirectory($mode_base);
        
        $available_plugins = array();
        $resource = opendir($salome_plugins_directory);
	    if ($resource !== false) {
	        while (false !== ($item = readdir($resource))) {
	            if ($item != "." && $item != ".." && $item != ".svn") {
	                if (is_dir($salome_plugins_directory."/".$item)) {
	                    $available_plugins[$item] = new SalomeTMFPluginSalome($item, $salome_plugins_directory."/".$item);
	                }
	            }
	        }
	        closedir($resource);
	    } else {
	        return false;
	    }
        return $available_plugins;
    }
    
    /**
     * Add the plugin $plugin_name to the configuration of Salome
     * for the project with ID $group_id.
     *
     * @param string $plugin_name the name of the plugin to add
     * @param int $group_id the ID of the project
     * @return boolean true if the plugin has been added, false otherwise
     */
    function addPlugin($plugin_name, $group_id) {
        // TO DO
    }
    
    /**
     * Remove the plugin $plugin_name to the configuration of Salome
     * for the project with ID $group_id. The plugin still be installed
     * and available for other groups, and it will be still be possible 
     * to add it in the future.
     *
     * @param string $plugin_name the name of the plugin to remove
     * @param int $group_id the ID of the project
     * @return boolean true if the plugin has been removed, false otherwise
     */
    function removePlugin($plugin_name, $group_id) {
        // TO DO
    }
    
    /**
     * Set the plugins contained in the array $plugins activated to the 
     * configuration of Salome for the project with ID $group_id.
     *
     * @param array of string $plugins an array of plugins' name
     * @param int $group_id the ID of the project
     * @return boolean true if the plugins have been set, false otherwise
     */
    function setPlugins($plugins, $group_id) {
        //Check that plugins exist
        if ($GLOBALS['disable_soap']) {
            $available_plugins = $this->getAvailablePlugins('j');
        } else {
            $available_plugins = array_merge($this->getAvailablePlugins('j'), $this->getAvailablePlugins('s'));
        }
        foreach($plugins as $key => $name) {
            if (!isset($available_plugins[$name])) {
                unset($plugins[$key]);
            }
        }
        $dao = new PluginSalomeActivatedPluginsDao(CodendiDataAccess::instance());
        return $dao->storePlugins($group_id, $plugins);
    }
    
    /**
     * Return true if the plugin $plugin_name is activated in the configuration 
     * of Salome for the project with ID $group_id.
     *
     * @param string $plugin_name the name of the plugin
     * @param int $group_id the ID of the project
     * @return boolean true if the plugin is activated for the project, false otherwise
     */
    function isPluginActivated($plugin_name, $group_id) {
        if (isset($this->_activated_plugins[$group_id])) {
            return in_array($plugin_name, $this->_activated_plugins[$group_id]);
        } else {
            $activated_plugins = $this->getActivatedPlugins($group_id);
            if ($activated_plugins !== false) {
                return in_array($plugin_name, $activated_plugins);
            } else {
                return false;
            }
        }
    }
    
    /**
     * Return the array of activated plugins in the configuration 
     * of Salome for the project with ID $group_id.
     *
     * @param int $group_id the ID of the project
     * @return array of string the array of activated plugins for the project, or false if an error occured
     */
    function getActivatedPlugins($group_id) {
        if (!isset($this->_activated_plugins[$group_id])) {
            $this->_activated_plugins[$group_id] = array();
            $dao = new PluginSalomeActivatedPluginsDao(CodendiDataAccess::instance());
            $dar = $dao->searchByGroupId($group_id);
            foreach($dar as $row) {
                $this->_activated_plugins[$group_id][] = $row['name'];
            }
        }
        return $this->_activated_plugins[$group_id];
    }
    
}

?>
