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
if(!$request->valid($vGroupId)) {
    exit_error($Language->getText('project_admin_index','invalid_p'), $Language->getText('project_admin_index','p_not_found'));
}
$group_id = $request->get('group_id');


session_require(array('group'=>$group_id,'admin_flags'=>'A'));

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
            $validUgroups = ugroup_validate_admin_ugroups($group_id, $ugroups);
            if ($validUgroups) {
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
                        $newUgroups[] = ugroup_get_name_from_id($ugroupId);
                    }
                    //update group history
                    group_add_history('membership_request_updated', implode(',', $oldUgroups).' :: '.implode(',', $newUgroups), $group_id);
                    $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_index', 'member_request_delegation_ugroups_msg'));
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_index', 'member_request_delegation_ugroups_error'));
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_index', 'member_request_delegation_ugroups_error'));
        }
        break;

    case 'member_req_notif_message':
        $vMessage = new Valid_Text('text');
        $vMessage->required();
        if ($request->valid($vMessage)) {
            $message = trim($request->get('text'));
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

project_admin_header(array('title'=>$Language->getText('project_admin_ugroup','permission_request'),'group'=>$group_id));

echo '
<h2>'.$Language->getText('project_admin_index','member_request_delegation_title').'</h2>';

echo '<tr><td colspan="2">';
echo $Language->getText('project_admin_index','member_request_delegation_desc');
echo '</td></tr>';

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

echo '<tr><td colspan="2">';
echo $Language->getText('project_admin_index','member_request_delegation_msg_desc');
echo '</td></tr>';

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

$em = EventManager::instance();
$em->processEvent('permission_request_information', array('group_id' => $group_id));

project_admin_footer(array());
?>