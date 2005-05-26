<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id$
//
//
//  Written for CodeX by Nicolas Terray
//

$Language->loadLanguageMsg('tracker/tracker');

if ( !user_isloggedin() ) {
	exit_not_logged_in();
	return;
}

if ( !$ath->userIsAdmin() ) {
	exit_permission_denied();
	return;
}

$ath->adminHeader(array('title'=>$Language->getText('tracker_admin_field_usage','tracker_admin').$Language->getText('tracker_admin_field_usage','usage_admin'),'help' => 'TrackerAdministration.html#TrackerPermissionsManagement')); //TODO


//{{{ We check variables submitted by user
if (!isset($_REQUEST['perm_type']) || 
    !($_REQUEST['perm_type'] === 'tracker' || $_REQUEST['perm_type'] === 'fields')
    ) {
    $perm_type = '';
 }else {
    $perm_type = $_REQUEST['perm_type'];
 }
//We aren't going to update, unless the user's asked to
$update = false;
if (isset($_REQUEST['update'])) {
    $update = true;
 }
//}}}

switch ($perm_type) {
 case 'tracker':
     if ($update) {
         //The user want to update permissions for the tracker.
         //We look into the request for specials variable
         $prefixe_expected     = 'permissions_';
         $len_prefixe_expected = strlen($prefixe_expected);
         foreach($_REQUEST as $key => $value) {
             $pos = strpos($key, $prefixe_expected);
             if ($pos !== false) {
                 //We've just found a variable
                 //We check now if the suffixe (id of ugroup) and the value is numeric values
                 $suffixe = substr($key, $len_prefixe_expected);
                 if (is_numeric($suffixe) && is_numeric($value)) {
                     $ugroup_id  = $suffixe;
                     switch($value) {
                     case 0: //TRACKER_FULL_ACCESS
                         //On efface les anciennes permissions
                         permission_clear_ugroup_object($group_id, $ugroup_id, $atid); //TODO: traitements des erreurs
                         permission_add_ugroup($group_id, 'TRACKER_ACCESS_FULL', $atid, $ugroup_id); //TODO: traitements des erreurs
                         break;
                     case 1: //TRACKER_ACCESS_ASSIGNEE
                         permission_clear_ugroup_object($group_id, $ugroup_id, $atid); //TODO: traitements des erreurs
                         permission_add_ugroup($group_id, 'TRACKER_ACCESS_ASSIGNEE', $atid, $ugroup_id); //TODO: traitements des erreurs
                         break;
                     case 2: //TRACKER_ACCESS_SUBMITTER
                         permission_clear_ugroup_object($group_id, $ugroup_id, $atid); //TODO: traitements des erreurs
                         permission_add_ugroup($group_id, 'TRACKER_ACCESS_SUBMITTER', $atid, $ugroup_id); //TODO: traitements des erreurs
                         break;
                     case 3: //TRACKER_ACCESS_SUBMITTER *AND* TRACKER_ACCESS_ASSIGNEE
                         permission_clear_ugroup_object($group_id, $ugroup_id, $atid); //TODO: traitements des erreurs
                         permission_add_ugroup($group_id, 'TRACKER_ACCESS_SUBMITTER', $atid, $ugroup_id); //TODO: traitements des erreurs
                         permission_add_ugroup($group_id, 'TRACKER_ACCESS_ASSIGNEE', $atid, $ugroup_id); //TODO: traitements des erreurs
                         break;
                     case 100: //NO ACCESS (Remove permission)
                         permission_clear_ugroup_object($group_id, $ugroup_id, $atid); //TODO: traitements des erreurs
                         break;
                     default://unknown permission
                         //do nothing
                         break;
                     }
                 }
             }
         }
     }

     //We retrieve project ugroups
     $result = db_query("SELECT ugroup_id, name FROM ugroup WHERE group_id='".$group_id."' ORDER BY ugroup_id");
     $ugroups = array();
     while ($row = db_fetch_array($result)) {
         $ugroups[] = array(
                            'id'   => $row[0],
                            'name' => $row[1]
                            );
     }

     //We retrieve permissions for each ugroup
     $ugroups_permissions = array();
     foreach($ugroups as $ugroup) {
         $permissions = array();
         if (permission_ugroup_has_permission('TRACKER_ACCESS_FULL', $atid, $ugroup['id'])) {
                 $permissions['TRACKER_ACCESS_FULL'] = 1;
         }
         if (permission_ugroup_has_permission('TRACKER_ACCESS_SUBMITTER', $atid, $ugroup['id'])) {
                 $permissions['TRACKER_ACCESS_SUBMITTER'] = 1;
         }
         if (permission_ugroup_has_permission('TRACKER_ACCESS_ASSIGNEE', $atid, $ugroup['id'])) {
                 $permissions['TRACKER_ACCESS_ASSIGNEE'] = 1;
         }
         $ugroups_permissions[] = array(
                                        'ugroup' => $ugroup,
                                        'permissions' => $permissions
                                        );
     }
     $ath->displayPermissionsTracker($ugroups_permissions);
     break;
 case 'fields':
     echo 'NYI';
     break;
 default:
     $ath->displayPermissionsGeneralMenu();
     break;
 }

$ath->footer(array());
?>