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

    function &getFRSFileFromDb($file_id) {
        $_id = (int) $file_id;
        $dao =& $this->_getFRSFileDao();
        $dar = $dao->searchById($_id);

        if($dar->isError()){
            return;
        }
        
        if(!$dar->valid()) {
            return;
        }

        $data_array =& $dar->current();

        return(FRSFileFactory::getFileFromArray($data_array));
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
        	$files[] = FRSFileFactory::getFileFromArray($data_array);
		}

        return $files;
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

}

?>
