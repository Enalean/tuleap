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
//We aren't going to reset, unless the user's asked to
$reset = false;
if (isset($_REQUEST['reset'])) {
    $reset = true;
 }
//}}}

switch ($perm_type) {
 case 'tracker':
     if ($update || $reset) {
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
         } else if($reset) {
             //The user want to clear permissions
             permission_clear_all($group_id, 'TRACKER_ACCESS_FULL', $atid, false);
             permission_clear_all($group_id, 'TRACKER_ACCESS_ASSIGNEE', $atid, false);
             permission_clear_all($group_id, 'TRACKER_ACCESS_SUBMITTER', $atid, false);
             
         }

         //We log the changes
         permission_add_history($group_id, 'TRACKER_ACCESS_FULL', $atid);
         permission_add_history($group_id, 'TRACKER_ACCESS_ASSIGNEE', $atid);
         permission_add_history($group_id, 'TRACKER_ACCESS_SUBMITTER', $atid);

     }
     //display
     $ugroups_permissions = permission_get_tracker_ugroups_permissions($group_id, $atid);
     $ath->displayPermissionsTracker($ugroups_permissions);
     break;
 case 'fields':
     //display
     $ugroups_permissions = array();
     //We look for fields
     $fields = $art_field_fact->getAllUsedFields();
     foreach($fields as $field) {
         $fake_id = permission_build_field_id($atid, $field->getID());
         $ugroups = permission_get_field_tracker_ugroups_permissions($group_id, $fake_id);
         $ugroups_permissions[$field->getID()] = array(
                                                       'field' => array(
                                                                        'label' => $field->getLabel(),
                                                                        'id'    => $field->getID(),
                                                                        'link'  => '/tracker/admin/index.php?group_id='.$group_id.'&atid='.$atid.'&func=display_field_update&field_id='.$field->getID()
                                                                        ),
                                                       'ugroups' => $ugroups
                                                       );
     }
     $ath->displayPermissionsFieldsTracker($ugroups_permissions);
     break;
 default:
     $ath->displayPermissionsGeneralMenu();
     break;
 }

$ath->footer(array());
?>