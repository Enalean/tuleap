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


	var $dao;

	function & _getFileModuleMonitorDao() {
		if (!$this->dao) {
			$this->dao = & new FileModuleMonitorDao(CodexDataAccess :: instance());
		}
		return $this->dao;
	}
    

    

}

?>
