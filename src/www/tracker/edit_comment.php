<?php
/*
* Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
*
* Originally written by Mohamed CHAARI, 2007. STMicroelectronics.
*
* This file is a part of Codendi.
*
* Codendi is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* Codendi is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Codendi; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


// Check if this tracker is valid (not deleted)
if (! $ath->isValid()) {
    exit_error($Language->getText('global', 'error'), $Language->getText('tracker_add', 'invalid'));
}
if (! $ah->userCanEditFollowupComment($request->get('artifact_history_id'))) {
    exit_permission_denied();
}

$group    = $ath->getGroup();
$group_id = $ath->getGroupID();
$params   = ['title' => $group->getPublicName() . ' ' . $ath->getName() . ' #' . $ah->getID() . ' - \'' . $ah->getSummary() . '\'',
    'pagename' => 'tracker',
    'atid' => $ath->getID(),
];
// Display header page
$ath->header($params);
echo '<div id="tracker_toolbar_clear"></div>';

$ah->displayEditFollowupComment($request->get('artifact_history_id'));

$ath->footer($params);
