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


//We check variables submitted by user
if (!isset($_REQUEST['perm_type']) || 
    !($_REQUEST['perm_type'] === 'tracker' || $_REQUEST['perm_type'] === 'fields')
    ) {
    $perm_type = '';
 }else {
    $perm_type = $_REQUEST['perm_type'];
 }


switch ($perm_type) {
 case 'tracker':
     $ath->displayPermissionsTracker();
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