<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 *
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com> 
 *
 * IMMucLogManager
 */

require_once('common/plugin/PluginManager.class.php');
require_once('IM.class.php');

require_once('PluginIMMucConversationLogDao.class.php');
require_once('PluginIMMucChangeTopicLogDao.class.php');
require_once('PluginIMMucJoinTheRoomLogDao.class.php');
require_once('PluginIMMucLeftTheRoomLogDao.class.php');

require_once('IMMucConversationLog.class.php');
require_once('IMMucSystemLog.class.php');

class IMMucLogManager {

	// the controler of the IM plugin
    private $_controler;
	private static $_muclogmanager_instance;

	public static function getMucLogManagerInstance() {
        if ( ! self::$_muclogmanager_instance) {
            self::$_muclogmanager_instance = new IMMucLogManager();
        }
        return self::$_muclogmanager_instance;
    }
	
	private function _getIMPlugin() {
        $plugin_manager =& PluginManager::instance();
        $im_plugin = $plugin_manager->getPluginByName('IM');
        return $im_plugin;
    }
    
	public function __construct() {
		// set the IM plugin controler
        $this->_controler = new IM($this->_getIMPlugin());
    }
    
    public function getLogsByGroupName($codendi_unix_group_name) {
        $logs = array();
        
        // user messages
		$dao = new PluginIMMucConversationLogDao(IMDataAccess::instance($this->_controler));
    	$dar = $dao->searchByMucName($codendi_unix_group_name);
    	if ($dar && $dar->valid()) {
    		while ($dar->valid()) {
    			$row = $dar->current();
    			if ($row['body'] != null) {
            		$current_conv = new IMMucConversationLog($row['sentDate'], $row['nickname'], $row['fromJID'], $row['body']);
            		$logs[$row['sentDate']] = $current_conv;
    			}
    			$dar->next();
    		}
        }
        
        //
        // system messages
        //
        // Change Topic
        $dao = new PluginIMMucChangeTopicLogDao(IMDataAccess::instance($this->_controler));
        $dar = $dao->searchByMucName($codendi_unix_group_name);
        if ($dar && $dar->valid()) {
            while ($dar->valid()) {
                $row = $dar->current();
                if ($row['subject'] != null) {
                    $current_conv = new IMMucChangeTopicSystemLog($row['logTime'], $row['nickname'], $row['subject']);
                    $logs[$row['logTime']] = $current_conv;
                }
                $dar->next();
            }
        }
        // join the room
        $dao = new PluginIMMucJoinTheRoomLogDao(IMDataAccess::instance($this->_controler));
        $dar = $dao->searchByMucName($codendi_unix_group_name);
        if ($dar && $dar->valid()) {
            while ($dar->valid()) {
                $row = $dar->current();
                $current_conv = new IMMucJoinTheRoomSystemLog($row['joinedDate'], $row['nickname']);
                $logs[$row['joinedDate']] = $current_conv;
                $dar->next();
            }
        }
        // left the room
        $dao = new PluginIMMucLeftTheRoomLogDao(IMDataAccess::instance($this->_controler));
        $dar = $dao->searchByMucName($codendi_unix_group_name);
        if ($dar && $dar->valid()) {
            while ($dar->valid()) {
                $row = $dar->current();
                $current_conv = new IMMucLeftTheRoomSystemLog($row['leftDate'], $row['nickname']);
                $logs[$row['leftDate']] = $current_conv;
                $dar->next();
            }
        }
        
        // sort the events by date
        ksort($logs);
        
        return $logs;
    }
    
	public function getLogsByGroupNameBeforeDate($codendi_unix_group_name, $end_date) {
		$logs = array();
        
        // user messages
	    $dao = new PluginIMMucConversationLogDao(IMDataAccess::instance($this->_controler));
    	$dar = $dao->searchByMucNameBeforeDate($codendi_unix_group_name, $end_date);
    	if ($dar && $dar->valid()) {
    		while ($dar->valid()) {
    			$row = $dar->current();
    			if ($row['body'] != null) {
            		$current_conv = new IMMucConversationLog($row['sentDate'], $row['nickname'], $row['nickname'], $row['body']);
            		$logs[$row['sentDate']] = $current_conv;
    			}
    			$dar->next();
    		}
        }
        //
        // system messages
        //
        // Change Topic
        $dao = new PluginIMMucChangeTopicLogDao(IMDataAccess::instance($this->_controler));
        $dar = $dao->searchByMucNameBeforeDate($codendi_unix_group_name, $end_date);
        if ($dar && $dar->valid()) {
            while ($dar->valid()) {
                $row = $dar->current();
                if ($row['subject'] != null) {
                    $current_conv = new IMMucChangeTopicSystemLog($row['logTime'], $row['nickname'], $row['subject']);
                    $logs[$row['logTime']] = $current_conv;
                }
                $dar->next();
            }
        }
        // join the room
        $dao = new PluginIMMucJoinTheRoomLogDao(IMDataAccess::instance($this->_controler));
        $dar = $dao->searchByMucNameBeforeDate($codendi_unix_group_name, $end_date);
        if ($dar && $dar->valid()) {
            while ($dar->valid()) {
                $row = $dar->current();
                $current_conv = new IMMucJoinTheRoomSystemLog($row['joinedDate'], $row['nickname']);
                $logs[$row['joinedDate']] = $current_conv;
                $dar->next();
            }
        }
        // left the room
        $dao = new PluginIMMucLeftTheRoomLogDao(IMDataAccess::instance($this->_controler));
        $dar = $dao->searchByMucNameBeforeDate($codendi_unix_group_name, $end_date);
        if ($dar && $dar->valid()) {
            while ($dar->valid()) {
                $row = $dar->current();
                $current_conv = new IMMucLeftTheRoomSystemLog($row['leftDate'], $row['nickname']);
                $logs[$row['leftDate']] = $current_conv;
                $dar->next();
            }
        }
        
        // sort the events by date
        ksort($logs);
        
        return $logs;
    }

    public function getLogsByGroupNameAfterDate($codendi_unix_group_name, $start_date) {
		$logs = array();
        
        // user messages
        $dao = new PluginIMMucConversationLogDao(IMDataAccess::instance($this->_controler));
    	$dar = $dao->searchByMucNameAfterDate($codendi_unix_group_name, $start_date);
    	if ($dar && $dar->valid()) {
    		while ($dar->valid()) {
    			$row = $dar->current();
    			if ($row['body'] != null) {
            		$current_conv = new IMMucConversationLog($row['sentDate'], $row['nickname'], $row['nickname'], $row['body']);
            		$logs[$row['sentDate']] = $current_conv;
    			}
    			$dar->next();
    		}
        }
        //
        // system messages
        //
        // Change Topic
        $dao = new PluginIMMucChangeTopicLogDao(IMDataAccess::instance($this->_controler));
        $dar = $dao->searchByMucNameAfterDate($codendi_unix_group_name, $start_date);
        if ($dar && $dar->valid()) {
            while ($dar->valid()) {
                $row = $dar->current();
                if ($row['subject'] != null) {
                    $current_conv = new IMMucChangeTopicSystemLog($row['logTime'], $row['nickname'], $row['subject']);
                    $logs[$row['logTime']] = $current_conv;
                }
                $dar->next();
            }
        }
        // join the room
        $dao = new PluginIMMucJoinTheRoomLogDao(IMDataAccess::instance($this->_controler));
        $dar = $dao->searchByMucNameAfterDate($codendi_unix_group_name, $start_date);
        if ($dar && $dar->valid()) {
            while ($dar->valid()) {
                $row = $dar->current();
                $current_conv = new IMMucJoinTheRoomSystemLog($row['joinedDate'], $row['nickname']);
                $logs[$row['joinedDate']] = $current_conv;
                $dar->next();
            }
        }
        // left the room
        $dao = new PluginIMMucLeftTheRoomLogDao(IMDataAccess::instance($this->_controler));
        $dar = $dao->searchByMucNameAfterDate($codendi_unix_group_name, $start_date);
        if ($dar && $dar->valid()) {
            while ($dar->valid()) {
                $row = $dar->current();
                $current_conv = new IMMucLeftTheRoomSystemLog($row['leftDate'], $row['nickname']);
                $logs[$row['leftDate']] = $current_conv;
                $dar->next();
            }
        }
        
        // sort the events by date
        ksort($logs);
        
        return $logs;
    }
    
	public function getLogsByGroupNameBetweenDates($codendi_unix_group_name, $start_date, $end_date) {
		$logs = array();
		
		// user messages
	    $dao = new PluginIMMucConversationLogDao(IMDataAccess::instance($this->_controler));
    	$dar = $dao->searchByMucNameBetweenDates($codendi_unix_group_name, $start_date, $end_date);
    	if ($dar && $dar->valid()) {
    		while ($dar->valid()) {
    			$row = $dar->current();
    			if ($row['body'] != null) {
            		$current_conv = new IMMucConversationLog($row['sentDate'], $row['nickname'], $row['nickname'], $row['body']);
            		$logs[$row['sentDate']] = $current_conv;
    			}
    			$dar->next();
    		}
        }
        //
        // system messages
        //
        // Change Topic
        $dao = new PluginIMMucChangeTopicLogDao(IMDataAccess::instance($this->_controler));
        $dar = $dao->searchByMucNameBetweenDates($codendi_unix_group_name, $start_date, $end_date);
        if ($dar && $dar->valid()) {
            while ($dar->valid()) {
                $row = $dar->current();
                if ($row['subject'] != null) {
                    $current_conv = new IMMucChangeTopicSystemLog($row['logTime'], $row['nickname'], $row['subject']);
                    $logs[$row['logTime']] = $current_conv;
                }
                $dar->next();
            }
        }
        // join the room
        $dao = new PluginIMMucJoinTheRoomLogDao(IMDataAccess::instance($this->_controler));
        $dar = $dao->searchByMucNameBetweenDates($codendi_unix_group_name, $start_date, $end_date);
        if ($dar && $dar->valid()) {
            while ($dar->valid()) {
                $row = $dar->current();
                $current_conv = new IMMucJoinTheRoomSystemLog($row['joinedDate'], $row['nickname']);
                $logs[$row['joinedDate']] = $current_conv;
                $dar->next();
            }
        }
        // left the room
        $dao = new PluginIMMucLeftTheRoomLogDao(IMDataAccess::instance($this->_controler));
        $dar = $dao->searchByMucNameBetweenDates($codendi_unix_group_name, $start_date, $end_date);
        if ($dar && $dar->valid()) {
            while ($dar->valid()) {
                $row = $dar->current();
                $current_conv = new IMMucLeftTheRoomSystemLog($row['leftDate'], $row['nickname']);
                $logs[$row['leftDate']] = $current_conv;
                $dar->next();
            }
        }
        
        // sort the events by date
        ksort($logs);
        
        return $logs;
    }
    
}
?>
