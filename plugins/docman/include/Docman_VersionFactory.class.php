<?php
/*
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
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
    
    function Docman_VersionFactory() {
    }
    
    function create($row) {
        $dao =& $this->_getVersionDao();
        return $dao->createFromRow($row);
    }
    var $dao;
    function &_getVersionDao() {
        if (!$this->dao) {
            $this->dao =& new Docman_VersionDao(CodendiDataAccess::instance());
        }
        return $this->dao;
    }
    
    function getAllVersionForItem(&$item) {
        $dao =& $this->_getVersionDao();
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
        $dao = $this->_getVersionDao();
        return $dao->deleteSpecificVersion($item->getId(), $number);
       
        
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
}

?>