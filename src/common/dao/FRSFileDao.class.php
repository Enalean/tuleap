<?php
/**
 * Copyright (c) CodeX, 2006. All Rights Reserved.
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
require_once('include/DataAccessObject.class.php');

class FRSFileDao extends DataAccessObject {

    function FRSFileDao(&$da) {
        DataAccessObject::DataAccessObject($da);
    }

    /**
     * Return the array that match given id.
     *
     * @return DataAccessResult
     */
    function searchById($id) {
        $_id = (int) $id;
        return $this->_search(' f.file_id = '.$_id, '', ' ORDER BY release_time DESC LIMIT 1');
    }
    
    function searchInReleaseById($id, $group_id) {
        $_id = (int) $id;
        $_group_id = (int) $group_id;
        return $this->_search(' p.group_id='.$_group_id.' AND r.release_id = f.release_id' .
        		              ' AND r.package_id = p.package_id AND f.file_id ='.$_id,'',
        		              'ORDER BY post_date DESC LIMIT 1',array('frs_package AS p', 'frs_release AS r'));
    }

    function searchByIdList($idList) {
        if(is_array($idList) && count($idList) > 0) {
            $sql_where = sprintf(' f.file_id IN (%s)', implode(', ', $idList));
        }
        return $this->_search($sql_where, '', '');
    }

    /**
     * Return the list of files for a given release according to filters
     *
     * @param int $id the ID of the release the files belong to
     * @param int $only_active_files 1 means that only files with an active status will be retrieved. 0 means all files
     * @return DataAccessResult
     */
    function searchByReleaseId($id, $only_active_files = 1) {
        $_id = (int) $id;
        $where_status = "";
        if ($only_active_files == 1) {
            $where_status = " AND status='A' ";
        }
        return $this->_search(' release_id='.$_id.' '.$where_status,'','');
    }
    		
   
   function searchInfoByGroupFileID($group_id, $file_id){
        $_group_id = (int) $group_id;
        $_file_id = (int) $file_id;
        
        $sql = sprintf("SELECT f.filename, f.file_id AS file_id, p.group_id AS group_id, " .
        				"p.package_id, r.release_id "
              		  ."FROM frs_release AS r, frs_package AS p, frs_file AS f "
              		  ."WHERE p.group_id= %s "
			  		  ."AND r.package_id = p.package_id "
			  		  ."AND f.release_id = r.release_id "
                      ."AND f.file_id=%s ",
			  			$this->da->quoteSmart($_group_id),
			  			$this->da->quoteSmart($_file_id));
        return $this->retrieve($sql);
    }
   
    /**
     * Retrieve file info from database.
     * 
     * @param int $release_id the ID of the release the files belong to
     * @param int $only_active_files 1 means that only files with an active status will be retrieved. 0 means all files
     */
    function searchInfoFileByReleaseID($release_id, $only_active_files = 1){
    	$_release_id = (int) $release_id;
    
    $where_status = "";
    if ($only_active_files) {
        $where_status = " AND status='A' ";
    }
    	
    	$sql = sprintf("SELECT frs_file.file_id AS file_id, frs_file.filename AS filename, frs_file.file_size AS file_size," 
				 	. "frs_file.release_time AS release_time, frs_file.type_id AS type, frs_file.processor_id AS processor," 
				 	. "frs_dlstats_filetotal_agg.downloads AS downloads  FROM frs_file " 
				 	. "LEFT JOIN frs_dlstats_filetotal_agg ON frs_dlstats_filetotal_agg.file_id=frs_file.file_id " 
				 	. "WHERE release_id=%s".$where_status , 	
				 	$this->da->quoteSmart($_release_id));
		return $this->retrieve($sql);
    }
   
    function _search($where, $group = '', $order = '', $from = array()) {
        $sql = 'SELECT f.* '
            .' FROM frs_file AS f '
            .(count($from) > 0 ? ', '.implode(', ', $from) : '') 
            .(trim($where) != '' ? ' WHERE '.$where.' ' : '') 
            .$group
            .$order;
        return $this->retrieve($sql);
    }
    
    function searchFileByName($file_name, $group_id){
    	$_group_id = (int) $group_id;
    	return $this->_search(' p.group_id='.$_group_id.' AND r.release_id = f.release_id' .
    						  ' AND r.package_id = p.package_id AND filename='.$this->da->quoteSmart($file_name).' AND f.status=\'A\'','',
							  '', array('frs_package AS p', 'frs_release AS r'));
    }

    /**
     * create a row in the table frs_file
     *
     * @return true or id(auto_increment) if there is no error
     */
    function create($file_name=null, $release_id=null, $type_id=null,
    				$processor_id=null, $release_time=null, 
                    $file_size=null, $post_date=null, $status ='A') {

        $arg    = array();
        $values = array();

        if($file_name !== null) {
            $arg[] = 'filename';
            $values[] = $this->da->quoteSmart($file_name);
        }

        if($release_id !== null) {
            $arg[] = 'release_id';
            $values[] = ((int) $release_id);
        }
        
        if($type_id !== null) {
            $arg[] = 'type_id';
            $values[] = ((int) $type_id);
        }

        if($processor_id !== null) {
            $arg[] = 'processor_id';
            $values[] = ((int) $processor_id);
        }


        $arg[] = 'release_time';
        $values[] = ((int) time());
        
        if($file_size !== null) {
            $arg[] = 'file_size';
            $values[] = ((int) $file_size);
        } else {
            $arg[] = 'file_size';
            $values[] = filesize($file_name); 
        }

        $arg[] = 'post_date';
        $values[] = ((int) time());

        $arg[] = 'status';
        $values[] = $status;
        
        $sql = 'INSERT INTO frs_file'
            .'('.implode(', ', $arg).')'
            .' VALUES ('.implode(', ', $values).')';
        return $this->_createAndReturnId($sql);
    }
    
    
    function createFromArray($data_array) {
        $arg    = array();
        $values = array();
        $cols   = array('filename', 'release_id', 'type_id', 'processor_id', 'file_size', 'status');
        foreach ($data_array as $key => $value) {
            if (in_array($key, $cols)) {
                $arg[]    = $key;
                $values[] = $this->da->quoteSmart($value);
            }
        }
        $arg[]    = 'release_time';
        $values[] = $this->da->quoteSmart(time());
        $arg[]    = 'post_date';
        $values[] = $this->da->quoteSmart(time());
        if (count($arg)) {
            $sql = 'INSERT INTO frs_file '
                .'('.implode(', ', $arg).')'
                .' VALUES ('.implode(', ', $values).')';
            return $this->_createAndReturnId($sql);
        } else {
            return false;
        }
    }
    
    
    function _createAndReturnId($sql) {
        $inserted = $this->update($sql);
        if ($inserted) {
            $dar = $this->retrieve("SELECT LAST_INSERT_ID() AS id");
            if ($row = $dar->getRow()) {
                $inserted = $row['id'];
            } else {
                $inserted = $dar->isError();
            }
        }
        return $inserted;
    }
    /**
     * Update a row in the table frs_file 
     *
     * @return true if there is no error
     */
    function updateById($file_id, $file_name=null, $release_id=null, $type_id=null,
    				$processor_id=null, $release_time=null, $file_size=null, $status=null) {       
       
        $argArray = array();

		if($file_name !== null) {
            $argArray[] = 'file_name='.$this->da->quoteSmart($file_name);
        }

        if($release_id !== null) {
            $argArray[] = 'release_id='.((int) $release_id);
        }
		
        if($type_id !== null) {
            $argArray[] = 'type_id='.((int) $type_id);
        }
        
        if($processor_id !== null) {
            $argArray[] = 'processor_id='.((int) $processor_id);
        }
        
        if($release_time !== null) {
            $argArray[] = 'release_time='.((int) $release_time);
        }

        if($file_size !== null) {
            $argArray[] = 'file_size='.((int) $file_size);
        }

        if($status !== null) {
            $argArray[] = 'status='.$this->da->quoteSmart($status);
        }

        $sql = 'UPDATE frs_file'
            .' SET '.implode(', ', $argArray)
            .' WHERE file_id='.((int) $file_id);

        $inserted = $this->update($sql);
        return $inserted;
    }

    function updateFromArray($data_array) {
        $updated = false;
        $id = false;
        if (isset($data_array['file_id'])) {
            $file_id = $data_array['file_id'];
        }
        if ($file_id) {
            $dar = $this->searchById($file_id);
            if (!$dar->isError() && $dar->valid()) {
                $current =& $dar->current();
                $set_array = array();
                foreach($data_array as $key => $value) {
                    if ($key != 'id' && $key!= 'post_date' && $value != $current[$key]) {
                        $set_array[] = $key .' = '. $this->da->quoteSmart($value);
                    }
                }
                if (count($set_array)) {
                    $sql = 'UPDATE frs_file'
                        .' SET '.implode(' , ', $set_array)
                        .' WHERE file_id='. $this->da->quoteSmart($file_id);
                    $updated = $this->update($sql);
                }
            }
        }
        return $updated;
    }

    /**
     * Delete entry that match $release_id in frs_file
     *
     * @param $file_id int
     * @return true if there is no error
     */
    function delete($file_id) {
        $sql = sprintf("UPDATE frs_file SET status='D' WHERE file_id=%d",
                       $file_id);

        $deleted = $this->update($sql);
        return $deleted;
    }
    
    /**
     * Log the file download action into the database
     * 
     * @param Object{FRSFile) $file the FRSFile Object to log the download of
     * @param int $user_id the user that download the file (if 0, the current user will be taken)
     * @return boolean true if there is no error, false otherwise
     */
    function logDownload($file, $user_id = 0) {
    	   if ($user_id == 0) {
    	       // must take the current user
           $user_id = user_getid();
    	   }
       //Insert a new entry in the file release download log table
       $sql = "INSERT INTO filedownload_log(user_id,filerelease_id,time) "
             ."VALUES ('".$user_id."','".$file->getFileID()."','".time()."')";
       $inserted = $this->update($sql);
       return $inserted;	
    }
}

?>
