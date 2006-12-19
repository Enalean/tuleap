<?php
/**
 * Copyright (c) Xerox, 2006. All Rights Reserved.
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

require_once('FRSPackage.class.php');
require_once('common/dao/FRSPackageDao.class.php');

/**
 * 
 */
class FRSPackageFactory {

    function FRSPackageFactory() {
        
    }

    function &getFRSPackageFromArray(&$array) {
        $frs_package = null;
        $frs_package = new FRSPackage($array);
        return $frs_package;
    }

    function &getFRSPackageFromDb($package_id = null, $group_id=null) {
        $_id = (int) $package_id;
        $dao =& $this->_getFRSPackageDao();
        if($group_id){
        	$_group_id = (int) $group_id;
        	$dar = $dao->searchInGroupById($_id, $_group_id);
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

        return(FRSPackageFactory::getFRSPackageFromArray($data_array));
    }
    
    function &getFRSPackageByFileIdFromDb($file_id){
    	$_id = (int) $file_id;
        $dao =& $this->_getFRSPackageDao();
        $dar = $dao->searchByFileId($_id);
        
        if($dar->isError()){
            return;
        }
        
        if(!$dar->valid()) {
            return;
        }
        
        $data_array =& $dar->current();

        return(FRSPackageFactory::getFRSPackageFromArray($data_array));
    }
    
    function &getFRSPackageByReleaseIDFromDb($release_id, $group_id) {
        $_id = (int) $release_id;
        $_group_id = (int) $group_id;
        $dao =& $this->_getFRSPackageDao();
        $dar = $dao->searchInGroupByReleaseId($_id, $_group_id);

        if($dar->isError()){
            return;
        }
        
        if(!$dar->valid()) {
            return;
        }

        $data_array =& $dar->current();

        return(FRSPackageFactory::getFRSPackageFromArray($data_array));
    }
    
    function &getFRSPackagesFromDb($group_id) {
        $_id = (int) $group_id;
        $dao =& $this->_getFRSPackageDao();
        $dar = $dao->searchByGroupId($_id);
        if($dar->isError()){
            return;
        }
        
        if(!$dar->valid()) {
            return;
        }
        
        $packages = array();
		while ($dar->valid()){		
        	$data_array =& $dar->current();
        	$packages[] = FRSPackageFactory::getFRSPackageFromArray($data_array);
        	$dar->next();
		}

        return $packages;
    }

    
    function isPackageNameExist($package_name, $group_id){
    	$_id = (int) $group_id;
        $dao =& $this->_getFRSPackageDao();
        $dar = $dao->searchPackageByName($package_name, $_id);

        if($dar->isError()){
            return;
        }
        
        return $dar->valid();
    }
    
    
    var $dao;
    function &_getFRSPackageDao() {
        if (!$this->dao) {
            $this->dao =& new FRSPackageDao(CodexDataAccess::instance());
        }
        return $this->dao;
    }
    
    
    function update($data_array) {
        $dao =& $this->_getFRSPackageDao();
        return $dao->updateFromArray($data_array);
    }
    
    
    function create($data_array) {
        $dao =& $this->_getFRSPackageDao();
        $id = $dao->createFromArray($data_array);
        return $id;
    }

}

?>
