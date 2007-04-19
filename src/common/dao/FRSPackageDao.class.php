<?php
/**
 * Copyright (c) CodeX, 2006. All Rights Reserved.
 *
 * Originally written by Marc Nazarian, 2006
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

class FRSPackageDao extends DataAccessObject {

    function FRSPackageDao(&$da) {
        DataAccessObject::DataAccessObject($da);
        $this->table_name = 'frs_package';
    }

    /**
     * Return the array that match given id.
     *
     * @return DataAccessResult
     */
    function searchById($id) {
        $_id = (int) $id;
        return $this->_search(' p.package_id = '.$_id, '', ' ORDER BY rank DESC LIMIT 1');
    }
    
    function searchInGroupById($id, $group_id) {
        $_id = (int) $id;
        $_group_id = (int) $group_id;
        return $this->_search(' p.package_id = '.$_id.' AND p.group_id = '.$_group_id, '', ' ORDER BY rank DESC LIMIT 1');
    }
    
    function searchByFileId($file_id){
       $_file_id = (int) $file_id;
       return $this->_search(' f.file_id ='.$_file_id.' AND f.release_id = r.release_id AND r.package_id = p.package_id','',
			   				 'ORDER BY rank DESC LIMIT 1', array('frs_release AS r','frs_file AS f'));
    }
    
    function searchInGroupByReleaseId($id, $group_id) {
       $_id = (int) $id;
       $_group_id = (int) $group_id;
       return $this->_search('p.group_id = '.$_group_id.' AND r.release_id = '.$_id.' AND p.package_id = r.package_id','',
	   						 'ORDER BY rank DESC LIMIT 1', array('frs_release AS r'));
    }

    function searchByIdList($idList) {
        if(is_array($idList) && count($idList) > 0) {
            $sql_where = sprintf(' p.package_id IN (%s)', implode(', ', $idList));
        }
        return $this->_search($sql_where, '', '');
    }

    /**
     * Return the list of packages for a given projet according to filters
     *
     * @return DataAccessResult
     */
    function searchByGroupId($id) {
        $_id = (int) $id; 
        return $this->_search(' p.group_id = '.$_id, '', ' ORDER BY rank ASC ');
    }
    
    function searchActivePackagesByGroupId($id){
    	$_id = (int) $id;
    	return $this->_search(' group_id='.$_id.' AND status_id = 1','','ORDER BY rank');
    }
   
    function _search($where, $group = '', $order = '', $from = array()) {
        $sql = 'SELECT p.* '
            .' FROM frs_package AS p '
            .(count($from) > 0 ? ', '.implode(', ', $from) : '') 
            .(trim($where) != '' ? ' WHERE '.$where.' ' : '') 
            .$group
            .$order;
        return $this->retrieve($sql);
    }
    
    
    function searchPackageByName($package_name, $group_id){
    	$_group_id = (int) $group_id;
    	return $this->_search(' group_id='.$_group_id.' AND name='.$this->da->quoteSmart(htmlspecialchars($package_name)),'','');
    }
    

    /**
     * create a row in the table frs_package
     *
     * @return true or id(auto_increment) if there is no error
     */
    function create($group_id=null, $name=null, 
                    $status_id=null, $rank=null, 
                    $approve_license=null) {

        $arg    = array();
        $values = array();

        if($group_id !== null) {
            $arg[] = 'group_id';
            $values[] = ((int) $group_id);
        }

        if($name !== null) {
            $arg[] = 'name';
            $values[] = $this->da->quoteSmart($name);
        }

        if($status_id !== null) {
            $arg[] = 'status_id';
            $values[] = ((int) $status_id);
        }

        if($rank !== null) {
            $arg[] = 'rank';
            $values[] = $this->prepareRanking(0, $group_id, $rank, array('primary_key' => 'package_id', 'parent_key' => 'group_id'));
        }

        if($approve_license !== null) {
            $arg[] = 'approve_license';
            $values[] = ($approve_license ? 1 : 0);
        }

        $sql = 'INSERT INTO frs_package'
            .'('.implode(', ', $arg).')'
            .' VALUES ('.implode(', ', $values).')';
        return $this->_createAndReturnId($sql);
    }
    
    function createFromArray($data_array) {
        $arg    = array();
        $values = array();
        $cols   = array('group_id', 'name', 'status_id', 'rank', 'approve_license');
        foreach ($data_array as $key => $value) {
            if ($key == 'rank') {
                $value = $this->prepareRanking(0, $data_array['group_id'], $value, array('primary_key' => 'package_id', 'parent_key' => 'group_id'));
            }
            if (in_array($key, $cols)) {
                $arg[]    = $key;
                $values[] = $this->da->quoteSmart($value);
            }
        }
        if (count($arg)) {
            $sql = 'INSERT INTO frs_package '
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
     * Update a row in the table frs_package 
     *
     * @return true if there is no error
     */
    function updateById($package_id, $group_id, $name=null,
                    $status_id=null, $rank=null, $approve_license=null) {       
       
        $argArray = array();

        if($group_id !== null) {
            $argArray[] = 'group_id='.((int) $group_id);
        }

        if($name !== null) {
            $argArray[] = 'name='.$this->da->quoteSmart($name);
        }

        if($status_id !== null) {
            $argArray[] = 'status_id='.((int) $status_id);
        }

        if($rank !== null) {
            $argArray[] = 'rank='. $this->prepareRanking($package_id, $group_id, $rank, array('primary_key' => 'package_id', 'parent_key' => 'group_id'));
        }

        if($approve_license !== null) {
            $argArray[] = 'approve_license='.($approve_license ? 1 : 0);
        }

        $sql = 'UPDATE frs_package'
            .' SET '.implode(', ', $argArray)
            .' WHERE package_id='.((int) $package_id);

        $inserted = $this->update($sql);
        return $inserted;
    }

    function updateFromArray($data_array) {
        $updated = false;
        $id = false;
        if (isset($data_array['package_id'])) {
            $package_id = $data_array['package_id'];
        }
        if ($package_id) {
            $dar = $this->searchById($package_id);
            if (!$dar->isError() && $dar->valid()) {
                $current =& $dar->current();
                $set_array = array();
                foreach($data_array as $key => $value) {
                    if ($key != 'package_id' && $value != $current[$key]) {
                        if ($key == 'rank') {
                            $value = $this->prepareRanking($package_id, $current['group_id'], $value, array('primary_key' => 'package_id', 'parent_key' => 'group_id'));
                        }
                        $set_array[] = $key .' = '. $this->da->quoteSmart($value);
                    }
                }
                if (count($set_array)) {
                    $sql = 'UPDATE frs_package'
                        .' SET '.implode(' , ', $set_array)
                        .' WHERE package_id='. $this->da->quoteSmart($package_id);
                    $updated = $this->update($sql);
                }
            }
        }
        return $updated;
    }

    /**
     * Delete entry that match $package_id in frs_package
     *
     * @param $package_id int
     * @return true if there is no error
     */
    function delete($package_id) {
        $sql = sprintf("DELETE FROM frs_package WHERE package_id=%d",
                       $package_id);

        $deleted = $this->update($sql);
        return $deleted;
    }

}

?>
