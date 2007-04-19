<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id: admin_trackers.php 1387 2005-03-08 16:41:17Z guerin $
//
//
//  Written for CodeX by Stephane Bouhet
//

$Language->loadLanguageMsg('tracker/tracker');

if ( !user_isloggedin() ) {
	exit_not_logged_in();
	return;
}

if ( !user_ismember($group_id,'A') ) {
	exit_permission_denied();
	return;
}

$ath->adminTrackersHeader(array('title'=>$Language->getText('tracker_admin_trackers','all_admin'),'help' => 'TrackerAdministration.html'));
echo $ath->displayAdminTrackers();
$ath->footer(array());

?>
