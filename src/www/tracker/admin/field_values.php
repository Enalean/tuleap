<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// 
//
//
//  Written for CodeX by Stephane Bouhet
//


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

$ath->adminHeader(array('title'=>$Language->getText('tracker_admin_field_usage','tracker_admin').$Language->getText('tracker_admin_field_values_details','values_admin'),'help' => 'TrackerAdministration.html#TrackerFieldValuesManagement'));

$hp = Codex_HTMLPurifier::instance();
echo '<H2>'.$Language->getText('tracker_import_admin','tracker').' \'<a href="/tracker/admin/?group_id='.(int)$group_id.'&atid='.(int)$atid.'">'. $hp->purify(SimpleSanitizer::unsanitize($ath->getName()), CODEX_PURIFIER_CONVERT_HTML) .'</a>\' '.$Language->getText('tracker_admin_field_values_details','values_admin').'</H2>';
$ath->displayFieldValuesEditList();

$ath->footer(array());

?>
