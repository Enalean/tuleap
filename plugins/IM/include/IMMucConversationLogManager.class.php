<?php
/**
 * @copyright Copyright (c) Xerox Corporation, CodeX Team, 2001-2008. All rights reserved
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 *
 * IMMucConversationLogManager
 */

require_once('common/plugin/PluginManager.class.php');
require_once('IM.class.php');
require_once('PluginIMMucConversationLogDao.class.php');
require_once('IMMucConversationLog.class.php');
        
class IMMucConversationLogManager {

	// the controler of the IM plugin
    private $_controler;
	private static $_mucconversationlogmanager_instance;

	public static function getMucConversationLogManagerInstance() {
        if ( ! $this->_mucconversationlogmanager_instance) {
            $this->_mucconversationlogmanager_instance = new IMMucConversationLogManager();
        }
        return $this->_mucconversationlogmanager_instance;
    }
	
	private function _getIMPlugin() {
        $plugin_manager =& PluginManager::instance();
        $im_plugin = $plugin_manager->getPluginByName('IM');
        return $im_plugin;
    }
    
	public function IMMucConversationLogManager() {
		// set the IM plugin controler
        $this->_controler = new IM($this->_getIMPlugin());
    }
    
    public function getConversationLogsByGroupName($codendi_unix_group_name) {
		$dao = new PluginIMMucConversationLogDao(IMDataAccess::instance($this->_controler));
    	$dar = $dao->searchByMucName($codendi_unix_group_name);
    	if ($dar && $dar->valid()) {
    		$logs = array();
    		while ($dar->valid()) {
    			$row = $dar->current();
    			if ($row['body'] != null) {	// can happen when user change topic name for instance
            		$current_conv = new IMMucConversationLog($row['logTime'], $row['nickname'], $row['body']);
            		$logs[] = $current_conv;
    			}
    			$dar->next();
    		}
    		return $logs;
        } else {
            return false;
        }
    }
    
}
?>
