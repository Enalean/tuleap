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
require_once('common/dao/FileModuleMonitorDao.class.php');

/**
 * 
 */
class FileModuleMonitorFactory {

    function FileModuleMonitorFactory() {
        
    }

	function whoIsMonitoringPackageById($group_id, $package_id){
		$_group_id = (int) $group_id;
		$_package_id = (int) $package_id;
		
        $dao =& $this->_getFileModuleMonitorDao();
        $dar = $dao->whoIsMonitoringPackageByID($group_id, $package_id);
        if($dar->isError()){
            return;
        }
        
        if(!$dar->valid()) {
            return;
        }
        
        $data_array = array();
		while ($dar->valid()){
        	$data_array[] = $dar->current();
        	$dar->next();
		}
        return $data_array;
	}
	
	function &getFilesModuleMonitorFromDb($id) {
        $_id = (int) $id;
        $dao =& $this->_getFileModuleMonitorDao();
        $dar = $dao->searchById($_id);
        

        $data_array = array();
        if(!$dar->isError() && $dar->valid()) {
            while ($dar->valid()){
                $data_array[] = $dar->current();
                $dar->next();
            }
        }
        return $data_array;
    }
    
    /**
	 *  isMonitoring - Is the current user in the list of people monitoring this package.
	 *
	 *  @return	boolean	is_monitoring.
	 */
	function isMonitoring($filemodule_id) {

		$_filemodule_id = (int) $filemodule_id;
        $dao =& $this->_getFileModuleMonitorDao();
        $dar = $dao->searchMonitoringFileByUserAndPackageId($_filemodule_id);
		
		if($dar->isError()){
            return;
        }


		if (!$dar->valid() || $dar->rowCount() < 1) {
			return false;
		} else {
			return true;
		}
	}


	var $dao;

	function & _getFileModuleMonitorDao() {
		if (!$this->dao) {
			$this->dao = new FileModuleMonitorDao(CodendiDataAccess :: instance());
		}
		return $this->dao;
	}

	function setMonitor($filemodule_id) {
		$dao = & $this->_getFileModuleMonitorDao();
		$res = $dao->create($filemodule_id);
		return $res;
	}
    
    function stopMonitor($filemodule_id){
    	$_id = (int) $filemodule_id;
    	$dao =& $this->_getFileModuleMonitorDao();
    	return $dao->delete($_id);
    }
     
}

?>
