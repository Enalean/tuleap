<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2004-2011. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once __DIR__ . '/../../include/pre.php';

$request = HTTPRequest::instance();

// Valid group id
$vGroupId = new Valid_GroupId();
$vGroupId->required();
if (! $request->valid($vGroupId)) {
    exit_error($Language->getText('project_admin_index', 'invalid_p'), $Language->getText('project_admin_index', 'p_not_found'));
}
$group_id = $request->get('group_id');


session_require(['group' => $group_id, 'admin_flags' => 'A']);

//  get the Project
$pm      = ProjectManager::instance();
$project = $pm->getProject($group_id);
if (! $project || ! is_object($project) || $project->isError()) {
    exit_no_group();
}

//if the project isn't active, require you to be a member of the super-admin group
if ($project->getStatus() != 'A') {
    $request->checkUserIsSuperUser();
}

$vFunc = new Valid_WhiteList('func', ['member_req_notif_group', 'member_req_notif_message']);
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
                $result       = ugroup_filter_ugroups_by_project_admin($group_id, $ugroups);
                $nonAdmins    = $result['non_admins'];
                $validUgroups = $result['ugroups'];
                if (empty($validUgroups)) {
                    // If no valid ugroups the default one is project admins ugroup
                    $validUgroups = [$GLOBALS['UGROUP_PROJECT_ADMIN']];
                    $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_index', 'member_request_delegation_ugroups_all_invalid'));
                } else {
                    // If some selected ugroups are not valid display them to the user.
                    $diff = array_diff($ugroups, $validUgroups);
                    if (! empty($diff)) {
                        $deletedUgroups = [];
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
                    $oldUgroups = [];
                    if ($darUgroups && ! $darUgroups->isError() && $darUgroups->rowCount() > 0) {
                        foreach ($darUgroups as $row) {
                            $oldUgroups[] = ugroup_get_name_from_id($row['ugroup_id']);
                        }
                    } else {
                        $oldUgroups = [ugroup_get_name_from_id($GLOBALS['UGROUP_PROJECT_ADMIN'])];
                    }
                    foreach ($validUgroups as $ugroupId) {
                        $ugroupName   = ugroup_get_name_from_id($ugroupId);
                        $newUgroups   = [$ugroupName];
                        $addedUgroups = [];
                        if ($ugroupId == $GLOBALS['UGROUP_PROJECT_ADMIN']) {
                            $addedUgroups[] = \Tuleap\User\UserGroup\NameTranslator::getUserGroupDisplayKey((string) 'project_admin');
                        } else {
                            $addedUgroups[] = $ugroupName;
                        }
                    }
                    //update group history
                    (new ProjectHistoryDao())->groupAddHistory('membership_request_updated', implode(',', $oldUgroups) . ' :: ' . implode(',', $newUgroups), $group_id);
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
            $dar     = $pm->getMessageToRequesterForAccessProject($group_id);
            if ($dar && ! $dar->isError() && $dar->rowCount() == 1) {
                $row = $dar->current();
                if (! strcmp($row['msg_to_requester'], $message)) {
                    $updatedMessage = false;
                }
            }
            if ($request->valid($vMessage) & ! empty($message) & $updatedMessage) {
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

project_admin_header(
    $Language->getText('project_admin_ugroup', 'permission_request'),
    \Tuleap\Project\Admin\Navigation\NavigationPermissionsDropdownPresenterBuilder::PERMISSIONS_ENTRY_SHORTNAME
);

echo '<div class="tlp-framed-horizontally">
<h2>' . $Language->getText('project_admin_index', 'member_request_delegation_title') . '</h2>';

echo '<p>';
if (! $project->isPublic()) {
    echo $Language->getOverridableText('project_admin_index', 'member_request_delegation_desc_private_group');
} else {
    echo $Language->getOverridableText('project_admin_index', 'member_request_delegation_desc_restricted_user');
}
echo '</p>';

$title = gettext('Users groups');
echo <<<EOS
<section class="tlp-pane">
    <div class="tlp-pane-container">
        <div class="tlp-pane-header">
            <h1 class="tlp-pane-title">
                <i class="tlp-pane-title-icon fa-solid fa-users" aria-hidden="true"></i>
                $title
            </h1>
        </div>
        <section class="tlp-pane-section">
EOS;

$notices = [];
EventManager::instance()->processEvent(
    'permission_request_information',
    ['group_id' => $group_id, 'notices' => &$notices]
);
if ($notices) {
    echo '<div class="tlp-alert-info">';
    echo implode('<br>', $notices);
    echo '</div>';
}


//Retrieve the saved ugroups for notification from DB
$dar = $pm->getMembershipRequestNotificationUGroup($group_id);
if ($dar && ! $dar->isError() && $dar->rowCount() > 0) {
    $selectedUgroup = [];
    foreach ($dar as $row) {
        $selectedUgroup[] = $row['ugroup_id'];
    }
} else {
        $selectedUgroup = [$GLOBALS['UGROUP_PROJECT_ADMIN']];
}


$ugroupList = [['value' => $GLOBALS['UGROUP_PROJECT_ADMIN'], 'text' => \Tuleap\User\UserGroup\NameTranslator::getUserGroupDisplayKey((string) 'project_admin')]];
/** @psalm-suppress DeprecatedFunction */
$res = ugroup_db_get_existing_ugroups($group_id);
while ($row = db_fetch_array($res)) {
    $ugroupList[] = ['value' => $row['ugroup_id'], 'text' => $row['name']];
}

$purifier = Codendi_HTMLPurifier::instance();

echo '<form method="post" action="permission_request.php">';
echo '<input type="hidden" name="func" value="member_req_notif_group" />';
echo '<input type="hidden" name="group_id" value="' . $purifier->purify($group_id) . '">';

echo '<div class="tlp-form-element">';
echo '<label class="tlp-label" for="select-user-group">';
echo $Language->getText('project_admin_index', 'member_request_delegation_desc_selected_group');
echo '</label>';
echo '<select multiple class="tlp-select tlp-select-adjusted" id="select-user-group">';
foreach ($ugroupList as $ugroup) {
    $selected = in_array($ugroup['value'], $selectedUgroup, true) ? 'selected' : '';
    echo '<option value="' . $purifier->purify($ugroup['value']) . '" ' . $selected . '>' . $purifier->purify($ugroup['text']) . '</option>';
}
echo '</select>';
echo '</div>';

echo '<div class="tlp-pane-section-submit">';
echo '<input type="submit" name="submit" class="tlp-button-primary" value="' . $Language->getText('global', 'btn_update') . '" />';
echo '</div>';
echo '</form>';

echo <<<EOS
        </section>
    </div>
</section>
EOS;

$title = gettext('Default message');
echo <<<EOS
<section class="tlp-pane">
    <div class="tlp-pane-container">
        <div class="tlp-pane-header">
            <h1 class="tlp-pane-title">
                <i class="tlp-pane-title-icon fa-solid fa-message" aria-hidden="true"></i>
                $title
            </h1>
        </div>
        <section class="tlp-pane-section">
EOS;
echo '<p>';
echo $Language->getText('project_admin_index', 'member_request_delegation_msg_desc');
echo '</p>';

$placeholder = $GLOBALS['Language']->getText('project_admin_index', 'member_request_delegation_msg_to_requester');

$message = '';
$dar     = $pm->getMessageToRequesterForAccessProject($group_id);
if ($dar && ! $dar->isError() && $dar->rowCount() == 1) {
    $row = $dar->current();
    if ($row['msg_to_requester'] !== 'member_request_delegation_msg_to_requester') {
        $message = $row['msg_to_requester'];
    }
}
echo '<form method="post" action="permission_request.php">
        <div class="tlp-form-element">
            <label class="tlp-label" for="default-message">' . gettext('Default message') . '</label>
            <textarea wrap="virtual" id="default-message" class="tlp-textarea" rows="5" cols="70" name="text" placeholder="' . $purifier->purify($placeholder) . '">' . $purifier->purify($message) . '</textarea>
        </div>
          <input type="hidden" name="func" value="member_req_notif_message">
          <input type="hidden" name="group_id" value="' . $purifier->purify($group_id) . '">
          <div class="tlp-pane-section-submit">
            <input name="submit" class="tlp-button-primary" type="submit" value="' . $GLOBALS['Language']->getText('global', 'btn_update') . '"/>
          </div>
     </form>';

echo <<<EOS
        </section>
    </div>
</section>
EOS;

project_admin_footer([]);
