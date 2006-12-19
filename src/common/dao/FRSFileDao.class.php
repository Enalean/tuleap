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
        
        $sql = sprintf("SELECT f.* "
              ."FROM frs_file AS f, frs_release AS r, frs_package AS p WHERE p.group_id = %s " 
			  ."AND r.release_id = f.release_id "
			  ."AND r.package_id = p.package_id "
			  ."AND f.file_id = %s ORDER BY release_date DESC LIMIT 1",
			  $this->da->quoteSmart($_group_id),
			  $this->da->quoteSmart($_id));
        return $this->retrieve($sql);
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
     * @return DataAccessResult
     */
    function searchByReleaseId($id) {
        $_id = (int) $id;
        $sql = sprintf("SELECT * FROM frs_file WHERE release_id = %s",
                $this->da->quoteSmart($_id));
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
    	$sql = sprintf("SELECT f.* FROM frs_release AS r, frs_file AS f, frs_package AS p WHERE "
    					."p.group_id = %s AND r.release_id = f.release_id "
    					."AND r.package_id = p.package_id AND name = %s",
                $this->da->quoteSmart($_group_id),
                $this->da->quoteSmart($file_name));
        return $this->retrieve($sql);
    }

    /**
     * create a row in the table frs_file
     *
     * @return true or id(auto_increment) if there is no error
     */
    function create($file_name=null, $release_id=null, $type_id=null,
    				$processor_id=null, $release_time=null, 
                    $file_size=null, $post_date=null) {

        $arg    = array();
        $values = array();

        if($file_name !== null) {
            $arg[] = 'file_name';
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
        $values[] = ((int) time);
        
        if($file_size !== null) {
            $arg[] = 'file_size';
            $values[] = ((int) $file_size);
        }

        $arg[] = 'post_date';
        $values[] = ((int) time());

        $sql = 'INSERT INTO frs_file'
            .'('.implode(', ', $arg).')'
            .' VALUES ('.implode(', ', $values).')';
        return $this->_createAndReturnId($sql);
    }
    
    
    function createFromArray($data_array) {
        $arg    = array();
        $values = array();
        $cols   = array('file_name', 'release_id', 'type_id', 'processor_id', 'file_size');
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
    				$processor_id=null, $release_time=null, $file_size=null) {       
       
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
        $sql = sprintf("DELETE FROM frs_file WHERE file_id=%d",
                       $file_id);

        $deleted = $this->update($sql);
        return $deleted;
    }

}

?>
