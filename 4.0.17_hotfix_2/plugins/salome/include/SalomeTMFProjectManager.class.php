<?php
/**
 * @copyright Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 *
 * SalomeTMFProjectManager
 */

require_once('common/plugin/PluginManager.class.php');
require_once('salome.class.php');
require_once('PluginSalomeProjectDao.class.php');
require_once('SalomeTMFProject.class.php');
        
class SalomeTMFProjectManager {

    // the controler of the salome plugin
    var $_controler;
    
    function SalomeTMFProjectManager() {
        // set the salome plugin controler
        $this->_controler =& new salome($this->_getSalomePlugin());
    }
    
    function &instance() {
        static $_salomeprojectmanager_instance;
        if (!$_salomeprojectmanager_instance) {
            $_salomeprojectmanager_instance = new SalomeTMFProjectManager();
        }
        return $_salomeprojectmanager_instance;
    }
    
    /* private */ function _getSalomePlugin() {
        $plugin_manager =& PluginManager::instance();
        $salome_plugin =& $plugin_manager->getPluginByName('salome');
        return $salome_plugin;
    }
    
    /**
     * Get the Salome project with salome project ID $salome_project_id
     *
     * @param int $salome_project_id the salome project ID
     * @return {SalomeTMFProject} the salome project, or false if the project is not found
     */
    function getSalomeProjectFromSalomeProjectID($salome_project_id) {
        $salome_dao =& new PluginSalomeProjectDao(SalomeDataAccess::instance($this->_controler));
        $salome_dar = $salome_dao->searchBySalomeProjectId($salome_project_id);
        if ($salome_dar && $salome_dar->valid()) {
            $row = $salome_dar->current();
            $salome_project_id = $row['id_projet'];
            return new SalomeTMFProject($salome_project_id, $row);
        } else {
            return false;
        }
    }
    
    /**
     * Get the Salome project corresponding with the Codendi project $codendi_group_id
     *
     * @param int $codendi_group_id the codendi group ID
     * @return {SalomeTMFProject} the salome project, or false if the project is not found
     */
    function getSalomeProjectFromCodendiGroupID($codendi_group_id) {
        $salome_dao =& new PluginSalomeProjectDao(SalomeDataAccess::instance($this->_controler));
        $salome_dar = $salome_dao->searchByGroupId($codendi_group_id);
        if ($salome_dar && $salome_dar->valid()) {
            $row = $salome_dar->current();
            $salome_project_id = $row['id_projet'];
            return new SalomeTMFProject($salome_project_id, $row);
        } else {
            return false;
        }
    }
    
    /**
     * Create a salome Project
     *
     * @return int the ID of the project just created, or false if the creation failed
     */
     function createSalomeProject($codendi_group_id, $name, $description) {
        $salome_dao =& new PluginSalomeProjectDao(SalomeDataAccess::instance($this->_controler));
        $project_id = $salome_dao->create($codendi_group_id, $name, $description);
        return $project_id;
    }
    
    /**
     * Update a salome Project
     *
     * @param int $codendi_group_id ID of the codendi project
     * @param string $name new name of the project
     * @param string $description new description of the project
     * @return boolean false if the update failed.
     */
     function updateSalomeProject($codendi_group_id, $name, $description) {
        $salome_dao =& new PluginSalomeProjectDao(SalomeDataAccess::instance($this->_controler));
        if (! $salome_dao->updateByGroupId($codendi_group_id, $name, $description)) {
            return false;
        } else {
            return true;
        }
    }
}
?>
