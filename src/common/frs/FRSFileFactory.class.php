<?php
/*
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
        return $dao->updateFromArray($data_array);
    }
    
    
    function create($data_array) {
        $dao =& $this->_getFRSFileDao();
        $id = $dao->createFromArray($data_array);
        return $id;
    }
    
    /**
     * create a new file from a file present in the incoming dir
     *
     * @return true or id(auto_increment) if there is no error
     */
    function createFromIncomingFile($name=null, $release_id=null, 
                               $type_id=null, $processor_id=null) {
        
        // check if the file exists
        $uploaded_files = $this->getUploadedFileNames();
        if (! in_array($name, $uploaded_files)) {
            $this->setError('File not found: '.$name);
            return false;
        }

	// Don't use filesize() : Workaround for files larger than 2 GB
 	$filesize = file_utils_get_size($GLOBALS['ftp_incoming_dir'] . '/' . $name);

        $file = new FRSFile();
        $file->setFileName($name);
        $file->setFileSize($filesize);
        $file->setReleaseID($release_id);
        $file->setTypeID($type_id);
        $file->setProcessorID($processor_id);
        $file->setStatus('A');
        
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
    	$dao =& $this->_getFRSFileDao();
    	return $dao->delete($_id);
    }
/*

Physically delete a file from the download server and database

First, make sure the file is theirs
Second, delete it from the db
Third, delete it from the download server

return 0 if file not deleted, 1 otherwise
*/
    function delete_file ($group_id,$file_id) {
	  	GLOBAL $ftp_incoming_dir;


	  	$file =& $this->getFRSFileFromDb($file_id, $group_id); 
	  	
	  	if (!$file) {
	    	//file not found for this project
	    	return 0;
	  	} else {
	    	/*
	    	   delete the file from the database
	    	*/
	    	$file_name = $file->getFileName();
	    	$this->_delete($file_id);
	    //append the filename and project name to a temp file for the root perl job to grab
	    	$time = time();
	    	$pm = ProjectManager::instance();
            exec ('/bin/echo "'. $file_name .'::'. $pm->getProject($group_id)->getUnixName() .'::'.$time.'" >> '. $ftp_incoming_dir .'/.delete_files');
	
	    	return 1;
	  	}
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
            //if ((!ereg('^\.', $file[0])) && is_file($GLOBALS['ftp_incoming_dir'] . '/' . $file)) { // Not useful with workaround
            $uploaded_file_names[] = $file;
            //}
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
        $ret_val = null;
        $exec_res = null;
        //exec("/bin/date > /tmp/" . $group_unix_name . "$group_id", $exec_res);
		//exec($GLOBALS['codendi_bin_prefix'] . "/fileforge /tmp/" . $group_unix_name . "$group_id " . $group_unix_name, $exec_res);
        $cmd = $GLOBALS['codendi_bin_prefix'] . "/fileforge $file_name " . $group_unix_name . "/" . $upload_sub_dir;
        exec($cmd, $exec_res, $ret_val);
        return $ret_val;
    }

}

?>
