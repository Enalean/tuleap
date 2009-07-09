<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2009
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
 */

require_once('common/dao/include/DataAccessObject.class.php');

class permsDao extends DataAccessObject {

    public function __construct(&$da){
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
     * Method getPermissions which extract permissions of a  user group on an item
     * @param int item_id item id
     * @param int permission_type (PLUGIN_DOCMAN_MANAGE, PLUGIN_DOCMAN_READ, PLUGIN_DOCMAN_WRITE)
     * @return array row_perms permission information
     */
    
    public function getPermissions($item_id, $group_id){
        $table_perms = array();
        $sql = sprintf( 'SELECT DISTINCT Ugrp.ugroup_id, Ugrp.name, P.permission_type '.
                       ' FROM permissions P '.
                       ' JOIN ugroup Ugrp ON (Ugrp.ugroup_id=P.ugroup_id) '. 
                       ' WHERE Ugrp.group_id IN (100, %d) '. 
                       ' AND P.object_id = %d '.
                       ' AND P.permission_type IN ("PLUGIN_DOCMAN_READ","PLUGIN_DOCMAN_WRITE","PLUGIN_DOCMAN_MANAGE","PLUGIN_DOCMAN_ADMIN")',
                       $this->da->escapeInt($group_id),$this->da->escapeInt($item_id));
        $dar = $this->retrieve($sql);
        if($dar && !$dar->isError()) {
            while ($row_perms = $dar->getRow()) {
        	    $row['ugroup_id']       = $row_perms['ugroup_id'];
        	    $row['name']            = util_translate_name_ugroup($row_perms['name']);
        	    $row['permission_type'] = $row_perms['permission_type']; 
                $table_perms[] = $row;
            }
        }
        return $table_perms;
     }
     
}




?>