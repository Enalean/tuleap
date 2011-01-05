<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2010. All rights reserved
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

require_once('common/dao/FRSLogDao.class.php');

class FRSLog {
    
    protected function __construct() {
        $em = EventManager::instance();
        $eventToListen = array('frs_create_package',
                               'frs_update_package', 
                               'frs_delete_package', 
                               'frs_create_release',
                               'frs_update_release', 
                               'frs_delete_release', 
                               'frs_create_file',
                               'frs_update_file', 
                               'frs_delete_file', 
                               'frs_restore_file'
        );

        foreach($eventToListen as $event) {
            $em->addListener($event, $this, 'addLog', true, 0);
        }
    }

    protected static $_instance;
    public static function instance() {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }
        return self::$_instance;
    }


    /**
     * Process the event add_log
     */
    function addLog($event, $params) {
        $userID = $params['user_id'];
        $projectID = $params['project_id'];
        $itemID = $params['item_id'];
        switch ($event) {
            case 'frs_create_package' :
                $actionID = FRSPackage::EVT_CREATE;
                break;
            case 'frs_update_package' :
                $actionID = FRSPackage::EVT_UPDATE;
                break;
            case 'frs_delete_package' :
                $actionID = FRSPackage::EVT_DELETE;
                break;
            case 'frs_create_release' :
                $actionID = FRSRelease::EVT_CREATE;
                break;
            case 'frs_update_release' :
                $actionID = FRSRelease::EVT_UPDATE;
                break;
            case 'frs_delete_release' :
                $actionID = FRSRelease::EVT_DELETE;
                break;
            case 'frs_create_file' :
                $actionID = FRSFile::EVT_CREATE;
                break;
            case 'frs_update_file' :
                $actionID = FRSFile::EVT_UPDATE;
                break;
            case 'frs_delete_file' :
                $actionID = FRSFile::EVT_DELETE;
                break;
            case 'frs_restore_file' :
                $actionID = FRSFile::EVT_RESTORE;
                break;
        }
        $dao = new FRSLogDao(CodendiDataAccess::instance());
        $dao->addLog($userID, $projectID, $itemID, $actionID);
    }

}

?>