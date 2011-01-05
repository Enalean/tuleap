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

require_once('FRSFile.class.php');
require_once('common/dao/FRSFileDao.class.php');
require_once ('common/frs/FRSLog.class.php');

/**
 * 
 */
class FRSFileFactory extends Error {

    function FRSFileFactory() {
    }

    function &getFRSFileFromArray(&$array) {
        $frs_file = null;
        $frs_file = new FRSFile($array);
        return $frs_file;
    }

    function &getFRSFileFromDb($file_id, $group_id=null) {
        $_id = (int) $file_id;
        $dao =& $this->_getFRSFileDao();
        if($group_id){
        	$_group_id = (int) $group_id;
        	$dar = $dao->searchInReleaseById($_id, $group_id);
        }else{
        	$dar = $dao->searchById($_id);
        }

        $file = null;
        if(!$dar->isError() && $dar->valid()) {
            $data_array =& $dar->current();
            $file = FRSFileFactory::getFRSFileFromArray($data_array);
        }
        return $file;
    }
    
    /**
     * get the files of the release.
     * 
     * @param int $relase_id the ID of the release the files belong to
     */
    function &getFRSFilesFromDb($release_id) {
        $_id = (int) $release_id;
        $dao =& $this->_getFRSFileDao();
        $dar = $dao->searchByReleaseId($_id);

        $files = array();
        if(!$dar->isError() && $dar->valid()) {
            while ($dar->valid()){
                $data_array =& $dar->current();
                $files[] = FRSFileFactory::getFRSFileFromArray($data_array);
                $dar->next();
            }
        }
        return $files;
    }
    
    function getFRSFileInfoListFromDb($group_id, $file_id) {
		$_group_id = (int) $group_id;
		$_file_id = (int) $file_id;
		$dao = & $this->_getFRSFileDao();
		
		$dar = $dao->searchInfoByGroupFileID($_group_id, $_file_id);

		if ($dar->isError()) {
			return;
		}

		if (!$dar->valid()) {
			return;
		}	
		
		$file_info = array ();
		while ($dar->valid()) {
			$file_info[] = $dar->current();
			$dar->next();
		}
		return $file_info;

	}
	
	function getFRSFileInfoListByReleaseFromDb($release_id) {
		$_release_id = (int) $release_id;
		$dao = & $this->_getFRSFileDao();
		
		$dar = $dao->searchInfoFileByReleaseID($_release_id);

		if ($dar->isError()) {
			return;
		}

		if (!$dar->valid()) {
			return;
		}	
		
		$file_info = array ();
		while ($dar->valid()) {
			$file_info[] = $dar->current();
			$dar->next();
		}
		return $file_info;

	}
    
    function isFileNameExist($file_name, $group_id){
    	$_id = (int) $group_id;
        $dao =& $this->_getFRSFileDao();
        $dar = $dao->searchFileByName($file_name, $_id);

        if($dar->isError()){
            return;
        }
        
        return $dar->valid();
    }
    
    /**
     * Determine if there is already a file named $file_basename in the release $release_id for the project $group_id
     *
     * @param string $file_basename the file name (base, without directory) we want to check
     * @param int $release_id the ID of the release the file belongs to
     * @param int $group_id the ID of the project the file belongs to
     * @return boolean true if a file named $file_basename already exists in the release $release_id, false otherwise
     */
    function isFileBaseNameExists($file_basename, $release_id, $group_id) {
        $subdir = $this->getUploadSubDirectory($release_id);
        $file_name = $subdir.'/'.$file_basename;
        return $this->isFileNameExist($file_name, $group_id);
    }
    
    var $dao;
    
    function &_getFRSFileDao() {
        if (!$this->dao) {
            $this->dao =& new FRSFileDao(CodendiDataAccess::instance());
        }
        return $this->dao;
    }
    
    function update($data_array) {
        $dao =& $this->_getFRSFileDao();
        if ($dao->updateFromArray($data_array)) {
            $file = $this->getFRSFileFromDb($data_array['file_id']);
            $um = UserManager::instance();
            $user = $um->getCurrentUser();
            $this->_getEventManager()->processEvent('frs_update_file',
                                                   array('user_id'    => $user->getId(),
                                                         'project_id' => $file->getGroup()->getGroupId(),
                                                         'item_id'    => $data_array['file_id']));
            return true;
        }
        return false;
    }
    
    
    function create($data_array) {
        $dao =& $this->_getFRSFileDao();
        if ($id = $dao->createFromArray($data_array)) {
            $file = $this->getFRSFileFromDb($id);
            $um = UserManager::instance();
            $user = $um->getCurrentUser();
            $this->_getEventManager()->processEvent('frs_create_file',
                                                    array('user_id'    => $user->getId(),
                                                          'project_id' => $file->getGroup()->getGroupId(),
                                                          'item_id'    => $id));
            return $id;
        }
        return false;
    }
    
    /**
     * create a new file from a file present in the incoming dir
     *
     * @return true or id(auto_increment) if there is no error
     */
    function createFromIncomingFile($name=null, $release_id=null, 
                               $type_id=null, $processor_id=null, $computedMd5, $referenceMd5) {
        
        // check if the file exists
        $uploaded_files = $this->getUploadedFileNames();
        if (! in_array($name, $uploaded_files)) {
            $this->setError('File not found: '.$name);
            return false;
        }

	// Don't use filesize() : Workaround for files larger than 2 GB
        $filesize = file_utils_get_size($GLOBALS['ftp_incoming_dir'] . '/' . $name);

        $um = UserManager::instance();
        $user = $um->getCurrentUser();

        $file = new FRSFile();
        $file->setFileName($name);
        $file->setFileSize($filesize);
        $file->setReleaseID($release_id);
        $file->setTypeID($type_id);
        $file->setProcessorID($processor_id);
        $file->setStatus('A');
        $file->setComputedMd5($computedMd5);
        $file->setReferenceMd5($referenceMd5);
        $file->setUserID($user->getId());

        // retrieve the group_id
        $release_fact =& $this->_getFRSReleaseFactory();
        $release =& $release_fact->getFRSReleaseFromDb($release_id);
        $group_id = $release->getGroupID();
        
        // get the sub directory where to move the file
        $upload_sub_dir = $this->getUploadSubDirectory($release->getReleaseID());
        
        $exec_return = $this->moveFileForge($group_id, $name, $upload_sub_dir);
        // shall we test the result of fileforge ???
        
        // set the new name of the file: we add the sub-directory
        $file->setFileName($upload_sub_dir.'/'.$name);
        return $this->create($file->toArray());
    }
    
    /**
     * Get the sub directory where to upload the files
     *
     * @static
     *
     * @param int $release_id the ID of the release the file belongs to
     * @return string the sub-directory (wihtout any /) where to upload the file
     */
    function getUploadSubDirectory($release_id) {
        $release_fact =& $this->_getFRSReleaseFactory();
        $release =& $release_fact->getFRSReleaseFromDb($release_id);
        // get the sub directory where to upload the file
        return 'p' . $release->getPackageID() . '_r' . $release->getReleaseID();
    }
    
    /**
     * Get a Release Factory
     *
     * @return Object{FRSReleaseFactory} a FRSReleaseFactory Object.
     */
    function &_getFRSReleaseFactory() {
        $f = new FRSReleaseFactory();
        return $f;
    }
    
    function _delete($file_id){
        $_id = (int) $file_id;
        $file = $this->getFRSFileFromDb($_id);
        $dao =& $this->_getFRSFileDao();
        if ($dao->delete($_id)) {
            $um = UserManager::instance();
            $user = $um->getCurrentUser();
            $this->_getEventManager()->processEvent('frs_delete_file',
                                                   array('user_id'    => $user->getId(),
                                                         'project_id' => $file->getGroup()->getGroupId(),
                                                         'item_id'    => $_id));
            return true;
        }
        return false;
    }

    /**
     * Mark a file as deleted
     *
     * Deletion of a file in FRS is a complex process.
     * First, when user attempt to delete a file (or recursively when she deletes
     * a release or a whole package) files are not immedialty deleted:
     * #1: Flag the file as deleted (status = D, no longer appears in web interface)
     * #2: Move flaged files into a staging area for a while
     * #3: Every so often permanently erase from the file system the files from
     *     the stagging area that are older than a given threshold.
     * Why such complex process ?
     * #1: To allow files to be backed-up even if files are uploaded and deleted
     *     before a backup job occurs.
     * #2: The staging area/period allows site admin to magically restore files
     *     if they were removed by mistake.
     * #3: Previous step 2 needs to be done by 'root' because files might no be
     *     owned by Codendiadm user.
     * #4: Whe need to move files in a staging area because otherwise people would
     *     not be able to upload a file with the same name in the same release
     *     because the new file will override the deleted one and when the job
     *     comes to purge the file it will remove the new one (valid).
     *
     * @param Integer $group_id
     * @param Integer $file_id
     *
     * @return Boolean
     */
    function delete_file ($group_id, $file_id) {
        $file = $this->getFRSFileFromDb($file_id, $group_id);
        if ($file) {
            return $this->_delete($file_id);
        }
        return false;
    }

    /**
     * Centralize treatement of files physical deletion in FRS
     * 
     * @param Integer $time Date from when the files must be erased
     * 
     * @return Boolean
     */
    public function purgeDeletedFiles($time, $backend) {
        $this->moveDeletedFilesToStagingArea();
        $this->purgeFiles($time);
        $this->cleanStaging();
        $this->restoreDeletedFiles($backend);
        return true;
    }

    /**
     * Move to staging all files marked as deleted but still in the release area
     * 
     * @return Boolean
     */
    public function moveDeletedFilesToStagingArea() {
        $dao = $this->_getFRSFileDao();
        $dar = $dao->searchStagingCandidates();
        if ($dar && !$dar->isError()) {
            foreach ($dar as $row) {
                $this->moveDeletedFileToStagingArea(new FRSFile($row));
            }
            return true;
        }
        return false;
    }

    /**
     * Physically move one file from release area to staging
     * 
     * The file is renamed during the move with its file id to avoid override
     * if someone upload and delete 2 times (or more) the same file in the same
     * release
     * 
     * @param FRSFile $file
     */
    public function moveDeletedFileToStagingArea($file) {
        $stagingPath = $this->getStagingPath($file);
        $stagingDir  = dirname($stagingPath);
        if (!is_dir($stagingDir)) {
            mkdir($stagingDir, 0750, true);
        }
        if (rename($file->getFileLocation(), $stagingPath)) {
            $dao = $this->_getFRSFileDao();
            $deleted = $dao->setFileInDeletedList($file->getFileId());
            
            // Delete release directory when the last file is removed
            $nbFiles = 0;
            $dir = new DirectoryIterator(dirname($file->getFileLocation()));
            foreach ($dir as $f) {
                if (!$f->isDot()) {
                    $nbFiles++;
                }
            }
            if ($nbFiles === 0) {
                rmdir(dirname($file->getFileLocation()));
            }
            return $deleted;
        }
        return false;
    }

    /**
     * Get the path in staging area of a file
     * 
     * @param FRSFile $file
     * 
     * @return String
     */
    public function getStagingPath($file) {
        $fileName    = basename($file->getFileLocation());
        $releasePath = dirname($file->getFileLocation());
        $relDirName  = basename($releasePath);
        $prjDirName  = basename(dirname($releasePath));
        $stagingPath = $GLOBALS['ftp_frs_dir_prefix'].'/DELETED/'.$prjDirName.'/'.$relDirName;
        return $stagingPath.'/'.$fileName.'.'.$file->getFileId();
    }

    /**
     * Permanently erase from the file system all deleted files older than given date
     *
     * @param Integer $time Timestamp
     *
     * @return Boolean
     */
    public function purgeFiles($time) {
        $dao = $this->_getFRSFileDao();
        $dar = $dao->searchFilesToPurge($time);
        if ($dar && !$dar->isError()) {
            foreach ($dar as $row) {
                $file = new FRSFile($row);
                $this->purgeFile($file);
            }
            return true;
        }
        return false;
    }

    /**
     * Erase from the file system one file
     *
     * @param FRSFile $file File to delete
     *
     * @return Boolean
     */
    public function purgeFile($file) {
        if (unlink($this->getStagingPath($file))) {
            $dao = $this->_getFRSFileDao();
            return $dao->setPurgeDate($file->getFileID(), time());
        }
        return false;
    }

    /**
     * Remove empty releases and project directories in staging area
     * 
     * @return Boolean
     */
    public function cleanStaging() {
        // All projects
        $prjIter = new DirectoryIterator($GLOBALS['ftp_frs_dir_prefix'].'/DELETED');
        foreach ($prjIter as $prj) {
            if (strpos($prj->getFilename(), '.') !== 0) {
                // Releases
                $nbRel   = 0;
                $relIter = new DirectoryIterator($prj->getPathname());
                foreach ($relIter as $rel) {
                    if (!$rel->isDot()) {
                        // Files
                        $nbFiles  = 0;
                        $fileIter = new DirectoryIterator($rel->getPathname());
                        foreach ($fileIter as $file) {
                            if (!$file->isDot()) {
                                $nbFiles++;
                            }
                        }
                        if ($nbFiles === 0) {
                            rmdir($rel->getPathname());
                        } else {
                            $nbRel++;
                        }
                    }
                }
                if ($nbRel === 0) {
                    rmdir($prj->getPathname());
                }
            }
        }
        return true;
    }

    /**
     * List all files deleted but not already purged
     * 
     * @param Integer $groupId
     * @param Integer $offset
     * @param Integer $limit
     * 
     * @return Boolean
     */
    public function listPendingFiles($groupId, $offset, $limit) {
        $dao = $this->_getFRSFileDao();
        return $dao->searchFilesToPurge($_SERVER['REQUEST_TIME'], $groupId, $offset, $limit);
    }

    /** 
     * Returns true if user has permissions to add files
     * 
     * NOTE : For the moment, only super admin, project admin (A) and file admin (R2) can add files
     * 
     * @param int $group_id the project ID this file is in
     * @param int $user_id the ID of the user. If not given or false, take the current user
     * @return boolean true if the user has permission to add files, false otherwise
     */ 
    function userCanAdd($group_id,$user_id=false) {
        $pm =& PermissionsManager::instance();
        $um =& UserManager::instance();
        if (! $user_id) {
            $user =& $um->getCurrentUser();
        } else {
            $user =& $um->getUserById($user_id);    
        }
        $ok = $user->isSuperUser() || user_ismember($group_id,'R2') || user_ismember($group_id,'A');
        return $ok;
    }

    /**
     * Returns the names of the files present in the incoming directory
     *
     * @return array of string : the names of the files present in the incoming directory
     */
    function getUploadedFileNames() {
        $uploaded_file_names = array();
        //iterate and show the files in the upload directory

        /// This won't work for files > 2GB
        //$dirhandle = @ opendir($GLOBALS['ftp_incoming_dir']);
        //while ($file = @ readdir($dirhandle)) {

        // Workaround for files bigger than 2Gb:
        $filelist = shell_exec("/usr/bin/find ".$GLOBALS['ftp_incoming_dir']." -maxdepth 1 -type f -printf \"%f\\n\"");
	$files = explode("\n",$filelist);
        // Remove last (empty) element
        array_pop($files);
        foreach ($files as $file) {
            if (!ereg('^\.', $file[0])) {
                $uploaded_file_names[] = $file;
            }
        }
        return $uploaded_file_names;
    }
    
    /**
     * Force the upload directory creation, and move the file $file_name in the good directory
     *
     * @global $GLOBALS['codendi_bin_prefix']
     *
     * @param int $group_id the ID of the project we want to upload the file
     * @param string $file_name the name of the file we want to upload
     * @param string $upload_sub_dir the name of the sub-directory the file will be moved in
     * @return string the feedback returned by the fileforge command.
     */
    function moveFileForge($group_id, $file_name, $upload_sub_dir) {
        $pm = ProjectManager::instance();
        $group = $pm->getProject($group_id);
        $group_unix_name = $group->getUnixName(false);
        $file_name = preg_replace('` `', '\\ ', $file_name);
        if (!file_exists($GLOBALS['ftp_frs_dir_prefix'].'/'.$group_unix_name . '/' . $upload_sub_dir.'/'.$file_name)) {
            $ret_val  = null;
            $exec_res = null;
            $cmd = $GLOBALS['codendi_bin_prefix'] . "/fileforge $file_name " . $group_unix_name . "/" . $upload_sub_dir;
            exec($cmd, $exec_res, $ret_val);
            return $ret_val;
        }
        return "Error";
    }

    /**
     * Wrapper to get a UserManager instance
     *
     * @return UserManager
     */
    function _getUserManager() {
        $um = UserManager::instance();
        return $um;
    }

    /**
     * Wrapper to get an EventManager instance
     *
     * @return EventManager
     */
    function _getEventManager() {
        $em = EventManager::instance();
        FRSLog::instance();
        return $em;
    }

    /**
     * restore file by moving it from staging area to its old location
     * 
     * @param FRSFile $file 
     * 
     * @return Boolean
     */
    function restoreFile($file, $backend) {
        $stagingPath = $this->getStagingPath($file);
        if (file_exists($stagingPath)) {
            if (!is_dir(dirname($file->getFileLocation()))) {
                mkdir(dirname($file->getFileLocation()), 0755, true);
                $backend->chgrp(dirname($file->getFileLocation()), $GLOBALS['sys_http_user']);
            }
            if (rename($stagingPath, $file->getFileLocation())) {
                $dao = $this->_getFRSFileDao();
                if ($dao->restoreFile($file->getFileID())) {
                    $um = $this->_getUserManager();
                    $user = $um->getCurrentUser();
                    $this->_getEventManager()->processEvent('frs_restore_file',
                                                           array('user_id'    => $user->getId(),
                                                                 'project_id' => $file->getGroup()->getGroupId(),
                                                                 'item_id'    => $file->getFileID()));
                    return true;
                }
                return false;
            }
        }
        return false;
    }

    /**
     * Restore files marked to be restored
     * 
     * @return Boolean
     */
    public function restoreDeletedFiles($backend) {
        $dao = $this->_getFRSFileDao();
        $dar = $dao->searchFilesToRestore();
        if ($dar && !$dar->isError() && $dar->rowCount() >0) {
            foreach ($dar as $row) {
                $file = new FRSFile($row);
                if (!$this->restoreFile($file, $backend)) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }
    
    /**
     * Mark the files that site admin wants to restore
     * 
     * @param FRSFile $file
     * 
     * @return Boolean
     */
    public function markFileToBeRestored($file) {
        $dao = $this->_getFRSFileDao();
        return $dao->markFileToBeRestored($file->getFileID());
    }
    
    /**
     * Insert the computed md5sum value in case of offline checksum compute
     * 
     * @param Integer $fileId
     * @param String $md5Computed
     * 
     * @return Boolean
     */
    
    public function updateComputedMd5sum($fileId, $md5Computed) {
        $dao = $this->_getFRSFileDao();
        return $dao->updateComputedMd5sum($fileId, $md5Computed);
    }

    /**
     * Compare md5sums to check if they fit
     * Note : Empty fields make coparison pass
     *
     * @param String $computedMd5
     * @param String $referenceMd5
     *
     * @return Boolean
     */
    function compareMd5Checksums($computedMd5, $referenceMd5) {
        return($computedMd5 == '' || $referenceMd5 == '' || $computedMd5 == $referenceMd5);
    }
}

?>
