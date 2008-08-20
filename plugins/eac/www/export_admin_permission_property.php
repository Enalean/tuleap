<?php 
 /**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Ikram BOUOUD, 2008
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
 * 
 */
require_once('pre.php');
require_once('showPermsVisitor.class.php');
require_once('userGroupExportMembers.class.php');


$valueGroupId               = new Valid_UInt('group_id');
if($valueGroupId->validate($group_id)){
    $group_id               = $request->get('group_id'); 
 }else {
    echo 'no group_id choosen';
    exit;
 }
$valueObjectId              = new Valid_UInt('object_id');
if($valueObjectId->validate($object_id)){
    $object_id              = $request->get('object_id'); 
 }else {
    echo 'no object_id choosen';
    exit;
 }
$valuePermissionType       = new Valid_String('permission_type');
if($valuePermissionType->validate($permission_type)){
    $permission_type        = $request->get('permission_type'); 
    
 }else {
    echo 'no permission_type choosen';
    exit;
 }
$sep                        = ',';
$ugroups                    = array();
$visitor                    = new  showPermsVisitor();
$ugroups                    = $visitor->listUgroups($group_id, $ugroups);
$ugroupMembers              = new userGroupExportMembers();
header('Content-Disposition: filename=export_item_permissions.csv');
header('Content-Type: text/csv');



echo 'user group'.$sep.'read'.$sep.'write'.$sep.'manage'.PHP_EOL;

foreach($ugroups as $ugrp){
$resultat_permissions       = $visitor->extractPermissions($group_id, $ugrp['ugroup_id'], $object_id, $permission_type);
while ($row_permissions     = db_fetch_array($resultat_permissions))
      {
      $permission = $visitor->permissionFormatting($permission_type);
      echo $ugrp['name']."".$sep."".$permission."".PHP_EOL;
      $resultat_ugroup_members      = $ugroupMembers->userList($ugrp['ugroup_id'], $group_id);
         while ($row_ugroup_members = db_fetch_array($resultat_ugroup_members ))
	  { 
	    echo $row_ugroup_members['user_name']."".PHP_EOL;
	  }
      }
}

?>
