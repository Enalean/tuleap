<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2011. All rights reserved
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
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('pre.php');

// Valid group id
$vGroupId = new Valid_GroupId();
$vGroupId->required();
if (!$request->valid($vGroupId)) {
    exit_error($Language->getText('project_admin_index', 'invalid_p'), $Language->getText('project_admin_index', 'p_not_found'));
}
$group_id = $request->get('group_id');


session_require(array('group'=>$group_id, 'admin_flags'=>'A'));

//
//  get the Project
//
$pm    = ProjectManager::instance();
$project = $pm->getProject($group_id);
if (!$project || !is_object($project) || $project->isError()) {
    exit_no_group();
}

//if the project isn't active, require you to be a member of the super-admin group
if ($project->getStatus() != 'A') {
    session_require(array('group'=>1));
}

$vFunc = new Valid_WhiteList('func', array('member_req_notif_group', 'member_req_notif_message'));
$vFunc->required();
if ($request->isPost() && $request->valid($vFunc)) {
    /*
      updating the database
    */
    switch ($request->get('func')) {
    case 'member_req_notif_group':
        $vUGroups = new Valid_UInt('ugroups');
        $vUGroups->required();
        if ($request->validArray($vUGroups)) {
            $ugroups = $request->get('ugroups');
            // Remove ugroups that are empty or contain no project admins
            $result = ugroup_filter_ugroups_by_project_admin($group_id, $ugroups);
            $nonAdmins    = $result['non_admins'];
            $validUgroups = $result['ugroups'];
            if (empty($validUgroups)) {
                // If no valid ugroups the default one is project admins ugroup
                $validUgroups = array($GLOBALS['UGROUP_PROJECT_ADMIN']);
                $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_index', 'member_request_delegation_ugroups_all_invalid'));
            } else {
                // If some selected ugroups are not valid display them to the user.
                $diff = array_diff($ugroups, $validUgroups);
                if (!empty($diff)) {
                    foreach ($diff as $ugroupId) {
                        $deletedUgroups[] = ugroup_get_name_from_id($ugroupId);
                    }
                    $GLOBALS['Response']->addFeedback('warning', $Language->getText('project_admin_index', 'member_request_delegation_ugroups_some_invalid', implode(', ', $deletedUgroups)));
                }
                // Inform about the number of non admins in the selected ugroups
                // and indicate that they will not recieve any permission request mail.
                if ($nonAdmins > 0) {
                    $GLOBALS['Response']->addFeedback('warning', $Language->getText('project_admin_index', 'member_request_delegation_ugroups_non_admins', $nonAdmins));
                }
            }
            //to retreive the old marked ugroups
            $darUgroups = $pm->getMembershipRequestNotificationUGroup($group_id);
            if ($pm->setMembershipRequestNotificationUGroup($group_id, $validUgroups)) {
                if ($darUgroups && !$darUgroups->isError() && $darUgroups->rowCount() > 0) {
                    foreach ($darUgroups as $row) {
                        $oldUgroups[] = ugroup_get_name_from_id($row['ugroup_id']);
                    }
                } else {
                    $oldUgroups = array(ugroup_get_name_from_id($GLOBALS['UGROUP_PROJECT_ADMIN']));
                }
                foreach ($validUgroups as $ugroupId) {
                    $ugroupName = ugroup_get_name_from_id($ugroupId);
                    $newUgroups[] = $ugroupName;
                    if ($ugroupId == $GLOBALS['UGROUP_PROJECT_ADMIN']) {
                        $addedUgroups[] = util_translate_name_ugroup('project_admin');
                    } else {
                        $addedUgroups[] = $ugroupName;
                    }
                }
                //update group history
                group_add_history('membership_request_updated', implode(',', $oldUgroups).' :: '.implode(',', $newUgroups), $group_id);
                $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_index', 'member_request_delegation_ugroups_msg', implode(', ', $addedUgroups)));
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_index', 'member_request_delegation_ugroups_error'));
        }
        break;

    case 'member_req_notif_message':
        $updatedMessage = true;
        // Validate the text
        $vMessage = new Valid_Text('text');
        $vMessage->required();
        $message = trim($request->get('text'));
        $dar = $pm->getMessageToRequesterForAccessProject($group_id);
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->current();
            if (!strcmp($row['msg_to_requester'], $message)) {
                $updatedMessage = false;
            }
        }
        if ($request->valid($vMessage) & !empty($message) & $updatedMessage) {
            if ($pm->setMessageToRequesterForAccessProject($group_id, $message)) {
                $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_index', 'member_request_delegation_msg_info'));
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_index', 'member_request_delegation_msg_error'));
        }
        break;
    }
}

/*
    Delegate notifications
*/

project_admin_header(array('title'=>$Language->getText('project_admin_ugroup', 'permission_request'), 'group'=>$group_id));

echo '
<h2>'.$Language->getText('project_admin_index', 'member_request_delegation_title').'</h2>';

echo '<table>';
echo '<tr><td colspan="2"><p>';
if (!$project->isPublic()) {
    echo $Language->getText('project_admin_index', 'member_request_delegation_desc_private_group');
} else {
    echo $Language->getText('project_admin_index', 'member_request_delegation_desc_restricted_user');
}
echo '</p></td></tr>';

//Retrieve the saved ugroups for notification from DB
$dar = $pm->getMembershipRequestNotificationUGroup($group_id);
if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
    foreach ($dar as $row) {
        $selectedUgroup[] = $row['ugroup_id'];
    }
} else {
        $selectedUgroup = array($GLOBALS['UGROUP_PROJECT_ADMIN']);
}

 
$ugroupList = array(array('value' => $GLOBALS['UGROUP_PROJECT_ADMIN'], 'text' => util_translate_name_ugroup('project_admin')));
$res = ugroup_db_get_existing_ugroups($group_id);
while ($row = db_fetch_array($res)) {
    $ugroupList[] = array('value' => $row['ugroup_id'], 'text' => $row['name']);
}
echo '<tr><td colspan="2" style="text-align: center;">';
echo '<form method="post" action="permission_request.php">';
echo '<input type="hidden" name="func" value="member_req_notif_group" />';
echo '<input type="hidden" name="group_id" value="'. $group_id .'">';
echo html_build_multiple_select_box_from_array($ugroupList, "ugroups[]", $selectedUgroup, 8, false, '', false, '', false, '', false);
echo '<br />';
echo '<input type="submit" name="submit" value="'.$Language->getText('global', 'btn_update').'" />';
echo '</form>';
echo '</td></tr>';

echo '<tr><td colspan="2"><p>';
echo $Language->getText('project_admin_index', 'member_request_delegation_msg_desc');
echo '</p></td></tr>';

$message = $GLOBALS['Language']->getText('project_admin_index', 'member_request_delegation_msg_to_requester');
$dar = $pm->getMessageToRequesterForAccessProject($group_id);
if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
    $row = $dar->current();
    if ($row['msg_to_requester'] != "member_request_delegation_msg_to_requester" ) {
        $message = $row['msg_to_requester'];
    }
}
echo '<tr><td colspan="2" style="text-align: center;">';
echo '<form method="post" action="permission_request.php">
          <textarea wrap="virtual" rows="5" cols="70" name="text">'.$message.'</textarea></p>
          <input type="hidden" name="func" value="member_req_notif_message">
          <input type="hidden" name="group_id" value="' .$group_id. '">
          <br><input name="submit" type="submit" value="'.$GLOBALS['Language']->getText('global', 'btn_update').'"/></br>
     </form>';

echo '</td></tr>';
echo '</table>';

$em = EventManager::instance();
$em->processEvent('permission_request_information', array('group_id' => $group_id));

project_admin_footer(array());
?>