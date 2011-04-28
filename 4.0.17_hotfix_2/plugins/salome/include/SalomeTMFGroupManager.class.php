<?php
/**
 * @copyright Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 *
 * SalomeTMFGroupManager
 */

require_once('common/plugin/PluginManager.class.php');
require_once('salome.class.php');
require_once('PluginSalomeGroupDao.class.php');
require_once('SalomeTMFGroup.class.php');
        
class SalomeTMFGroupManager {

    // the controler of the salome plugin
    var $_controler;
    
    function SalomeTMFGroupManager() {
        // set the salome plugin controler
        $this->_controler =& new salome($this->_getSalomePlugin());
    }
    
    function &instance() {
        static $_salomegroupmanager_instance;
        if (!$_salomegroupmanager_instance) {
            $_salomegroupmanager_instance = new SalomeTMFGroupManager();
        }
        return $_salomegroupmanager_instance;
    }
    
    /* private */ function _getSalomePlugin() {
        $plugin_manager =& PluginManager::instance();
        $salome_plugin =& $plugin_manager->getPluginByName('salome');
        return $salome_plugin;
    }
    
    /**
     * Get the Salome group with salome group ID $salome_group_id
     *
     * @param int $salome_group_id the salome group ID
     * @return {SalomeTMFGroup} the salome group, or false if the group is not found
     */
    function getSalomeGroupFromSalomeGroupID($salome_group_id) {
        $salome_dao =& new PluginSalomeGroupDao(SalomeDataAccess::instance($this->_controler));
        $salome_dar = $salome_dao->searchBySalomeGroupId($salome_group_id);
        if ($salome_dar && $salome_dar->valid()) {
            $row = $salome_dar->current();
            $salome_group_id = $row['id_groupe'];
            return new SalomeTMFGroup($salome_group_id, $row);
        } else {
            return false;
        }
    }
    
    /**
     * Get the Salome group with salome group name $salome_group_name in the project $salome_project_id
     *
     * @param int $salome_project_id the salome project ID
     * @param string $salome_group_name the salome group name
     * @return {SalomeTMFGroup} the salome group, or false if the group is not found
     */
    function getSalomeGroupFromSalomeGroupName($salome_project_id, $salome_group_id) {
        $salome_dao =& new PluginSalomeGroupDao(SalomeDataAccess::instance($this->_controler));
        $salome_dar = $salome_dao->searchByName($salome_project_id, $salome_group_id);
        if ($salome_dar && $salome_dar->valid()) {
            $row = $salome_dar->current();
            $salome_group_id = $row['id_groupe'];
            return new SalomeTMFGroup($salome_group_id, $row);
        } else {
            return false;
        }
    }
    
    /**
     * Return true of the Salome user belongs to to the Salome group 
     * corresponding with the Codendi UGroup $codendi_ugroup_id of the project $codendi_group_id
     *
     * @param int $salome_user_id the salome user ID
     * @param int $codendi_group_id the codendi group ID
     * @param int $codendi_ugroup_id the codendi ugroup ID
     * @return boolean true if the user belongs to the group, false otherwise
     */
    function isSalomeUserMemberOf($salome_user_id, $codendi_group_id, $codendi_ugroup_id) {
        $salome_dao =& new PluginSalomeGroupDao(SalomeDataAccess::instance($this->_controler));
        $salome_group = $this->getSalomeGroupFromCodendiUGroupID($codendi_group_id, $codendi_ugroup_id);
        if ($salome_group) {
            return $salome_dao->isUserMemberOf($salome_user_id, $salome_group->getID());
        } else {
            return false;
        }
    }
    
    /**
     * Get the Salome group corresponding with the Codendi UGroup $codendi_ugroup_id of the project $codendi_group_id
     *
     * @param int $codendi_group_id the codendi group ID
     * @param int $codendi_ugroup_id the codendi ugroup ID
     * @return {SalomeTMFGroup} the salome group, or false if the group is not found
     */
    function getSalomeGroupFromCodendiUGroupID($codendi_group_id, $codendi_ugroup_id) {
        $salome_dao =& new PluginSalomeGroupDao(SalomeDataAccess::instance($this->_controler));
        $salome_dar = $salome_dao->searchByUGroupId($codendi_group_id, $codendi_ugroup_id);
        if ($salome_dar && $salome_dar->valid()) {
            $row = $salome_dar->current();
            $salome_group_id = $row['id_groupe'];
            return new SalomeTMFGroup($salome_group_id, $row);
        } else {
            return false;
        }
    }
    
    /**
     * Add Salome User $salome_user_id in Salome Group corresponding with
     * Codendi ugroup $codendi_ugroup_id of project group_id
     *
     * @param int $salome_user_id ID of the salome user
     * @param int $codendi_group_id ID of the codendi project
     * @param int $codendi_ugroup_id ID of the codendi ugroup
     * @param boolean false if the addition failed.
     */
    function addUserInGroup($salome_user_id, $codendi_group_id, $codendi_ugroup_id) {
        $salome_group = $this->getSalomeGroupFromCodendiUGroupID($codendi_group_id, $codendi_ugroup_id);
        if ($salome_group) {
            return $this->addUserInSalomeGroup($salome_user_id, $salome_group->getID());
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','salome_group_notfound', array(util_translate_name_ugroup(ugroup_get_name_from_id($codendi_ugroup_id)))));
            return false;
        }
    }
    
    /**
     * Add Salome User $salome_user_id in Salome Group $salome_group_id
     *
     * @param int $salome_user_id ID of the salome user
     * @param int $salome_group_id ID of the salome group
     * @param boolean false if the addition failed.
     */
    function addUserInSalomeGroup($salome_user_id, $salome_group_id) {
        $salome_dao =& new PluginSalomeGroupDao(SalomeDataAccess::instance($this->_controler));
        return $salome_dao->addUserInGroup($salome_user_id, $salome_group_id);
    }
    
    /**
     * Remove Salome User $salome_user_id from Salome Group corresponding with
     * Codendi ugroup $codendi_ugroup_id of project group_id
     *
     * @param int $salome_user_id ID of the salome user
     * @param int $codendi_group_id ID of the codendi project
     * @param int $codendi_ugroup_id ID of the codendi ugroup
     * @param boolean false if the deletion failed.
     */
    function removeUserInGroup($salome_user_id, $codendi_group_id, $codendi_ugroup_id) {
        $salome_group = $this->getSalomeGroupFromCodendiUGroupID($codendi_group_id, $codendi_ugroup_id);
        if ($salome_group) {
            $salome_dao =& new PluginSalomeGroupDao(SalomeDataAccess::instance($this->_controler));
            return $salome_dao->removeUserInGroup($salome_user_id, $salome_group->getID());
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','salome_group_notfound', array(util_translate_name_ugroup(ugroup_get_name_from_id($codendi_ugroup_id)))));
            return false;
        }
    }
    
    /**
     * Remove All Salome User from Salome Group corresponding with
     * Codendi ugroup $codendi_ugroup_id of project group_id
     *
     * @param int $codendi_group_id ID of the codendi project
     * @param int $codendi_ugroup_id ID of the codendi ugroup
     * @param boolean false if the deletion failed.
     */
    function removeAllUserInGroup($codendi_group_id, $codendi_ugroup_id) {
        $salome_group = $this->getSalomeGroupFromCodendiUGroupID($codendi_group_id, $codendi_ugroup_id);
        if ($salome_group) {
            $salome_dao =& new PluginSalomeGroupDao(SalomeDataAccess::instance($this->_controler));
            return $salome_dao->removeAllUserInGroup($salome_group->getID());
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','salome_group_notfound', array(util_translate_name_ugroup(ugroup_get_name_from_id($codendi_ugroup_id)))));
            return false;
        }
    }
    
    /**
     * Create a salome Group
     *
     * @return int the ID of the group just created, or false if the creation failed
     */
     function createSalomeGroup($salome_project_id, $name, $description, $permission) {
        $salome_dao =& new PluginSalomeGroupDao(SalomeDataAccess::instance($this->_controler));
        if ($salome_dao->createGroup($salome_project_id, $name, $description, $permission)) {
            $salome_group_id = $salome_dao->da->lastInsertId();
            return $salome_group_id;
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','project_creation_group_not_created'));
            return false;
        }
    }
    
    /**
     * Update a salome Group
     *
     * @return boolean false if the update failed
     */
     function updateSalomeGroup($codendi_group_id, $codendi_ugroup_id, $description) {
        $salome_dao =& new PluginSalomeGroupDao(SalomeDataAccess::instance($this->_controler));
        $salome_group = $this->getSalomeGroupFromCodendiUGroupID($codendi_group_id, $codendi_ugroup_id);
        if ($salome_group) {
            return $salome_dao->updateGroup($codendi_group_id, $codendi_ugroup_id, $description);
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','salome_group_notfound', array(util_translate_name_ugroup(ugroup_get_name_from_id($codendi_ugroup_id)))));
            return false;
        }
    }
    
    /**
     * Delete a salome Group
     *
     * @return boolean false if the deletion failed
     */
     function deleteSalomeGroup($salome_project_id, $name) {
        $salome_dao =& new PluginSalomeGroupDao(SalomeDataAccess::instance($this->_controler));
        return $salome_dao->deleteGroup($salome_project_id, $name);
    }
    
    /**
     * Delete all salome Group of a project
     *
     * @return boolean false if the deletion failed
     */
    function deleteAllSalomeGroups($salome_project_id) {
        $salome_dao =& new PluginSalomeGroupDao(SalomeDataAccess::instance($this->_controler));
        return $salome_dao->deleteAllGroups($salome_project_id);
    }

}

?>
