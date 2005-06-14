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
$Language->loadLanguageMsg('project/project');

if ( !user_isloggedin() ) {
	exit_not_logged_in();
	return;
}

if ( !$ath->userIsAdmin() ) {
	exit_permission_denied();
	return;
}

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
//We aren't going to reset, unless the user's asked to
$reset = false;
if (isset($_REQUEST['reset'])) {
    $reset = true;
 }
//We display by group, unless the user's asked to not
$group_first = true;
if (isset($_REQUEST['group_first']) && $_REQUEST['group_first'] === "0") {
    $group_first = false;
 }
//We show the first group or the first field, unless the user's asked to show a specific
$selected_id = false;
if (isset($_REQUEST['selected_id']) && is_numeric($_REQUEST['selected_id'])) {
    $selected_id = $_REQUEST['selected_id'];
 }
//}}}
switch ($perm_type) {
 case 'tracker':
     if ($update || $reset) {
         if ($update) {
             permission_process_update_tracker_permissions($group_id, $atid, $_REQUEST);
         } else if($reset) {
             //The user want to clear permissions
             permission_clear_all_tracker($group_id, $atid);
         }
     }

     //display
     $ath->adminHeader(array('title'=>$Language->getText('tracker_admin_field_usage','tracker_admin').$Language->getText('tracker_admin_field_usage','usage_admin'),'help' => 'TrackerAdministration.html#TrackerPermissionsManagement')); //TODO
     $ugroups_permissions = permission_get_tracker_ugroups_permissions($group_id, $atid);
     $ath->displayPermissionsTracker($ugroups_permissions);
     break;
 case 'fields':
     if ($update) {
         if (isset($_REQUEST['permissions']) && is_array($_REQUEST['permissions'])) {
             $fields = $art_field_fact->getAllUsedFields();
             permission_process_update_fields_permissions($group_id, $atid, $fields, $_REQUEST['permissions']);
         }
     }
     //display
     $ath->adminHeader(array('title'=>$Language->getText('tracker_admin_field_usage','tracker_admin').$Language->getText('tracker_admin_field_usage','usage_admin'),'help' => 'TrackerAdministration.html#TrackerPermissionsManagement')); //TODO
     $ugroups_permissions = permission_get_field_tracker_ugroups_permissions($group_id, $atid, $art_field_fact->getAllUsedFields(), false);
     $ath->displayPermissionsFieldsTracker($ugroups_permissions, $group_first, $selected_id);
     break;
 default:
     $ath->adminHeader(array('title'=>$Language->getText('tracker_admin_field_usage','tracker_admin').$Language->getText('tracker_admin_field_usage','usage_admin'),'help' => 'TrackerAdministration.html#TrackerPermissionsManagement')); //TODO
     $ath->displayPermissionsGeneralMenu();
     break;
 }

$ath->footer(array());
?>