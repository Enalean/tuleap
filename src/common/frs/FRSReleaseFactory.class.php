<?php

/**
 * Copyright (c) Xerox, 2006. All Rights Reserved.
 *
 * Originally written by Anne Hardyau, 2006
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */

require_once ('FRSRelease.class.php');
require_once ('common/dao/FRSReleaseDao.class.php');
require_once ('common/frs/FRSFileFactory.class.php');
require_once ('common/frs/FRSPackageFactory.class.php');
require_once('www/project/admin/ugroup_utils.php');
/**
 * 
 */
class FRSReleaseFactory {
    
    var $STATUS_ACTIVE = 1;
    var $STATUS_DELETED = 2;
    var $STATUS_HIDDEN = 3;
    

	function FRSReleaseFactory() {

	}

	function & getFRSReleaseFromArray(& $array) {
		$frs_release = null;
		$frs_release = new FRSRelease($array);
		return $frs_release;
	}

	function & getFRSReleaseFromDb($release_id, $group_id=null, $package_id=null) {
		$_id = (int) $release_id;
		$dao = & $this->_getFRSReleaseDao();
		if($group_id && $package_id){
			$_group_id = (int) $group_id;
			$_package_id = (int) $package_id;
			$dar = $dao->searchByGroupPackageReleaseID($_id, $_group_id, $package_id);
		}else if($group_id) {
			$_group_id = (int) $group_id;
			$dar = $dao->searchInGroupById($_id, $_group_id);
		}else{
			$dar = $dao->searchById($_id);
		}
		

		if ($dar->isError()) {
			return;
		}

		if (!$dar->valid()) {
			return;
		}

		$data_array = & $dar->current();

		return (FRSReleaseFactory :: getFRSReleaseFromArray($data_array));
	}

	function & getFRSReleasesFromDb($package_id, $status_id=null, $group_id=null) {
		$_id = (int) $package_id;
		$dao = & $this->_getFRSReleaseDao();
		if(isset($status_id) && $status_id == $this->STATUS_ACTIVE && isset($group_id) && $group_id){
			$dar = $dao->searchActiveReleasesByPackageId($_id, $this->STATUS_ACTIVE);
		}else{
			$dar = $dao->searchByPackageId($_id);
		}

		if ($dar->isError()) {
			return;
		}

		if (!$dar->valid()) {
			return;
		}
		
		$um =& UserManager::instance();
        $user =& $um->getCurrentUser();
		$releases = array ();
		while ($dar->valid()) {
			$data_array = & $dar->current();
			if($status_id && $group_id){			
				if($this->userCanRead($group_id, $package_id, $data_array['release_id'], $user->getID())){
					$releases[] = FRSReleaseFactory :: getFRSReleaseFromArray($data_array);
				}
			}else{
				$releases[] = FRSReleaseFactory :: getFRSReleaseFromArray($data_array);
			}
			$dar->next();
		}

		return $releases;
	}
	
	function getFRSReleasesInfoListFromDb($group_id, $package_id=null) {
		$_id = (int) $group_id;
		$dao = & $this->_getFRSReleaseDao();
		if($package_id){
			$_package_id = (int) $package_id;
			$dar = $dao->searchByGroupPackageID($_id, $_package_id);
		}else{
			$dar = $dao->searchByGroupPackageID($_id);
		}

		if ($dar->isError()) {
			return;
		}

		if (!$dar->valid()) {
			return;
		}	

		$releases = array ();
		while ($dar->valid()) {
			$releases[] = $dar->current();
			$dar->next();
		}
		return $releases;
	}

	function isActiveReleases($package_id) {
		$_id = (int) $package_id;
		$dao = & $this->_getFRSReleaseDao();
		$dar = $dao->searchActiveReleasesByPackageId($_id, $this->STATUS_ACTIVE);

		if ($dar->isError()) {
			return;
		}

		return $dar->valid();

	}
	
    
    function getReleaseIdByName($release_name, $package_id){
    	$_id = (int) $package_id;
        $dao =& $this->_getFRSReleaseDao();
        $dar = $dao->searchReleaseByName($release_name, $_id);

        if($dar->isError()){
            return;
        }
        
        if(!$dar->valid()){
        	return;
        }else{
        	$res =& $dar->current();
        	return $res['release_id'];
        }
    }

    /**
     * Determine if a release has already the name $release_name in the package $package_id
     *
     * @return boolean true if there is already a release named $release_name in the package package_id, false otherwise
     */
     function isReleaseNameExist($release_name, $package_id) {
         $release_exists = $this->getReleaseIdByName($release_name, $package_id);
         return ($release_exists && count($release_exists) >=1);
     }

    
	var $dao;

	function & _getFRSReleaseDao() {
		if (!$this->dao) {
			$this->dao = & new FRSReleaseDao(CodexDataAccess :: instance());
		}
		return $this->dao;
	}

	function update($data_array) {
		$dao = & $this->_getFRSReleaseDao();
		return $dao->updateFromArray($data_array);
	}

	function create($data_array) {
		$dao = & $this->_getFRSReleaseDao();
		$id = $dao->createFromArray($data_array);
		return $id;
	}
	
	function _delete($release_id){
    	$_id = (int) $release_id;
    	$dao =& $this->_getFRSReleaseDao();
    	return $dao->delete($_id,$this->STATUS_DELETED);
    }

	/*
	
	Physically delete a release from the download server and database
	
	First, make sure the release is theirs
	Second, delete all its files from the db
	Third, delete the release itself from the deb
	Fourth, put it into the delete_files to be removed from the download server
	
	return 0 if release not deleted, 1 otherwise
	*/
	function delete_release($group_id, $release_id) {
		GLOBAL $ftp_incoming_dir;

		$release =& $this->getFRSReleaseFromDb($release_id, $group_id);
		
		if (!$release) {
			//release not found for this project
			return 0;
		} else {
			//delete all corresponding files from the database
			$res =& $release->getFiles();
			$rows = count($res);
			$frsff =& $this->_getFRSFileFactory();
			for ($i = 0; $i < $rows; $i++) {
				$frsff->delete_file($group_id, $res[$i]->getFileID());
				$filename = $res[$i]->getFileName();
			}

			//delete the release from the database
			$this->_delete($release_id);

			//append the releasename and project name to a temp file for the root perl job to grab
			if (isset($filename) && $filename) {
				//find the last occurrence of / in the filename to get the parentdir name
				$pos = strrpos($filename, "/");
				if (!$pos) {
					// not found...
				} else {
					$parentdir = substr($filename, 0, $pos);
					$time = time();
					exec("/bin/echo \"$parentdir::" . group_getunixname($group_id) . "::$time\" >> $ftp_incoming_dir/.delete_files");
				}
			}

			return 1;
		}
	}
    
    /**
     * Get a Package Factory
     *
     * @return Object{FRSPackageFactory} a FRSPackageFactory Object.
     */
    function &_getFRSPackageFactory() {
        return new FRSPackageFactory();
    }
    
    /**
     * Get a File Factory
     *
     * @return Object{FRSFileFactory} a FRSFileFactory Object.
     */
    function &_getFRSFileFactory() {
        return new FRSFileFactory();
    }
	
	/** return true if user has Read or Update permission on this release 
	 * @param group_id: the package this release is in
	 * @param release_id: the release id 
	 * @param user_id: if not given or 0 take the current user
     */ 
	function userCanRead($group_id,$package_id,$release_id,$user_id=0) {
        $pm =& PermissionsManager::instance();
        $um =& UserManager::instance();
        $user =& $um->getUserById($user_id);
        if($pm->isPermissionExist($release_id, 'RELEASE_READ')){
        	$ok = $user->isSuperUser() 
              	|| $pm->userHasPermission($release_id, 'RELEASE_READ', $user->getUgroups($group_id, array()));
		} else{
        	$frspf =& $this->_getFRSPackageFactory();
        	$ok = $frspf->userCanRead($group_id, $package_id, $user_id);
        }
        return $ok;
	}

    /** return true if user has Update permission on this release 
     * @param int $group_id the project this release is in
     * @param int $release_id the ID of the release to update
     * @param int $user_id if not given or 0, take the current user
     * @return boolean true if user can update the release $release_id, false otherwise
     */ 
	function userCanUpdate($group_id,$release_id,$user_id=0) {
        $pm =& PermissionsManager::instance();
        $um =& UserManager::instance();
        $user =& $um->getUserById($user_id);
        $ok = $user->isSuperUser() 
              || $pm->userHasPermission($release_id, 'RELEASE_READ', $user->getUgroups($group_id, array()));
        return $ok;
	}
    
    /** 
     * Returns true if user has permissions to Create releases
     * 
     * NOTE : At this time, there is no difference between creation and update, but in the future, permissions could be added
     * For the moment, only super admin, project admin (A) and file admin (R2) can create releases
     * 
     * @param int $group_id the project ID this release is in
     * @param int $user_id the ID of the user. If not given or 0, take the current user
     * @return boolean true if the user has permission to create releases, false otherwise
     */ 
	function userCanCreate($group_id,$user_id=0) {
        $pm =& PermissionsManager::instance();
        $um =& UserManager::instance();
        $user =& $um->getUserById($user_id);
        $ok = $user->isSuperUser() || $user->isMember($group_id,'R2') || $user->isMember($group_id,'A');
        return $ok;
	}

}
?>
