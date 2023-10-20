<?php
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//
//
//
//
//  Written for Codendi by Stephane Bouhet

if (! user_isloggedin()) {
    exit_not_logged_in();
    return;
}

if (! user_ismember($group_id, 'A')) {
    exit_permission_denied();
    return;
}

$ath->adminTrackersHeader(['title' => $Language->getText('tracker_admin_trackers', 'all_admin'),
]);
echo $ath->displayAdminTrackers();
$ath->footer([]);
