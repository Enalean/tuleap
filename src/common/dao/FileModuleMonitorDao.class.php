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

class FileModuleMonitorDao extends DataAccessObject {

    function FileModuleMonitorDao(&$da) {
        DataAccessObject::DataAccessObject($da);
    }

    function whoIsMonitoringPackageByID($group_id, $package_id){
		
		$_package_id = (int) $package_id;
        $_group_id = (int) $group_id;
        
        $sql = sprintf("SELECT u.email,p.name "
              ."FROM user AS u,filemodule_monitor AS fm, frs_package AS p "
              ."WHERE u.user_id = fm.user_id " 
			  ."AND fm.filemodule_id = p.package_id "
			  ."AND fm.filemodule_id = %s "
			  ."AND p.group_id = %s "
			  ."AND ( u.status='A' OR u.status='R' )",
			  $this->da->quoteSmart($_package_id),
			  $this->da->quoteSmart($_group_id));
        
        return $this->retrieve($sql);
    }
    
    function searchById($id) {
        $_id = (int) $id;
        return $this->_search(' fm.filemodule_id = '.$_id, '', ' ORDER BY filemodule_id DESC');
    }
    
    function _search($where, $group = '', $order = '', $from = array()) {
        $sql = 'SELECT fm.* '
            .' FROM filemodule_monitor AS fm '
            .(count($from) > 0 ? ', '.implode(', ', $from) : '') 
            .(trim($where) != '' ? ' WHERE '.$where.' ' : '') 
            .$group
            .$order;
        return $this->retrieve($sql);
    }
}

?>
