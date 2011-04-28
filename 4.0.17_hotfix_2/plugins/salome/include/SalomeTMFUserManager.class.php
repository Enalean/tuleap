<?php
/**
 * @copyright Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 *
 * SalomeTMFUserManager
 */

require_once('common/plugin/PluginManager.class.php');
require_once('salome.class.php');
require_once('PluginSalomeUserDao.class.php');
require_once('SalomeTMFUser.class.php');
        
class SalomeTMFUserManager {

    // the controler of the salome plugin
    var $_controler;
    
    function SalomeTMFUserManager() {
        // set the salome plugin controler
        $this->_controler =& new salome($this->_getSalomePlugin());
    }
    
    function &instance() {
        static $_salomeusermanager_instance;
        if (!$_salomeusermanager_instance) {
            $_salomeusermanager_instance = new SalomeTMFUserManager();
        }
        return $_salomeusermanager_instance;
    }
    
    /* private */ function _getSalomePlugin() {
        $plugin_manager =& PluginManager::instance();
        $salome_plugin =& $plugin_manager->getPluginByName('salome');
        return $salome_plugin;
    }
    
    /**
     * Get the Salome user with salome user ID $salome_user_id
     *
     * @param int $salome_user_id the salome user ID
     * @return {SalomeTMFUser} the salome user, or false if the user is not found
     */
    function getSalomeUserFromSalomeUserID($salome_user_id) {
        $salome_dao =& new PluginSalomeUserDao(SalomeDataAccess::instance($this->_controler));
        $salome_dar = $salome_dao->searchBySalomeID($salome_user_id);
        if ($salome_dar && $salome_dar->valid()) {
            $row = $salome_dar->current();
            $salome_user_id = $row['id_personne'];
            return new SalomeTMFUser($salome_user_id, $row);
        } else {
            return false;
        }
    }
    
    /**
     * Get the Salome user with login $codendi_username
     *
     * @param string $codendi_username the codendi user name
     * @return {SalomeTMFUser} the salome user, or false if the user is not found
     */
    function getSalomeUserFromCodendiUsername($codendi_username) {
        $salome_dao =& new PluginSalomeUserDao(SalomeDataAccess::instance($this->_controler));
        $salome_dar = $salome_dao->searchByLogin($codendi_username);
        if ($salome_dar && $salome_dar->valid()) {
            $row = $salome_dar->current();
            $salome_user_id = $row['id_personne'];
            return new SalomeTMFUser($salome_user_id, $row);
        } else {
            return false;
        }
    }
    
    /**
     * Create a salome User with login $codendi_username.
     * If a salome user with such a login already exist, 
     * we don't create it and we just return the salome user ID
     *
     * @param string $codendi_username the codendi user name
     * @return int the ID of the user, or false if the creation failed
     */
    function createSalomeUser($codendi_username) {
        $u = $this->getSalomeUserFromCodendiUsername($codendi_username);
        if (! $u) {
            $salome_dao =& new PluginSalomeUserDao(SalomeDataAccess::instance($this->_controler));
            if ($salome_dao->create($codendi_username)) {
                $salome_user_id = $salome_dao->da->lastInsertId();
                return $salome_user_id;
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_salome','project_creation_user_not_created'));
                return false;
            }
        } else {
            return $u->getID();
        }
    }

}

?>
