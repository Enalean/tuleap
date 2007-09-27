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
require_once('common/include/UserManager.class.php');

class FRSReleaseDao extends DataAccessObject {

    function FRSReleaseDao(&$da) {
        DataAccessObject::DataAccessObject($da);
    }

    /**
     * Return the array that match given id.
     *
     * @return DataAccessResult
     */
    function searchById($id) {
        $_id = (int) $id;
        return $this->_search(' r.release_id = '.$_id, '', ' ORDER BY release_date DESC LIMIT 1');
    }
    
    function searchInGroupById($id, $group_id) {
        $_id = (int) $id;
        $_group_id = (int) $group_id;
        return $this->_search(' p.group_id='.$_group_id.' AND r.release_id='.$_id.' AND r.package_id=p.package_id',
        					  '', ' ORDER BY release_date DESC LIMIT 1', array('frs_package AS p'));
    }
    
    function searchByGroupPackageReleaseID($release_id, $group_id, $package_id){
    	$_id = (int) $release_id;
        $_group_id = (int) $group_id;
        $_package_id = (int) $package_id;
        
        return $this->_search(' p.package_id='.$_package_id.' AND p.group_id='.$_group_id.' AND r.release_id='.$_id
        					   .' AND r.package_id=p.package_id', '', 'ORDER BY release_date DESC LIMIT 1',
        					   array('frs_package AS p'));
    }
    
    function searchByGroupPackageID($group_id, $package_id=null){
        $_group_id = (int) $group_id;
        if($package_id){
        	$_package_id = (int) $package_id;
        }else{
        	$_package_id = null;
        }
        $sql = sprintf("SELECT r.release_id, p.name AS package_name, p.package_id, r.name AS release_name, "
        		      ."r.status_id, s.name AS status_name "
              		  ."FROM frs_release AS r, frs_package AS p, frs_status AS s "
              		  ."WHERE p.group_id= %s "
			  		  ."AND r.package_id = p.package_id "
			  		  .($package_id ? "AND p.package_id = %s " : "")
			  		  ."AND s.status_id=r.status_id",
			  			$this->da->quoteSmart($_group_id),
			  			$this->da->quoteSmart($_package_id));
        return $this->retrieve($sql);
    }

    function searchByIdList($idList) {
        if(is_array($idList) && count($idList) > 0) {
            $sql_where = sprintf(' r.release_id IN (%s)', implode(', ', $idList));
        }
        return $this->_search($sql_where, '', '');
    }

    /**
     * Return the list of releases for a given package according to filters
     *
     * @return DataAccessResult
     */
    function searchByPackageId($id) {
        $_id = (int) $id;
        return $this->_search(' package_id='.$_id, '', ' ORDER BY release_date DESC, release_id DESC ');
    }
   
    function _search($where, $group = '', $order = '', $from = array()) {
        $sql = 'SELECT r.* '
            .' FROM frs_release AS r '
            .(count($from) > 0 ? ', '.implode(', ', $from) : '') 
            .(trim($where) != '' ? ' WHERE '.$where.' ' : '') 
            .$group
            .$order;
        return $this->retrieve($sql);
    }
    
    function searchActiveReleasesByPackageId($id){
    	$_id = (int) $id;
        return $this->_search(' package_id='.$_id.' AND status_id = 1','','ORDER BY release_date DESC, release_id DESC');
    }
    
    function searchReleaseByName($release_name, $package_id){
    	$_package_id = (int) $package_id;
    	return $this->_search(' package_id='.$_package_id
    	                      .' AND name='.$this->da->quoteSmart(htmlspecialchars($release_name)),'','');
    }
    

    /**
     * create a row in the table frs_release
     *
     * @return true or id(auto_increment) if there is no error
     */
    function create($package_id=null, $name=null,
    				$notes=null, $changes=null, 
                    $status_id=null, $preformatted=1, $release_date=null) {

        $arg    = array();
        $values = array();

        if($package_id !== null) {
            $arg[] = 'package_id';
            $values[] = ((int) $package_id);
        }

        if($name !== null) {
            $arg[] = 'name';
            $values[] = $this->da->quoteSmart($name);
        }
        
        if($notes !== null) {
            $arg[] = 'notes';
            $values[] = $this->da->quoteSmart($notes);
        }
        
        if($changes !== null) {
            $arg[] = 'changes';
            $values[] = $this->da->quoteSmart($changes);
        }

        if($status_id !== null) {
            $arg[] = 'status_id';
            $values[] = ((int) $status_id);
        }
        
        if($preformatted !== null) {
            $arg[] = 'preformatted';
            $values[] = ((int) $preformatted);
        }

		if($release_date !== null) {
        	$arg[] = 'release_date';
       		$values[] = ((int) $release_date);
		} else {
			$arg[] = 'release_date';
       		$values[] = ((int) time());
		}

		$um =& UserManager::instance();
        $user =& $um->getCurrentUser();
        $arg[] = 'released_by';
        $values[] = $this->da->quoteSmart($user->getID());

        $sql = 'INSERT INTO frs_release'
            .'('.implode(', ', $arg).')'
            .' VALUES ('.implode(', ', $values).')';
        return $this->_createAndReturnId($sql);
    }
    
    
    function createFromArray($data_array) {
        $arg    = array();
        $values = array();
        $cols   = array('package_id', 'name', 'notes', 'changes', 'status_id',  'release_date');
        $is_date = false;
        foreach ($data_array as $key => $value) {
            if (in_array($key, $cols)) {
            	if($key=='release_date')	{
            		$is_date=true;
            	}
                $arg[]    = $key;
                $values[] = $this->da->quoteSmart($value);
            }
        }
        
        $arg[] = 'preformatted';
        $values[] = 1;
        
        if(!$is_date){
        	$arg[] = 'release_date';
        	$values[] = $this->da->quoteSmart(time());
        }
        
        $arg[] = 'released_by';
        $um =& UserManager::instance();
        $user =& $um->getCurrentUser();
        $values[] = $this->da->quoteSmart($user->getID());
        
        if (count($arg)) {
            $sql = 'INSERT INTO frs_release '
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
     * Update a row in the table frs_release 
     *
     * @return true if there is no error
     */
    function updateById($release_id, $package_id=null, $name=null,
    				$notes=null, $changes=null, $status_id=null, 
    				$preformatted=null, $release_date=null) {       
       
        $argArray = array();

        if($package_id !== null) {
            $argArray[] = 'package_id='.((int) $package_id);
        }
		
		if($name !== null) {
            $argArray[] = 'name='.$this->da->quoteSmart($name);
        }

        if($notes !== null) {
            $argArray[] = 'notes='.$this->da->quoteSmart($notes);
        }
        
        if($changes !== null) {
            $argArray[] = 'changes='.$this->da->quoteSmart($changes);
        }
        
        if($status_id !== null) {
            $argArray[] = 'status_id='.((int) $status_id);
        }

        if($preformatted !== null) {
            $argArray[] = 'preformatted='.((int) $preformatted);
        }

        if($release_date !== null) {
            $argArray[] = 'release_date='.((int) $release_date);
        }


        $sql = 'UPDATE frs_release'
            .' SET '.implode(', ', $argArray)
            .' WHERE release_id='.((int) $release_id);

        $inserted = $this->update($sql);
        return $inserted;
    }

    function updateFromArray($data_array) {
        $updated = false;
        $id = false;
        if (isset($data_array['release_id'])) {
            $release_id = $data_array['release_id'];
        }
        if ($release_id) {
            $dar = $this->searchById($release_id);
            if (!$dar->isError() && $dar->valid()) {
                $current =& $dar->current();
                $set_array = array();
                foreach($data_array as $key => $value) {
                    if ($key != 'release_id' && $key != 'released_by' && $value != $current[$key]) {
                        $set_array[] = $key .' = '. $this->da->quoteSmart($value);
                    }
                }
                if (count($set_array)) {
                    $sql = 'UPDATE frs_release'
                        .' SET '.implode(' , ', $set_array)
                        .' WHERE release_id='. $this->da->quoteSmart($release_id);
                    $updated = $this->update($sql);
                }
                if(count($set_array)==0) { $updated = true;}
            }
        }
        return $updated;
    }

    /**
     * Delete entry that match $release_id in frs_release
     *
     * @param $release_id int
     * @return true if there is no error
     */
    function delete($release_id) {
        $sql = sprintf("DELETE FROM frs_release WHERE release_id=%d",
                       $release_id);

        $deleted = $this->update($sql);
        return $deleted;
    }

}

?>
