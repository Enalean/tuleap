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

class FRSLog
{

    public $dao;

    /**
     * Constructor of the class.
     * It is also used to add FRSLog events to listen in EventManager.
     *
     * @return FRSLog
     */
    protected function __construct()
    {
        $em = EventManager::instance();
        $packageEventToListen = array('frs_create_package',
                                      'frs_update_package',
                                      'frs_delete_package'
        );
        foreach ($packageEventToListen as $event) {
            $em->addListener($event, $this, 'addLogPackage', true);
        }

        $releaseEventToListen = array('frs_create_release',
                                      'frs_update_release',
                                      'frs_delete_release'
        );
        foreach ($releaseEventToListen as $event) {
            $em->addListener($event, $this, 'addLogRelease', true);
        }

        $fileEventToListen = array('frs_create_file',
                                   'frs_update_file',
                                   'frs_delete_file',
                                   'frs_restore_file'
        );
        foreach ($fileEventToListen as $event) {
            $em->addListener($event, $this, 'addLogFile', true);
        }
    }

    protected static $_instance;

    /**
     * Singleton pattern
     *
     * @return FRSLog
     */
    public static function instance()
    {
        if (!isset(self::$_instance)) {
            $c = self::class;
            self::$_instance = new $c();
        }
        return self::$_instance;
    }

    /**
     * Add log for events on FRSPackage
     *
     * @param String $event
     * @param Array  $params
     *
     * @return void
     */
    public function addLogPackage($event, $params)
    {
        $userID    = $this->_getCurrentUser()->getId();
        $projectID = $params['group_id'];
        $itemID    = $params['item_id'];
        switch ($event) {
            case 'frs_create_package':
                $actionID = FRSPackage::EVT_CREATE;
                break;
            case 'frs_update_package':
                $actionID = FRSPackage::EVT_UPDATE;
                break;
            case 'frs_delete_package':
                $actionID = FRSPackage::EVT_DELETE;
                break;
        }
        $this->addLog($userID, $projectID, $itemID, $actionID);
    }

    /**
     * Add log for events on FRSRelease
     *
     * @param String $event
     * @param Array  $params
     *
     * @return void
     */
    public function addLogRelease($event, $params)
    {
        $userID    = $this->_getCurrentUser()->getId();
        $projectID = $params['group_id'];
        $itemID    = $params['item_id'];
        switch ($event) {
            case 'frs_create_release':
                $actionID = FRSRelease::EVT_CREATE;
                break;
            case 'frs_update_release':
                $actionID = FRSRelease::EVT_UPDATE;
                break;
            case 'frs_delete_release':
                $actionID = FRSRelease::EVT_DELETE;
                break;
        }
        $this->addLog($userID, $projectID, $itemID, $actionID);
    }

    /**
     * Add log for events on FRSFile
     *
     * @param String $event
     * @param Array  $params
     *
     * @return void
     */
    public function addLogFile($event, $params)
    {
        $userID    = $this->_getCurrentUser()->getId();
        $projectID = $params['group_id'];
        $itemID    = $params['item_id'];
        switch ($event) {
            case 'frs_create_file':
                $actionID = FRSFile::EVT_CREATE;
                break;
            case 'frs_update_file':
                $actionID = FRSFile::EVT_UPDATE;
                break;
            case 'frs_delete_file':
                $actionID = FRSFile::EVT_DELETE;
                break;
            case 'frs_restore_file':
                $actionID = FRSFile::EVT_RESTORE;
                break;
        }
        $this->addLog($userID, $projectID, $itemID, $actionID);
    }

    /**
     * Obtain an instance of FRSLogDao
     *
     * @return FRSLogDao
     */
    public function _getFRSLogDao()
    {
        if (!$this->dao) {
            $this->dao = new FRSLogDao();
        }
        return $this->dao;
    }

    /**
     * Store the event in DB
     *
     * @param int $userID
     * @param int $projectID
     * @param int $itemID
     * @param int $actionID
     *
     * @return void
     */
    public function addLog($userID, $projectID, $itemID, $actionID)
    {
        $dao = $this->_getFRSLogDao();
        $dao->addLog($userID, $projectID, $itemID, $actionID);
    }

    /**
     * Obtain the current user
     *
     * @return PFUser
     */
    public function _getCurrentUser()
    {
        $um = UserManager::instance();
        return $um->getCurrentUser();
    }
}
