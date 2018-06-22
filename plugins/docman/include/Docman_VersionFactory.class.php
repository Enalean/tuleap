<?php
/*
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 * Copyright (c) Enalean 2016. All rights reserved
 *
 * Originally written by Manuel Vacelet, 2006
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
require_once('Docman_VersionDao.class.php');
require_once('Docman_Version.class.php');
/**
 * VersionFactory is a transport object (aka container) used to share data between
 * Model/Controler and View layer of the application
 */
class Docman_VersionFactory {
    
    function __construct() {
    }
    
    function create($row) {
        $dao = $this->_getVersionDao();
        return $dao->createFromRow($row);
    }
    var $dao;
    function _getVersionDao() {
        if (!$this->dao) {
            $this->dao = new Docman_VersionDao(CodendiDataAccess::instance());
        }
        return $this->dao;
    }

    function _getEventManager() {
        return EventManager::instance();
    }

    function _getItemFactory() {
        return new Docman_ItemFactory();
    }

    function _getUserManager() {
        return UserManager::instance();
    }

    function getAllVersionForItem(&$item) {
        $dao = $this->_getVersionDao();
        $dar = $dao->searchByItemId($item->getId());
        $versions = false;
        if ($dar && !$dar->isError()) {
            $versions = array();
            while ($dar->valid()) {
                $row = $dar->current();
                $versions[] = new Docman_Version($row);
                $dar->next();
            }
        }
        return $versions;
    }

    public function getCurrentVersionForItem($item) {
        $all_versions = $this->getAllVersionForItem($item);

        if (! empty($all_versions)) {
            return $all_versions[0];
        }

        return null;
    }

    function getSpecificVersion($item, $number) {
        $dao = $this->_getVersionDao();
        $dar = $dao->searchByNumber($item->getId(), $number);
        $version = null;
        if ($dar && !$dar->isError() && $dar->valid()) {
            $version = new Docman_Version($dar->current());
        }
        return $version;
    }
    /**
     * Returns the version of a given id
     *
     * @param Integer $id    Id of the version
     * @param String  $table Table name
     *
     * @return Docman_Version | null
     */
    function getSpecificVersionById($id, $table = 'plugin_docman_version_deleted') {
        $dao = $this->_getVersionDao();
        $dar = $dao->searchById($id, $table);
        $version = null;
        if ($dar && !$dar->isError() && $dar->valid()) {
            $version = new Docman_Version($dar->current());
        }
        return $version;
    }


    /**
     * Retrieve the next available version number for a file
     *
     * @param Docman_File $item
     *
     * @return Integer
     */
    function getNextVersionNumber($item) {
        $dao = $this->_getVersionDao();
        return $dao->searchNextVersionNumber($item->getId());
    }

    /**
     * Delete given version of document
     * 
     * @param Docman_Version $item
     * @param Integer        $number
     * 
     * @return Boolean
     */
    function deleteSpecificVersion($item, $number) {
        // The event must be processed before the version is deleted
        $version = $this->getSpecificVersion($item, $number) ;
        $user    = $this->_getUserManager()->getCurrentUser();
        $version->fireDeleteEvent($item, $user);
        $dao = $this->_getVersionDao();
        return $dao->deleteSpecificVersion($item->getId(), $number);
    }

    /**
     * Physically remove files related to deleted versions
     *
     * @param Integer $time
     *
     * @return Boolean
     */
    public function purgeDeletedVersions($time) {
        $dao = $this->_getVersionDao();
        $dar = $dao->listVersionsToPurge($time);
        if ($dar && !$dar->isError()) {
            foreach ($dar as $row) {
                $version = new Docman_Version($row);
                $this->purgeDeletedVersion($version);
            }
            return true;
        }
        return false;
    }

    /**
     * Invoque ''archive deleted item' hook in order to make a backup of a given item version.
     * This method should be used whithin the deleted docman version purge process
     *
     * @param Docman_Version $version Deleted docman item version
     *
     * @return Void
     */
    public function archiveBeforePurge($version)
    {
        $item    = $this->_getItemFactory()->getItemFromDb($version->getItemId(), array('ignore_deleted' => true));
        $prefix  = $item->getGroupId().'_i'.$version->getItemId().'_v'.$version->getNumber();
        $status  = true;
        $error   = null;
        $params  = array(
            'source_path'     => $version->getPath(),
            'archive_prefix'  => $prefix,
            'status'          => &$status,
            'error'           => &$error,
            'skip_duplicated' => false
        );

        $this->_getEventManager()->processEvent('archive_deleted_item', $params);

        return $params['status'];
    }

    /**
     * Physically remove the given version from the filesystem
     *
     * @param Docman_Version $version
     *
     * @return Boolean
     */
    public function purgeDeletedVersion($version)
    {
        $successfully_purged = $this->archiveBeforePurge($version);
        if ($successfully_purged) {
            if (file_exists($version->getPath()) && $this->physicalDeleteVersion($version->getPath())) {
                $dao = $this->_getVersionDao();
                return $dao->setPurgeDate($version->getId(), time());
            }
        }
        return false;
    }


    /**
     * Restore one version
     * 
     * @param Docman_Version $version
     * 
     * @return Boolean
     */
    public function restore($version) {
        $dao = $this->_getVersionDao();
        $dar = $dao->searchDeletedVersion($version->getItemId(), $version->getNumber());
        if ($dar && !$dar->isError()) {
            $row = $dar->getRow();
            if (!$row['purge_date'] && file_exists($row['path'])) {
                if ($dao->restore($version->getItemId(), $version->getNumber())) {
                    // Log the event
                    // Take into account deleted items because, when we restore a deleted item
                    // the versions are restored before the item (because we restore the item
                    // only if at least one version was restored successfully
                    $item  = $this->_getItemFactory()->getItemFromDb($version->getItemId(), array('ignore_deleted' => true));
                    $user  = $this->_getUserManager()->getCurrentUser();
                    $value = $version->getNumber();
                    if ($row['label'] !== '') {
                        $value .= ' ('.$row['label'].')';
                    }
                    $this->_getEventManager()->processEvent('plugin_docman_event_restore_version', array(
                          'group_id'   => $item->getGroupId(),
                          'item'       => $item,
                          'old_value'  => $value,
                          'user'       => $user)
                    );
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param  String  $docman_path
     * @param  Project $project
     * @param  String  $new_name
     * @return Boolean
     */
    function renameProject($docman_path, $project, $new_name){
        $updateSystem = rename($docman_path.$project->getUnixName(true), $docman_path.strtolower($new_name));
        if ($updateSystem){
            $dao = $this->_getVersionDao();
            return $dao->renameProject($docman_path, $project, $new_name);
        }
        return false;
    }
    
        /**
     * List pending versions ( marked as deleted but not physically removed yet)
     * in order to ease the restore
     *
     * @param Integer $groupId
     * @param Integer $offset
     * @param Integer $limit
     *
     * @return Array
     */
    function listPendingVersions($groupId, $offset, $limit) {
        $dao = $this->_getVersionDao();
        return $dao->listPendingVersions($groupId, $offset, $limit);
    }

    /**
     * List versions of the item that are deleted but not already purged
     *
     * @param Docman_Item $item
     *
     * @return Array()
     */
    function listVersionsToPurgeForItem($item) {
        $dao = $this->_getVersionDao();
        $dar = $dao->listVersionsToPurgeByItemId($item->getId());
        if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
            $list = array();
            foreach ($dar as $row) {
                $version = new Docman_Version($row);
                $list[] = $version;
            }
            return $list;
        }
        return false;
    }

    /**
     * Wrapper to unlink
     *
     * @param String $path
     *
     * @return Boolean
     */
    function physicalDeleteVersion($path) {
        if (unlink($path)) {
            return true;
        }
        return false;
    }
}
