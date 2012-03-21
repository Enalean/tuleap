<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

require_once ('FRSRelease.class.php');
require_once ('common/dao/FRSReleaseDao.class.php');
require_once ('common/frs/FRSFileFactory.class.php');
require_once ('common/frs/FRSPackageFactory.class.php');
require_once ('common/frs/FileModuleMonitorFactory.class.php');
require_once('www/project/admin/ugroup_utils.php');
require_once ('common/frs/FRSLog.class.php');
/**
 * 
 */
class FRSReleaseFactory {
    // Kept for legacy
    var $STATUS_ACTIVE  = FRSRelease::STATUS_ACTIVE;
    var $STATUS_DELETED = FRSRelease::STATUS_DELETED;
    var $STATUS_HIDDEN  = FRSRelease::STATUS_HIDDEN;
    

	function FRSReleaseFactory() {
	}

	function  getFRSReleaseFromArray(& $array) {
		$frs_release = null;
		$frs_release = new FRSRelease($array);
		return $frs_release;
	}

	/**
	 * Get one or more releases from the database
	 * 
	 * $extraFlags allow to define if you want to include deleted releases into
	 * the search (thanks to FRSReleaseDao::INCLUDE_DELETED constant)
	 * 
	 * @param $release_id
	 * @param $group_id
	 * @param $package_id
	 * @param $extraFlags
	 */
	function  getFRSReleaseFromDb($release_id, $group_id=null, $package_id=null, $extraFlags = 0) {
		$_id = (int) $release_id;
		$dao = & $this->_getFRSReleaseDao();
		if($group_id && $package_id){
			$_group_id = (int) $group_id;
			$_package_id = (int) $package_id;
			$dar = $dao->searchByGroupPackageReleaseID($_id, $_group_id, $package_id, $extraFlags);
		}else if($group_id) {
			$_group_id = (int) $group_id;
			$dar = $dao->searchInGroupById($_id, $_group_id, $extraFlags);
		}else{
			$dar = $dao->searchById($_id, $extraFlags);
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

	function  getFRSReleasesFromDb($package_id, $status_id=null, $group_id=null) {
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

		$releases = array ();
		if ($dar->valid()) {		
            $um =& UserManager::instance();
            $user =& $um->getCurrentUser();
            while ($dar->valid()) {
                $data_array = & $dar->current();
                if($status_id && $group_id){			
                    if($this->userCanRead($group_id, $package_id, $data_array['release_id'], $user->getID())){
                        $releases[] = $this->getFRSReleaseFromArray($data_array);
                    }
                }else{
                    $releases[] = $this->getFRSReleaseFromArray($data_array);
                }
                $dar->next();
            }
        }
        
		return $releases;
	}

    /**
     * Returns the list of releases for a given proejct
     * 
     * @param Integer $group_id
     * @param Integer $package_id
     * 
     * @return Array
     */
    function getFRSReleasesInfoListFromDb($group_id, $package_id=null) {
        $_id = (int) $group_id;
        $dao = $this->_getFRSReleaseDao();
        if ($package_id) {
            $_package_id = (int) $package_id;
            $dar = $dao->searchByGroupPackageID($_id, $_package_id);
        } else {
            $dar = $dao->searchByGroupPackageID($_id);
        }

        if ($dar && !$dar->isError()) {
            $releases = array ();
            foreach ($dar as $row) {
                $releases[] = $row;
            }
            return $releases;
        }
        return;
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

	function  _getFRSReleaseDao() {
		if (!$this->dao) {
			$this->dao =  new FRSReleaseDao(CodendiDataAccess :: instance(), $this->STATUS_DELETED);
		}
		return $this->dao;
	}

    function update($data_array) {
        $dao =  $this->_getFRSReleaseDao();
        if ($dao->updateFromArray($data_array)) {
            $release = $this->getFRSReleaseFromDb($data_array['release_id']);
            $this->getEventManager()->processEvent('frs_update_release',
                                                   array('group_id' => $release->getGroupID(),
                                                         'item_id'    => $data_array['release_id']));
            return true;
        }
        return false;
    }

    function create($data_array) {
        $dao = $this->_getFRSReleaseDao();
        if ($id = $dao->createFromArray($data_array)) {
            $release = $this->getFRSReleaseFromDb($id);
            $this->getEventManager()->processEvent('frs_create_release',
                                                   array('group_id' => $release->getGroupID(),
                                                         'item_id'    => $id));
            return $id;
        }
        return false;
    }

    function _delete($release_id){
        $_id = (int) $release_id;
        $release = $this->getFRSReleaseFromDb($_id);
        $dao = $this->_getFRSReleaseDao();
        if ($dao->delete($_id,$this->STATUS_DELETED)) {
            $this->getEventManager()->processEvent('frs_delete_release',
                                                   array('group_id' => $release->getGroupID(),
                                                         'item_id' => $_id));
            return true;
        }
        return false;
    }

    /**
     * Physically delete a release from the download server and database
     * First, make sure the release is theirs
     * Second, delete all its files from the db
     * Third, delete the release itself from the deb
     * Fourth, put it into the delete_files to be removed from the download server
     * return false if release not deleted, true otherwise
     * 
     * @param Integer $group_id
     * @param Integer $release_id
     * 
     * @return Boolean
     */
    function delete_release($group_id, $release_id) {
        GLOBAL $ftp_incoming_dir;

        $release = $this->getFRSReleaseFromDb($release_id, $group_id);

        if (!$release) {
            //release not found for this project
            return false;
        } else {
            //delete all corresponding files from the database
            $res = $release->getFiles();
            $frsff = $this->_getFRSFileFactory();
            foreach ($res as $file) {
                $frsff->delete_file($group_id, $file->getFileID());
            }

            //delete the release from the database
            $this->_delete($release_id);
            return true;
        }
    }

    /**
     * Delete all FRS releases and files of given project
     *
     * @param Integer $groupId Project ID
     *
     * @return Boolean
     */
    function deleteProjectReleases($groupId) {
        $deleteState = true;
        $resReleases = $this->getFRSReleasesInfoListFromDb($groupId);
        if (!empty($resReleases)) {
            foreach ($resReleases as $release) {
                if (!$this->delete_release($groupId, $release['release_id'])) {
                    $deleteState = false;
                }
            }
        }
        return $deleteState;
    }

    /**
     * Test is user can administrate FRS service of given project
     *
     * @param User    $user    User to test
     * @param Integer $groupId Project
     *
     * @return Boolean
     */
    protected function userCanAdmin($user, $groupId) {
        return FRSPackageFactory::userCanAdmin($user, $groupId);
    }

    /**
     * Return true if user has Read or Update permission on this release
     *
	 * @param Integer $group_id   The project the release is in
     * @param Integer $package_id The package this release is in
	 * @param Integer $release_id The release id
	 * @param Integer $user_id    If not given or false take the current user
     *
     * @return Boolean
     */ 
	function userCanRead($group_id, $package_id, $release_id, $user_id=false) {
        $um = $this->getUserManager();
	    if (!$user_id) {
            $user = $um->getCurrentUser();
        } else {
            $user = $um->getUserById($user_id);    
        }
        if ($this->userCanAdmin($user, $group_id)) {
            return true;
        } else {
            $pm = $this->getPermissionsManager();
            if ($pm->isPermissionExist($release_id, FRSRelease::PERM_READ)) {
                $ok = $pm->userHasPermission($release_id, FRSRelease::PERM_READ, $user->getUgroups($group_id, array()));
            } else {
                $frspf = $this->_getFRSPackageFactory();
                $ok    = $frspf->userCanRead($group_id, $package_id, $user->getId());
            }
            return $ok;
        }
	}

    /** 
     * Return true if user has Update permission on this release 
     *
     * @param Integer $group_id   The project this release is in
     * @param Integer $release_id The ID of the release to update
     * @param Integer $user_id    If not given or false, take the current user
     *
     * @return Boolean true if user can update the release $release_id, false otherwise
     */ 
	function userCanUpdate($group_id, $release_id, $user_id=false) {
        return $this->userCanCreate($group_id, $user_id);
	}
    
    /** 
     * Returns true if user has permissions to Create releases
     * 
     * NOTE : At this time, there is no difference between creation and update, but in the future, permissions could be added
     * For the moment, only super admin, project admin (A) and file admin (R2) can create releases
     * 
     * @param Integer $group_id The project ID this release is in
     * @param Integer $user_id  The ID of the user. If not given or false, take the current user
     *
     * @return Boolean true if the user has permission to create releases, false otherwise
     */ 
	function userCanCreate($group_id, $user_id=false) {
        $um = $this->getUserManager();
	    if (! $user_id) {
            $user = $um->getCurrentUser();
        } else {
            $user = $um->getUserById($user_id);    
        }
        return $this->userCanAdmin($user, $group_id);
	}

    /**
     * Set default permission on given release
     *
     * By default, release inherits its permissions from the parent package.
     * If no permission is set "explicitly" to package, release should be set to default one
     *
     * @param FRSRelease $release Release on which to apply permissions
     * 
     * @return Boolean
     */
    function setDefaultPermissions(FRSRelease $release) {
        $pm = $this->getPermissionsManager();
        // Reset permissions for this release, before setting the new ones
        if ($pm->clearPermission(FRSRelease::PERM_READ, $release->getReleaseID())) {
            $dar = $pm->getAuthorizedUgroups($release->getPackageID(), FRSPackage::PERM_READ, false);
            if ($dar && !$dar->isError() && $dar->rowCount()>0) {
                foreach($dar as $row) {
                    // Set new permissions
                    $pm->addPermission(FRSRelease::PERM_READ, $release->getReleaseID(), $row['ugroup_id']);
                }
                permission_add_history($release->getGroupID(), FRSRelease::PERM_READ, $release->getReleaseID());
                return true;
            }
        }
        return false;
    }

    /**
     * Send email notification to people monitoring the package the release belongs to
     *
     * @param FRSRelease $release Release in which the file is published
     *
     * @return Integer The number of people notified. False in case of error.
     */
    function emailNotification(FRSRelease $release) {
        $fmmf   = new FileModuleMonitorFactory();
        $result = $fmmf->whoIsMonitoringPackageById($release->getGroupID(), $release->getPackageID());

        if ($result && count($result) > 0) {
            $package = $this->_getFRSPackageFactory()->getFRSPackageFromDb($release->getPackageID());

            // To
            $array_emails = array ();
            foreach ($result as $res) {
                $array_emails[] = $res['email'];
            }
            $list = implode($array_emails, ', ');

            $pm      = ProjectManager::instance();
            $project = $pm->getProject($package->getGroupID());

            // Subject
            $subject = ' '.$GLOBALS['Language']->getText('file_admin_editreleases', 'file_rel_notice_subject', array($GLOBALS['sys_name'], $project->getPublicName(), $package->getName()));

            // Body
            $fileUrl  = get_server_url() . "/file/showfiles.php?group_id=".$package->getGroupID()."&release_id=".$release->getReleaseID();
            $notifUrl = get_server_url() . "/file/filemodule_monitor.php?filemodule_id=".$package->getPackageID();

            $body  = $GLOBALS['Language']->getText('file_admin_editreleases', 'download_explain_modified_package', array($project->getPublicName(), $package->getName(), $release->getName(), $fileUrl));

            if ($release->getNotes() != '') {
                $body .= $GLOBALS['Language']->getText('file_admin_editreleases', 'file_rel_notice_notes', array($release->getNotes()));
            }
            if ($release->getChanges() != '') {
                $body .= $GLOBALS['Language']->getText('file_admin_editreleases', 'file_rel_notice_changes', array($release->getChanges()));
            }

            $body .= $GLOBALS['Language']->getText('file_admin_editreleases', 'download_explain', array($notifUrl));
            
            $mail = new Mail();
            $mail->setFrom($GLOBALS['sys_noreply']);
            $mail->setBcc($list);
            $mail->setSubject($subject);
            $mail->setBody($body);

            if ($mail->send()) {
                return count($result);
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns an instance of EventManager
     *
     * @return EventManager
     */
    function getEventManager() {
         $em = EventManager::instance();
         FRSLog::instance();
         return $em;
    }

    /**
     * Return an instance of PermissionsManager
     *
     * @return PermissionsManager
     */
    function getPermissionsManager() {
        return PermissionsManager::instance();
    }

    /**
     * @return UserManager
     */
    function getUserManager() {
        return UserManager::instance();
    }

    /**
     * Get a Package Factory
     *
     * @return FRSPackageFactory
     */
    function _getFRSPackageFactory() {
        return new FRSPackageFactory();
    }

    /**
     * Get a File Factory
     *
     * @return FRSFileFactory
     */
    function _getFRSFileFactory() {
        return new FRSFileFactory();
    }
}
?>
