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
             //The user want to update permissions for the tracker.
             //We look into the request for specials variable
             $prefixe_expected     = 'permissions_';
             $len_prefixe_expected = strlen($prefixe_expected);

             //The actual permissions
             $stored_ugroups_permissions = permission_get_tracker_ugroups_permissions($group_id, $atid);

             //We look for anonymous and registered users' permissions, both in the user's request and in the db
             $user_set_anonymous_to_fullaccess        = isset($_REQUEST[$prefixe_expected.$GLOBALS['UGROUP_ANONYMOUS']]) && $_REQUEST[$prefixe_expected.$GLOBALS['UGROUP_ANONYMOUS']] === "0";
             $user_set_registered_to_fullaccess       = isset($_REQUEST[$prefixe_expected.$GLOBALS['UGROUP_REGISTERED']]) && $_REQUEST[$prefixe_expected.$GLOBALS['UGROUP_ANONYMOUS']] === "0";
             $anonymous_is_already_set_to_fullaccess  = isset($stored_ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]['permissions']['TRACKER_ACCESS_FULL']);
             $registered_is_already_set_to_fullaccess = isset($stored_ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['permissions']['TRACKER_ACCESS_FULL']);
             $registered_is_already_set_to_assignee   = isset($stored_ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['permissions']['TRACKER_ACCESS_ASSIGNEE']);
             $registered_is_already_set_to_submitter  = isset($stored_ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['permissions']['TRACKER_ACCESS_SUBMITTER']);
             //ANONYMOUS
             ////////////////////////////////////////////////////////////////
             if (isset($_REQUEST[$prefixe_expected.$GLOBALS['UGROUP_ANONYMOUS']])) {
                 switch($_REQUEST[$prefixe_expected.$GLOBALS['UGROUP_ANONYMOUS']]) {
                 case 0:
                     //TRACKER_ACCESS_FULL
                     //-------------------
                     if (!$anonymous_is_already_set_to_fullaccess) {
                         foreach($stored_ugroups_permissions as $stored_ugroup_id => $stored_ugroup_permissions) {
                             if ($stored_ugroup_id === $GLOBALS['UGROUP_ANONYMOUS']) {
                                 permission_add_ugroup($group_id, 'TRACKER_ACCESS_FULL', $atid, $stored_ugroup_id);
                                 $anonymous_is_already_set_to_fullaccess = true;
                             } else {
                                 //We remove permissions for others ugroups
                                 if (count($stored_ugroup_permissions['permissions']) > 0 
                                     && (!isset($_REQUEST[$prefixe_expected.$stored_ugroup_id]) || $_REQUEST[$prefixe_expected.$stored_ugroup_id] != 100)) {

                                     $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_anon_full', array($stored_ugroup_permissions['ugroup']['name']));
                                     if (isset($stored_ugroup_permissions['permissions']['TRACKER_ACCESS_FULL'])) {
                                         permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_FULL', $stored_ugroup_id, $atid);
                                         if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                                             $registered_is_already_set_to_fullaccess = false;
                                         }
                                     }
                                     if (isset($stored_ugroup_permissions['permissions']['TRACKER_ACCESS_ASSIGNEE'])) {
                                         permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_ASSIGNEE', $stored_ugroup_id, $atid);
                                         if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                                             $registered_is_already_set_to_assignee = false;
                                         }
                                     }
                                     if (isset($stored_ugroup_permissions['permissions']['TRACKER_ACCESS_SUBMITTER'])) {
                                         permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_SUBMITTER', $stored_ugroup_id, $atid);
                                         if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                                             $registered_is_already_set_to_submitter = false;
                                         }
                                     }
                                 }
                             }
                         }
                     }
                     break;
                 case 1:
                     //TRACKER_ACCESS_ASSIGNEE
                     //-----------------------
                     //forbidden, do nothing
                     break;
                 case 2:
                     //TRACKER_ACCESS_SUBMITTER
                     //------------------------
                     //forbidden, do nothing
                     break;
                 case 3:
                     //TRACKER_ACCESS_SUBMITTER && TRACKER_ACCESS_ASSIGNEE
                     //---------------------------------------------------
                     //forbidden, do nothing
                     break;
                 case 100:
                     //NO ACCESS
                     //---------
                     if ($anonymous_is_already_set_to_fullaccess) {
                         permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_FULL', $GLOBALS['UGROUP_ANONYMOUS'], $atid);
                         $anonymous_is_already_set_to_fullaccess = false;
                     }
                     break;
                 default:
                     //do nothing
                     break;
                 }
             }

             //REGISTERED
             ////////////////////////////////////////////////////////////////
             if (isset($_REQUEST[$prefixe_expected.$GLOBALS['UGROUP_REGISTERED']])) {
                 switch($_REQUEST[$prefixe_expected.$GLOBALS['UGROUP_REGISTERED']]) {
                 case 0:
                     //TRACKER_ACCESS_FULL
                     //-------------------
                     if (!$registered_is_already_set_to_fullaccess) {
                         //It is not necessary to process if the anonymous has full access
                         if ($anonymous_is_already_set_to_fullaccess) {
                             $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_anon_full', array($stored_ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['ugroup']['name']));
                         } else {
                             foreach($stored_ugroups_permissions as $stored_ugroup_id => $stored_ugroup_permissions) {
                                 if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                                     //We remove old permissions
                                     if ($registered_is_already_set_to_assignee) {
                                         permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_ASSIGNEE', $stored_ugroup_id, $atid);
                                         $registered_is_already_set_to_assignee = false;
                                     }
                                     if ($registered_is_already_set_to_submitter) {
                                         permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_SUBMITTER', $stored_ugroup_id, $atid);
                                         $registered_is_already_set_to_submitter = false;
                                     }
                                     permission_add_ugroup($group_id, 'TRACKER_ACCESS_FULL', $atid, $stored_ugroup_id);
                                     $registered_is_already_set_to_fullaccess = true;
                                 } else if($stored_ugroup_id !== $GLOBALS['UGROUP_ANONYMOUS']) { //ugroups other than anonymous
                                     //We remove permissions for others ugroups
                                     if (count($stored_ugroup_permissions['permissions']) > 0 
                                         && (!isset($_REQUEST[$prefixe_expected.$stored_ugroup_id]) || $_REQUEST[$prefixe_expected.$stored_ugroup_id] != 100)) {
                                         $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_full', array($stored_ugroup_permissions['ugroup']['name']));
                                         if (isset($stored_ugroup_permissions['permissions']['TRACKER_ACCESS_FULL'])) {
                                             permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_FULL', $stored_ugroup_id, $atid);
                                         }
                                         if (isset($stored_ugroup_permissions['permissions']['TRACKER_ACCESS_ASSIGNEE'])) {
                                             permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_ASSIGNEE', $stored_ugroup_id, $atid);
                                         }
                                         if (isset($stored_ugroup_permissions['permissions']['TRACKER_ACCESS_SUBMITTER'])) {
                                             permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_SUBMITTER', $stored_ugroup_id, $atid);
                                         }
                                     }
                                 }
                             }
                         }
                     }
                     break;
                 case 1:
                     //TRACKER_ACCESS_ASSIGNEE
                     //-----------------------
                     if (!$registered_is_already_set_to_assignee) {
                         //It is not necessary to process if the anonymous has full access (anon can't have assignee or submitter access)
                         if ($anonymous_is_already_set_to_fullaccess) {
                             $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_anon_full', array($stored_ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['ugroup']['name']));
                         } else {
                             foreach($stored_ugroups_permissions as $stored_ugroup_id => $stored_ugroup_permissions) {
                                 if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                                     //We remove old permissions
                                     if ($registered_is_already_set_to_fullaccess) {
                                         permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_FULL', $stored_ugroup_id, $atid);
                                         $registered_is_already_set_to_fullaccess = false;
                                     }
                                     if ($registered_is_already_set_to_submitter) {
                                         permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_SUBMITTER', $stored_ugroup_id, $atid);
                                         $registered_is_already_set_to_submitter = false;
                                     }
                                     permission_add_ugroup($group_id, 'TRACKER_ACCESS_ASSIGNEE', $atid, $stored_ugroup_id);
                                     $registered_is_already_set_to_assignee = true;
                                 } else if($stored_ugroup_id !== $GLOBALS['UGROUP_ANONYMOUS']) { //ugroups other than anonymous
                                     //We remove permissions for others ugroups if they have assignee
                                     if (isset($stored_ugroup_permissions['permissions']['TRACKER_ACCESS_ASSIGNEE']) && !isset($stored_ugroup_permissions['permissions']['TRACKER_ACCESS_SUBMITTER']) 
                                         && (!isset($_REQUEST[$prefixe_expected.$stored_ugroup_id]) || $_REQUEST[$prefixe_expected.$stored_ugroup_id] != 100)) {
                                         $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_assignee', array($stored_ugroup_permissions['ugroup']['name']));
                                         permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_ASSIGNEE', $stored_ugroup_id, $atid);
                                     }
                                 }
                             }
                         }
                     }
                     break;
                 case 2:
                     //TRACKER_ACCESS_SUBMITTER
                     //------------------------
                     if (!$registered_is_already_set_to_submitter) {
                         //It is not necessary to process if the anonymous has full access (anon can't have assignee or submitter access)
                         if ($anonymous_is_already_set_to_fullaccess) {
                             $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_anon_full', array($stored_ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['ugroup']['name']));
                         } else {
                             foreach($stored_ugroups_permissions as $stored_ugroup_id => $stored_ugroup_permissions) {
                                 if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                                     //We remove old permissions
                                     if ($registered_is_already_set_to_fullaccess) {
                                         permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_FULL', $stored_ugroup_id, $atid);
                                         $registered_is_already_set_to_fullaccess = false;
                                     }
                                     if ($registered_is_already_set_to_assignee) {
                                         permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_ASSIGNEE', $stored_ugroup_id, $atid);
                                         $registered_is_already_set_to_assignee = false;
                                     }
                                     permission_add_ugroup($group_id, 'TRACKER_ACCESS_SUBMITTER', $atid, $stored_ugroup_id);
                                     $registered_is_already_set_to_submitter = true;
                                 } else if($stored_ugroup_id !== $GLOBALS['UGROUP_ANONYMOUS']) { //ugroups other than anonymous
                                     //We remove permissions for others ugroups if they have submitter
                                     if (isset($stored_ugroup_permissions['permissions']['TRACKER_ACCESS_SUBMITTER']) && !isset($stored_ugroup_permissions['permissions']['TRACKER_ACCESS_ASSIGNEE']) 
                                         && (!isset($_REQUEST[$prefixe_expected.$stored_ugroup_id]) || $_REQUEST[$prefixe_expected.$stored_ugroup_id] != 100)) {
                                         $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_submitter', array($stored_ugroup_permissions['ugroup']['name']));
                                         permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_SUBMITTER', $stored_ugroup_id, $atid);
                                     }
                                 }
                             }
                         }
                     }
                     break;
                 case 3:
                     //TRACKER_ACCESS_SUBMITTER && TRACKER_ACCESS_ASSIGNEE
                     //---------------------------------------------------
                     if (!($registered_is_already_set_to_submitter && $registered_is_already_set_to_assignee)) {
                         //It is not necessary to process if the anonymous has full access (anon can't have assignee or submitter access)
                         if ($anonymous_is_already_set_to_fullaccess) {
                             $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_anon_full', array($stored_ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['ugroup']['name']));
                         } else {
                             foreach($stored_ugroups_permissions as $stored_ugroup_id => $stored_ugroup_permissions) {
                                 if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                                     //We remove old permissions
                                     if ($registered_is_already_set_to_fullaccess) {
                                         permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_FULL', $stored_ugroup_id, $atid);
                                         $registered_is_already_set_to_fullaccess = false;
                                     }
                                     if (!$registered_is_already_set_to_assignee) {
                                         permission_add_ugroup($group_id, 'TRACKER_ACCESS_ASSIGNEE', $atid, $stored_ugroup_id);
                                         $registered_is_already_set_to_assignee = true;
                                     }
                                     if (!$registered_is_already_set_to_submitter) {
                                         permission_add_ugroup($group_id, 'TRACKER_ACCESS_SUBMITTER', $atid, $stored_ugroup_id);
                                         $registered_is_already_set_to_submitter = true;
                                     }
                                 } else if($stored_ugroup_id !== $GLOBALS['UGROUP_ANONYMOUS']) { //ugroups other than anonymous
                                     //We remove permissions for others ugroups if they have submitter or assignee
                                     if ((isset($stored_ugroup_permissions['permissions']['TRACKER_ACCESS_SUBMITTER']) || isset($stored_ugroup_permissions['permissions']['TRACKER_ACCESS_ASSIGNEE'])) 
                                         && (!isset($_REQUEST[$prefixe_expected.$stored_ugroup_id]) || $_REQUEST[$prefixe_expected.$stored_ugroup_id] != 100)) {
                                         $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_submitter_assignee', array($stored_ugroup_permissions['ugroup']['name']));
                                         if (isset($stored_ugroup_permissions['permissions']['TRACKER_ACCESS_SUBMITTER'])) {
                                             permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_SUBMITTER', $stored_ugroup_id, $atid); 
                                         }
                                         if (isset($stored_ugroup_permissions['permissions']['TRACKER_ACCESS_ASSIGNEE'])) {
                                             permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_ASSIGNEE', $stored_ugroup_id, $atid);
                                         }
                                     }
                                 }
                             }
                         }
                     }
                     break;
                 case 100:
                     //NO SPECIFIC ACCESS
                     //------------------
                     if ($registered_is_already_set_to_assignee) {
                         permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_ASSIGNEE', $GLOBALS['UGROUP_REGISTERED'], $atid);
                         $registered_is_already_set_to_assignee = false;
                     }
                     if ($registered_is_already_set_to_submitter) {
                         permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_SUBMITTER', $GLOBALS['UGROUP_REGISTERED'], $atid);
                         $registered_is_already_set_to_submitter = false;
                     }
                     if ($registered_is_already_set_to_fullaccess) {
                         permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_FULL', $GLOBALS['UGROUP_REGISTERED'], $atid);
                         $registered_is_already_set_to_fullaccess = false;
                     }
                     break;
                 default:
                     //do nothing
                     break;
                 }
             }


             //OTHERS INSIGNIFIANT UGROUPS
             ////////////////////////////////////////////////////////////////
             foreach($_REQUEST as $key => $value) {
                 $pos = strpos($key, $prefixe_expected);
                 if ($pos !== false) {
                     //We've just found a variable
                     //We check now if the suffixe (id of ugroup) and the value is numeric values
                     $suffixe = substr($key, $len_prefixe_expected);
                     if (is_numeric($suffixe)) {
                         $ugroup_id  = $suffixe;
                         if ($ugroup_id != $GLOBALS['UGROUP_ANONYMOUS'] && $ugroup_id != $GLOBALS['UGROUP_REGISTERED']) { //already done.
                             $ugroup_name = $stored_ugroups_permissions[$ugroup_id]['ugroup']['name'];
                             switch($value) {
                             case 0: 
                                 //TRACKER_FULL_ACCESS
                                 //-------------------
                                 if (!isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_FULL'])) {
                                     if ($anonymous_is_already_set_to_fullaccess) { //It is not necessary to process if the anonymous has full access 
                                         $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_anon_full', array($ugroup_name));
                                     } else if ($registered_is_already_set_to_fullaccess) {//It is not necessary to process if the registered has full access 
                                         $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_full', array($ugroup_name));
                                     } else {
                                         //We remove old permissions
                                         if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_ASSIGNEE'])) {
                                             permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_ASSIGNEE', $ugroup_id, $atid);
                                         }
                                         if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_SUBMITTER'])) {
                                             permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_SUBMITTER', $ugroup_id, $atid);
                                         }
                                         permission_add_ugroup($group_id, 'TRACKER_ACCESS_FULL', $atid, $ugroup_id);
                                     }
                                 }
                                 break;
                             case 1: 
                                 //TRACKER_ACCESS_ASSIGNEE
                                 //-----------------------
                                 if (!isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_ASSIGNEE'])) {
                                     //It is not necessary to process if the anonymous has full access 
                                     if ($anonymous_is_already_set_to_fullaccess) {
                                         $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_anon_full', array($ugroup_name));
                                     } else if ($registered_is_already_set_to_fullaccess) {//It is not necessary to process if the registered has full access 
                                         $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_full', array($ugroup_name));
                                     } else if ($registered_is_already_set_to_submitter && $registered_is_already_set_to_assignee) {//It is not necessary to process if the registered has submitter and assignee
                                         $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_submitter_assignee', array($ugroup_name));
                                     } else if ($registered_is_already_set_to_assignee) {//It is not necessary to process if the registered has assignee
                                         $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_assignee', array($ugroup_name));
                                     } else {
                                         //We remove old permissions
                                         if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_FULL'])) {
                                             permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_FULL', $ugroup_id, $atid);
                                         }
                                         if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_SUBMITTER'])) {
                                             permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_SUBMITTER', $ugroup_id, $atid);
                                         }
                                         permission_add_ugroup($group_id, 'TRACKER_ACCESS_ASSIGNEE', $atid, $ugroup_id);
                                     }
                                 }
                                 break;
                             case 2: 
                                 //TRACKER_ACCESS_SUBMITTER
                                 //------------------------
                                 if (!isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_SUBMITTER'])) {
                                     //It is not necessary to process if the anonymous has full access
                                     if ($anonymous_is_already_set_to_fullaccess) {
                                         $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_anon_full', array($ugroup_name));
                                     } else if ($registered_is_already_set_to_fullaccess) {//It is not necessary to process if the registered has full access 
                                         $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_full', array($ugroup_name));
                                     } else if ($registered_is_already_set_to_submitter && $registered_is_already_set_to_assignee) {//It is not necessary to process if the registered has submitter and assignee
                                         $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_submitter_assignee', array($ugroup_name));
                                     } else if ($registered_is_already_set_to_submitter) {//It is not necessary to process if the registered has submitter
                                         $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_submitter', array($ugroup_name));
                                     } else {
                                         //We remove old permissions
                                         if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_FULL'])) {
                                             permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_FULL', $ugroup_id, $atid);
                                         }
                                         if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_ASSIGNEE'])) {
                                             permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_ASSIGNEE', $ugroup_id, $atid);
                                         }
                                         permission_add_ugroup($group_id, 'TRACKER_ACCESS_SUBMITTER', $atid, $ugroup_id);
                                     }
                                 }
                             break;
                             case 3: 
                                 //TRACKER_ACCESS_SUBMITTER && TRACKER_ACCESS_ASSIGNEE
                                 //---------------------------------------------------
                                 if (!(isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_ASSIGNEE']) && isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_SUBMITTER']))) {
                                     //It is not necessary to process if the anonymous has full access
                                     if ($anonymous_is_already_set_to_fullaccess) {
                                         $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_anon_full', array($ugroup_name));
                                     } else if ($registered_is_already_set_to_fullaccess) {//It is not necessary to process if the registered has full access 
                                         $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_full', array($ugroup_name));
                                     } else if ($registered_is_already_set_to_submitter && $registered_is_already_set_to_assignee) {//It is not necessary to process if the registered has submitter and assignee
                                         $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_submitter_assignee', array($ugroup_name));
                                     } else {
                                         //We remove old permissions
                                         if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_FULL'])) {
                                             permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_FULL', $ugroup_id, $atid);
                                         }
                                         if (!isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_ASSIGNEE'])) {
                                             permission_add_ugroup($group_id, 'TRACKER_ACCESS_ASSIGNEE', $atid, $ugroup_id);
                                         }
                                         if (!isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_SUBMITTER'])) {
                                             permission_add_ugroup($group_id, 'TRACKER_ACCESS_SUBMITTER', $atid, $ugroup_id);
                                         }
                                     }
                                 }
                                 break;
                             case 100: 
                                 //NO SPECIFIC ACCESS
                                 //------------------
                                 if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_FULL'])) {
                                     permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_FULL', $ugroup_id, $atid);
                                 }
                                 if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_ASSIGNEE'])) {
                                     permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_ASSIGNEE', $ugroup_id, $atid);
                                 }
                                 if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_SUBMITTER'])) {
                                     permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_SUBMITTER', $ugroup_id, $atid);
                                 }
                                 break;
                             default:
                                 //do nothing
                                 break;
                             }
                         }
                     }
                 }
             }
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
     if ($update || $reset) {
         if ($update && isset($_REQUEST['permissions'])) {
             //The actual permissions
             $stored_ugroups_permissions = permission_get_field_tracker_ugroups_permissions($group_id, $atid, $art_field_fact->getAllUsedFields());

             //We process the request
             reset($_REQUEST['permissions']);
             foreach($_REQUEST['permissions'] as $field_id => $ugroups_permissions) {
                 if (is_numeric($field_id)) {
                     $the_field_can_be_submitted = $field_id != 1 && $field_id != 6 && $field_id != 7;
                     $the_field_can_be_updated   = $the_field_can_be_submitted;
                     //artifact_id#field_id
                     $fake_object_id = permission_build_field_id($atid, $field_id);
                     
                     //We look for anonymous and registered users' permissions, both in the user's request and in the db
                     $user_set_anonymous_to_submit = isset($ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]) && 
                         isset($ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]['submit']) &&
                         $ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]['submit'] === "on";
                     $user_set_anonymous_to_read   = isset($ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]) && 
                         isset($ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]['others']) &&
                         $ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]['others'] === "0";
                     $user_set_anonymous_to_update = isset($ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]) && 
                         isset($ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]['others']) &&
                         $ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]['others'] === "1";
                     $user_set_registered_to_submit = isset($ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]) && 
                         isset($ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['submit']) &&
                         $ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['submit'] === "on";
                     $user_set_registered_to_read   = isset($ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]) && 
                         isset($ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['others']) &&
                         $ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['others'] === "0";
                     $user_set_registered_to_update = isset($ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]) && 
                         isset($ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['others']) &&
                         $ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['others'] === "1";

                     $anonymous_is_already_set_to_submit  = isset($stored_ugroups_permissions[$field_id]['ugroups'][$GLOBALS['UGROUP_ANONYMOUS']]['permissions']['TRACKER_FIELD_SUBMIT']);
                     $anonymous_is_already_set_to_read    = isset($stored_ugroups_permissions[$field_id]['ugroups'][$GLOBALS['UGROUP_ANONYMOUS']]['permissions']['TRACKER_FIELD_READ']);
                     $anonymous_is_already_set_to_update  = isset($stored_ugroups_permissions[$field_id]['ugroups'][$GLOBALS['UGROUP_ANONYMOUS']]['permissions']['TRACKER_FIELD_UPDATE']);
                     $registered_is_already_set_to_submit = isset($stored_ugroups_permissions[$field_id]['ugroups'][$GLOBALS['UGROUP_REGISTERED']]['permissions']['TRACKER_FIELD_SUBMIT']);
                     $registered_is_already_set_to_read   = isset($stored_ugroups_permissions[$field_id]['ugroups'][$GLOBALS['UGROUP_REGISTERED']]['permissions']['TRACKER_FIELD_READ']);
                     $registered_is_already_set_to_update = isset($stored_ugroups_permissions[$field_id]['ugroups'][$GLOBALS['UGROUP_REGISTERED']]['permissions']['TRACKER_FIELD_UPDATE']);
                     
                     //ANONYMOUS
                     ////////////////////////////////////////////////////////////////
                     //Firstly we set permissions for anonymous users
                     if (isset($ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']])) {
                         $ugroup_permissions = $ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']];

                         //SUBMIT Permission
                         //-----------------
                         if ($the_field_can_be_submitted && !$anonymous_is_already_set_to_submit && $user_set_anonymous_to_submit) {
                             //if the ugroup is anonymous, we have to erase submit permissions for other ugroups
                             reset($stored_ugroups_permissions[$field_id]['ugroups']);
                             foreach($stored_ugroups_permissions[$field_id]['ugroups'] as $stored_ugroup_id => $stored_ugroup_permissions) {
                                 if ($stored_ugroup_id === $GLOBALS['UGROUP_ANONYMOUS']) {
                                     permission_add_ugroup($group_id, 'TRACKER_FIELD_SUBMIT', $fake_object_id, $stored_ugroup_id);
                                     $anonymous_is_already_set_to_submit = true;
                                 } else {
                                     if (isset($stored_ugroup_permissions['permissions']['TRACKER_FIELD_SUBMIT'])) {
                                         $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_submit', array($stored_ugroup_permissions['ugroup']['name']));
                                         permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_SUBMIT', $stored_ugroup_id, $fake_object_id);
                                     }
                                 }
                             }
                         }else if ($anonymous_is_already_set_to_submit && !$user_set_anonymous_to_submit) {
                             permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_SUBMIT', $GLOBALS['UGROUP_ANONYMOUS'], $fake_object_id);
                             $anonymous_is_already_set_to_submit = false;
                         }

                         //UPDATE Permission
                         //---------------
                         if ($the_field_can_be_updated && !$anonymous_is_already_set_to_update && $user_set_anonymous_to_update) {
                             //if the ugroup is anonymous, we have to erase submt permissions for other ugroups
                             reset($stored_ugroups_permissions[$field_id]['ugroups']);
                             foreach($stored_ugroups_permissions[$field_id]['ugroups'] as $stored_ugroup_id => $stored_ugroup_permissions) {
                                 if ($stored_ugroup_id === $GLOBALS['UGROUP_ANONYMOUS']) {
                                     permission_add_ugroup($group_id, 'TRACKER_FIELD_UPDATE', $fake_object_id, $stored_ugroup_id);
                                     $anonymous_is_already_set_to_update = true;
                                 } else {
                                     if (isset($stored_ugroup_permissions['permissions']['TRACKER_FIELD_UPDATE'])) {
                                         $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_update', array($stored_ugroup_permissions['ugroup']['name']));
                                         permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_UPDATE', $stored_ugroup_id, $fake_object_id);
                                     }
                                     if (isset($stored_ugroup_permissions['permissions']['TRACKER_FIELD_READ'])) {
                                         $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_update', array($stored_ugroup_permissions['ugroup']['name']));
                                         permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_READ', $stored_ugroup_id, $fake_object_id);
                                     }
                                 }
                             }
                         }else if ($anonymous_is_already_set_to_update && !$user_set_anonymous_to_update) {
                             permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_UPDATE', $GLOBALS['UGROUP_ANONYMOUS'], $fake_object_id);
                             $anonymous_is_already_set_to_update = false;
                         }

                         //READ Permission
                         //---------------
                         if (!$anonymous_is_already_set_to_read && $user_set_anonymous_to_read) {
                             //if the ugroup is anonymous, we have to erase submit permissions for other ugroups
                             reset($stored_ugroups_permissions[$field_id]['ugroups']);
                             foreach($stored_ugroups_permissions[$field_id]['ugroups'] as $stored_ugroup_id => $stored_ugroup_permissions) {
                                 if ($stored_ugroup_id === $GLOBALS['UGROUP_ANONYMOUS']) {
                                     permission_add_ugroup($group_id, 'TRACKER_FIELD_READ', $fake_object_id, $stored_ugroup_id);
                                     $anonymous_is_already_set_to_read = true;
                                 } else {
                                     if (isset($stored_ugroup_permissions['permissions']['TRACKER_FIELD_READ'])) {
                                         $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_read', array($stored_ugroup_permissions['ugroup']['name']));
                                         permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_READ', $stored_ugroup_id, $fake_object_id);
                                     }
                                 }
                             }
                         }else if ($anonymous_is_already_set_to_read && !$user_set_anonymous_to_read) {
                             permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_READ', $GLOBALS['UGROUP_ANONYMOUS'], $fake_object_id);
                             $anonymous_is_already_set_to_read = false;
                         }
                     }

                     //REGISTERED
                     ////////////////////////////////////////////////////////////////
                     //Secondly we set permissions for registered users
                     if (isset($ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']])) {
                         $ugroup_permissions = $ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']];
                         
                         //SUBMIT Permission
                         //-----------------
                         if ($the_field_can_be_submitted && !$registered_is_already_set_to_submit && $user_set_registered_to_submit) {
                             //if the ugroup is registered, we have to:
                             // 1. check consistency with current permissions for anonymous users
                             if ($user_set_anonymous_to_submit || $anonymous_is_already_set_to_submit) {
                                 $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_submit', array($stored_ugroups_permissions[$field_id]['ugroups'][$GLOBALS['UGROUP_REGISTERED']]['ugroup']['name']));
                             } else {
                                 // 2. erase submit permissions for other ugroups
                                 foreach($stored_ugroups_permissions[$field_id]['ugroups'] as $stored_ugroup_id => $stored_ugroup_permissions) {
                                     if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                                         permission_add_ugroup($group_id, 'TRACKER_FIELD_SUBMIT', $fake_object_id, $stored_ugroup_id);
                                         $registered_is_already_set_to_submit = true;
                                     } else {
                                         if (isset($stored_ugroup_permissions['permissions']['TRACKER_FIELD_SUBMIT'])) {
                                             $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_regis_submit', array($stored_ugroup_permissions['ugroup']['name']));
                                             permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_SUBMIT', $stored_ugroup_id, $fake_object_id);
                                         }
                                     }
                                 }
                             }
                         }else if ($registered_is_already_set_to_submit && !$user_set_registered_to_submit) {
                             permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_SUBMIT', $GLOBALS['UGROUP_REGISTERED'], $fake_object_id);
                             $registered_is_already_set_to_submit = false;
                         }

                         //UPDATE Permission
                         //---------------
                         if ($the_field_can_be_updated && !$registered_is_already_set_to_update && $user_set_registered_to_update) {
                             //if the ugroup is registered, we have to:
                             // 1. check consistency with current permissions for anonymous users
                             if ($user_set_anonymous_to_update || $anonymous_is_already_set_to_update) {
                                 $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_update', array($stored_ugroups_permissions[$field_id]['ugroups'][$GLOBALS['UGROUP_REGISTERED']]['ugroup']['name']));
                             } else {
                                 // 2. erase update permissions for other ugroups
                                 foreach($stored_ugroups_permissions[$field_id]['ugroups'] as $stored_ugroup_id => $stored_ugroup_permissions) {
                                     if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                                         permission_add_ugroup($group_id, 'TRACKER_FIELD_UPDATE', $fake_object_id, $stored_ugroup_id);
                                         $registered_is_already_set_to_update = true;
                                     } else if($stored_ugroup_id !== $GLOBALS['UGROUP_ANONYMOUS']) { //ugroups other than anonymous
                                         if (isset($stored_ugroup_permissions['permissions']['TRACKER_FIELD_UPDATE'])) {
                                             $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_regis_update', array($stored_ugroup_permissions['ugroup']['name']));
                                             permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_UPDATE', $stored_ugroup_id, $fake_object_id);
                                         }
                                         if (isset($stored_ugroup_permissions['permissions']['TRACKER_FIELD_READ'])) {
                                             $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_regis_update', array($stored_ugroup_permissions['ugroup']['name']));
                                             permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_READ', $stored_ugroup_id, $fake_object_id);
                                         }
                                     }
                                 }
                             }
                         }else if ($registered_is_already_set_to_update && !$user_set_registered_to_update) {
                             permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_UPDATE', $GLOBALS['UGROUP_REGISTERED'], $fake_object_id);
                             $registered_is_already_set_to_update = false;
                         }

                         //READ Permission
                         //---------------
                         if (!$registered_is_already_set_to_read && $user_set_registered_to_read) {
                             //if the ugroup is registered, we have to:
                             // 1. check consistency with current permissions for anonymous users
                             if ($user_set_anonymous_to_read || $anonymous_is_already_set_to_read) {
                                 $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_read', array($stored_ugroups_permissions[$field_id]['ugroups'][$GLOBALS['UGROUP_REGISTERED']]['ugroup']['name']));
                             } else {
                                 // 2. erase read permissions for other ugroups
                                 foreach($stored_ugroups_permissions[$field_id]['ugroups'] as $stored_ugroup_id => $stored_ugroup_permissions) {
                                     if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                                         permission_add_ugroup($group_id, 'TRACKER_FIELD_READ', $fake_object_id, $stored_ugroup_id);
                                         $registered_is_already_set_to_read = true;
                                     } else {
                                         if (isset($stored_ugroup_permissions['permissions']['TRACKER_FIELD_READ'])) {
                                             $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_regis_read', array($stored_ugroup_permissions['ugroup']['name']));
                                             permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_READ', $stored_ugroup_id, $fake_object_id);
                                         }
                                     }
                                 }
                             }
                         }else if ($registered_is_already_set_to_read && !$user_set_registered_to_read) {
                             permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_READ', $GLOBALS['UGROUP_REGISTERED'], $fake_object_id);
                             $registered_is_already_set_to_read = false;
                         }
                     }


                     //OTHER INSIGNIFIANT UGROUPS
                     ////////////////////////////////////////////////////////////////
                     foreach($ugroups_permissions as $ugroup_id => $ugroup_permissions) {
                         if (is_numeric($ugroup_id) && $ugroup_id != $GLOBALS['UGROUP_REGISTERED'] && $ugroup_id != $GLOBALS['UGROUP_ANONYMOUS']) {
                             $name_of_ugroup = $stored_ugroups_permissions[$field_id]['ugroups'][$ugroup_id]['ugroup']['name'];
                         
                             //SUBMIT Permission
                             //-----------------
                             if ($the_field_can_be_submitted && !isset($stored_ugroups_permissions[$field_id]['ugroups'][$ugroup_id]['permissions']['TRACKER_FIELD_SUBMIT'])
                                 && isset($ugroup_permissions['submit']) 
                                 && $ugroup_permissions['submit'] === "on") {
                                 //if the ugroup is not anonymous and not registered, we have to:
                                 // check consistency with current permissions for anonymous users
                                 // and current permissions for registered users
                                 if ($user_set_anonymous_to_submit || $anonymous_is_already_set_to_submit) {
                                     $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_submit', array($name_of_ugroup));
                                 } else if ($user_set_registered_to_submit || $registered_is_already_set_to_submit) {
                                     $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_regis_submit', array($name_of_ugroup));       
                                 } else {
                                     permission_add_ugroup($group_id, 'TRACKER_FIELD_SUBMIT', $fake_object_id, $ugroup_id);
                                 }
                             } else if(isset($stored_ugroups_permissions[$field_id]['ugroups'][$ugroup_id]['permissions']['TRACKER_FIELD_SUBMIT'])
                                       && isset($ugroup_permissions['submit']) 
                                       && $ugroup_permissions['submit'] !== "on") {
                                 //If we don't have already clear the permissions
                                 if (!$user_set_anonymous_to_submit && !$user_set_registered_to_submit) {
                                     permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_SUBMIT', $ugroup_id, $fake_object_id);
                                 }
                             }

                             //UPDATE Permission
                             //-----------------
                             if ($the_field_can_be_updated && !isset($stored_ugroups_permissions[$field_id]['ugroups'][$ugroup_id]['permissions']['TRACKER_FIELD_UPDATE'])
                                 && isset($ugroup_permissions['others']) 
                                 && $ugroup_permissions['others'] === "1") {
                                 //if the ugroup is not anonymous and not registered, we have to:
                                 // check consistency with current permissions for anonymous users
                                 // and current permissions for registered users
                                 if ($user_set_anonymous_to_update || $anonymous_is_already_set_to_update) {
                                     $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_update', array($name_of_ugroup));
                                             
                                 } else if ($user_set_registered_to_update || $registered_is_already_set_to_update) {
                                     $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_regis_update', array($name_of_ugroup));
                                             
                                 } else {
                                     permission_add_ugroup($group_id, 'TRACKER_FIELD_UPDATE', $fake_object_id, $ugroup_id);
                                 }
                             } else if(isset($stored_ugroups_permissions[$field_id]['ugroups'][$ugroup_id]['permissions']['TRACKER_FIELD_UPDATE'])
                                       && isset($ugroup_permissions['others']) 
                                       && $ugroup_permissions['others'] !== "1") {
                                 //If we don't have already clear the permissions
                                 if (!$user_set_anonymous_to_update && !$user_set_registered_to_update) {
                                     permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_UPDATE', $ugroup_id, $fake_object_id);
                                 }
                             }

                             //READ Permission
                             //-----------------
                             if (!isset($stored_ugroups_permissions[$field_id]['ugroups'][$ugroup_id]['permissions']['TRACKER_FIELD_READ'])
                                 && isset($ugroup_permissions['others']) 
                                 && $ugroup_permissions['others'] === "0") {
                                 //if the ugroup is not anonymous and not registered, we have to:
                                 // check consistency with current permissions for anonymous users
                                 // and current permissions for registered users
                                 if ($user_set_anonymous_to_read || $anonymous_is_already_set_to_read) {
                                     $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_read', array($name_of_ugroup));
                                             
                                 } else if ($user_set_registered_to_read || $registered_is_already_set_to_read) {
                                     $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_regis_read', array($name_of_ugroup));
                                             
                                 } else if ($user_set_anonymous_to_update || $anonymous_is_already_set_to_update) {
                                     $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_update', array($name_of_ugroup));
                                             
                                 } else if ($user_set_registered_to_update || $registered_is_already_set_to_update) {
                                     $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_regis_update', array($name_of_ugroup));
                                             
                                 } else {
                                     permission_add_ugroup($group_id, 'TRACKER_FIELD_READ', $fake_object_id, $ugroup_id);
                                 }
                             } else if(isset($stored_ugroups_permissions[$field_id]['ugroups'][$ugroup_id]['permissions']['TRACKER_FIELD_READ'])
                                       && isset($ugroup_permissions['others']) 
                                       && $ugroup_permissions['others'] !== "0") {
                                 //If we don't have already clear the permissions
                                 if (!$user_set_anonymous_to_read && !$user_set_registered_to_read) {
                                     permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_READ', $ugroup_id, $fake_object_id);
                                 }
                             }
                         }
                     }
                 }
             }
         } else if($reset) {
             //The user want to clear permissions
             $fields = $art_field_fact->getAllUsedFields();
             foreach($fields as $field) {
                 permission_clear_all_fields_tracker($group_id, $atid, $field->getID());
             }
         }
     }
     //display
     $ath->adminHeader(array('title'=>$Language->getText('tracker_admin_field_usage','tracker_admin').$Language->getText('tracker_admin_field_usage','usage_admin'),'help' => 'TrackerAdministration.html#TrackerPermissionsManagement')); //TODO
     $ugroups_permissions = permission_get_field_tracker_ugroups_permissions($group_id, $atid, $art_field_fact->getAllUsedFields(), true);
     $ath->displayPermissionsFieldsTracker($ugroups_permissions, $group_first, $selected_id);
     break;
 default:
     $ath->adminHeader(array('title'=>$Language->getText('tracker_admin_field_usage','tracker_admin').$Language->getText('tracker_admin_field_usage','usage_admin'),'help' => 'TrackerAdministration.html#TrackerPermissionsManagement')); //TODO
     $ath->displayPermissionsGeneralMenu();
     break;
 }

$ath->footer(array());
?>