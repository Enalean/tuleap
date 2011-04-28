<?php
/**
 * @copyright Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 *
 * SalomeTMFProxyManager
 */

require_once('SalomeTMFProxy.class.php');
require_once('PluginSalomeProxyDao.class.php');
        
class SalomeTMFProxyManager {

    function SalomeTMFProxyManager() {
    }
    
    function &instance() {
        static $_salomeproxymanager_instance;
        if (!$_salomeproxymanager_instance) {
            $_salomeproxymanager_instance = new SalomeTMFProxyManager();
        }
        return $_salomeproxymanager_instance;
    }
    
    /**
     * Get the Salome proxy with codendi user ID $codendi_user_id
     *
     * @param int $codendi_user_id the codendi user ID
     * @return {SalomeTMFProxy} the salome proxy, or false if the proxy is not found
     */
    function getSalomeProxyFromCodendiUserID($codendi_user_id) {
        $dao =& new PluginSalomeProxyDao(CodendiDataAccess::instance());
        $dar = $dao->searchByCodendiUserID($codendi_user_id);
        if ($dar && $dar->valid()) {
            $row = $dar->current();
            return new SalomeTMFProxy($row['proxy'], $row['proxy_user'], $row['proxy_password'], ($row['active'] == 1));
        } else {
            return false;
        }
    }
    
    /**
     * Create a salome Proxy
     *
     * @param int $codendi_user_id the Id of the Codendi user
     * @param string $proxy the URL of the proxy, with port if needed (http://proxy.net:8000)
     * @param string proxy_user the name of the user for proxy authentification (optional)
     * @param string proxy_password the password of the user for proxy authentification (optional)
     * * @param boolean $proxy_enabled true if the proxy is enabled, false otherwise
     * @return true if the creation succeed or false if the creation failed
     */
     function createSalomeProxy($codendi_user_id, $proxy, $proxy_user, $proxy_password, $proxy_enabled) {
        $dao =& new PluginSalomeProxyDao(CodendiDataAccess::instance());
        return $dao->createProxy($codendi_user_id, $proxy, $proxy_user, $proxy_password, $proxy_enabled);
    }
    
    /**
     * Update a salome Proxy
     *
     * @param int $codendi_user_id the Id of the Codendi user
     * @param string $proxy the URL of the proxy, with port if needed (http://proxy.net:8000)
     * @param string $proxy_user the name of the user for proxy authentification (optional)
     * @param string $proxy_password the password of the user for proxy authentification (optional)
     * @param boolean $proxy_enabled true if the proxy is enabled, false otherwise
     * @return boolean false if the update failed.
     */
     function updateSalomeProxy($codendi_user_id, $proxy, $proxy_user, $proxy_password, $proxy_enabled) {
        $dao =& new PluginSalomeProxyDao(CodendiDataAccess::instance());
        if (! $dao->updateProxy($codendi_user_id, $proxy, $proxy_user, $proxy_password, $proxy_enabled)) {
            return false;
        } else {
            return true;
        }
    }
}

?>