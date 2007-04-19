<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id: field_usage.php 1387 2005-03-08 16:41:17Z guerin $
//
//
//  Written for CodeX by Stephane Bouhet
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

// Check if this tracker is valid (not deleted)
if ( !$ath->isValid() ) {
	exit_error($Language->getText('global','error'),$Language->getText('tracker_add','invalid'));
}

$ath->adminHeader(array('title'=>$Language->getText('tracker_admin_field_usage','tracker_admin').$Language->getText('tracker_admin_field_usage','usage_admin'),'help' => 'TrackerAdministration.html#TrackerFieldUsageManagement'));

echo '<H2>'.$Language->getText('tracker_import_admin','tracker').' \'<a href="/tracker/admin/?group_id='.$group_id.'&atid='.$atid.'">'.$ath->getName().'</a>\' '.$Language->getText('tracker_admin_field_usage','usage_admin').'</H2>';
$ath->displayFieldUsageList();
$ath->displayFieldUsageForm();

$ath->footer(array());

?>
