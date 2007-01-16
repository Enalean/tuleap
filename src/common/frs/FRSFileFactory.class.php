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
 * $Id$
 */

require_once('FRSFile.class.php');
require_once('common/dao/FRSFileDao.class.php');

/**
 * 
 */
class FRSFileFactory {

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

        if($dar->isError()){
            return;
        }
        
        if(!$dar->valid()) {
            return;
        }

        $data_array =& $dar->current();

        return(FRSFileFactory::getFRSFileFromArray($data_array));
    }
    
    function &getFRSFilesFromDb($release_id) {
        $_id = (int) $release_id;
        $dao =& $this->_getFRSFileDao();
        $dar = $dao->searchByReleaseId($_id);

        if($dar->isError()){
            return;
        }
        
        if(!$dar->valid()) {
            return;
        }

        $files = array();
		while ($dar->valid()){
        	$data_array =& $dar->current();
        	$files[] = FRSFileFactory::getFRSFileFromArray($data_array);
        	$dar->next();
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
    
    var $dao;
    
    function &_getFRSFileDao() {
        if (!$this->dao) {
            $this->dao =& new FRSFileDao(CodexDataAccess::instance());
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
     * create a new file from a temporary file
     *
     * @return true or id(auto_increment) if there is no error
     */
    function createFromTmpFile($name=null, $tmp_name=null,
                               $release_id=null, 
                               $type_id=null, $processor_id=null) {
        $file = new FRSFile();
        $file->setFileName($name);
        $file->setFileSize(filesize($tmp_name));
        $file->setReleaseID($release_id);
        $file->setTypeID($type_id);
        $file->setProcessorID($processor_id);
        $file_location = $file->getFileLocation();
        // move the file from temp dir to its real storage place
        if (rename($tmp_name, $file_location)) {
            $file->setFileName($file_location);
            return $this->create($file->toArray());
        } else {
            return false;
        }
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
	    	exec ("/bin/echo \"". $file_name ."::". group_getunixname($group_id) ."::$time\" >> $ftp_incoming_dir/.delete_files");
	
	    	return 1;
	  	}
	}
    
    /** 
     * Returns true if user has permissions to add files
     * 
     * NOTE : For the moment, only super admin, project admin (A) and file admin (R2) can add files
     * 
     * @param int $group_id the project ID this file is in
     * @param int $user_id the ID of the user. If not given or 0, take the current user
     * @return boolean true if the user has permission to add files, false otherwise
     */ 
    function userCanAdd($group_id,$user_id=0) {
        $pm =& PermissionsManager::instance();
        $um =& UserManager::instance();
        $user =& $um->getUserById($user_id);
        $ok = $user->isSuperUser() || user_ismember($group_id,'R2') || user_ismember($group_id,'A');
        return $ok;
    }

}

?>
