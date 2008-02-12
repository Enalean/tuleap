<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $$
//
//
//  Written for CodeX by Marc Nazarian
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

$ath->adminHeader(array('title'=>$Language->getText('tracker_admin_fieldset','tracker_admin').$Language->getText('tracker_admin_fieldset','fieldset_admin'),'help' => 'TrackerAdministration.html#TrackerFieldSetsManagement'));

$hp = Codex_HTMLPurifier::instance();
echo '<H2>'.$Language->getText('tracker_import_admin','tracker').' \'<a href="/tracker/admin/?group_id='.(int)$group_id.'&atid='.(int)$atid.'">'. $hp->purify($ath->getName(), CODEX_PURIFIER_BASIC) .'</a>\' '.$Language->getText('tracker_admin_fieldset','fieldset_admin').'</H2>';
$ath->displayFieldSetList();
$ath->displayFieldSetCreateForm();

$ath->footer(array());

?>
