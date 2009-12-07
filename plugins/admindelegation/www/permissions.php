<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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

require 'pre.php';
require_once dirname(__FILE__).'/../include/AdminDelegation_UserServiceManager.class.php';

// First, check plugin availability
$pluginManager = PluginManager::instance();
$p = $pluginManager->getPluginByName('admindelegation');
if (!$p || !$pluginManager->isPluginAvailable($p)) {
    header('Location: '.get_server_url());
}

// Grant access only to site admin
$um = UserManager::instance();
if (!$um->getCurrentUser()->isSuperUser()) {
    $GLOBALS['Response']->redirect($p->getPluginPath().'/permissions.php');
}

$usm = new AdminDelegation_UserServiceManager();

if ($request->isPost()) {
    $vFunc = new Valid_WhiteList('func', array('grant_user_service', 'revoke_user', 'revoke_user_service'));
    $vFunc->required();
    if ($request->valid($vFunc)) {
        $func = $request->get('func');
    } else {
        $func = '';
    }

    switch ($func) {
        case 'grant_user_service':
            $vUser = new Valid_String('user_to_grant');
            $vUser->required();
            if ($request->valid($vUser)) {
                $user = $um->findUser($request->get('user_to_grant'));
            } else {
                $user = false;
            }

            $vService = new Valid_WhiteList('service', AdminDelegation_Service::getAllServices());
            $vService->required();
            if ($request->valid($vService)) {
                $service = $request->get('service');
            } else {
                $service = false;
            }

            if ($user && $service) {
                if ($usm->addUserService($user, $service)) {
                    $GLOBALS['Response']->addFeedback('info', 'Permission granted to user');
                } else {
                    $GLOBALS['Response']->addFeedback('error', 'Fail to grant permission to user');
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', 'Either bad user or bad service');
            }
            break;

        case 'revoke_user':
            $vUser = new Valid_UInt('users_to_revoke');
            $vUser->required();
            if ($request->validArray($vUser)) {
                foreach ($request->get('users_to_revoke') as $userId) {
                    $user = $um->getUserById($userId);
                    if ($user) {
                        $usm->removeUser($user);
                    } else {
                        $GLOBALS['Response']->addFeedback('error', 'Bad user');
                    }
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', 'Bad user');
            }
            break;

        default:
            $GLOBALS['Response']->addFeedback('error', 'Bad action');
            break;
    }
    $GLOBALS['Response']->redirect($p->getPluginPath().'/permissions.php');
}


$GLOBALS['HTML']->header(array('title' => 'Admin rights delegation / Permissions'));
echo '<h1>Admin rights delegation / Permissions</h1>';

echo '<h2>Grant user</h2>';

echo '<form method="post" action="?">';
echo 'Grant ';
echo html_build_select_box_from_arrays(AdminDelegation_Service::getAllServices(), AdminDelegation_Service::getAllLabels(), 'service', 100, true);
echo ' to user ';
echo '<input type="hidden" name="func" value="grant_user_service" />';
echo '<input type="text" name="user_to_grant" value="Type user name (autcompletion)" id="granted_user" />';
echo '&nbsp;';
echo '<input type="submit" value="'.$GLOBALS['Language']->getText('global', 'btn_apply').'"/>';
echo '</form>';
$js = "new UserAutoCompleter('granted_user', '".util_get_dir_image_theme()."', false);";
$GLOBALS['HTML']->includeFooterJavascriptSnippet($js);

$uh = UserHelper::instance();

echo '<form method="post" action="?">';
echo '<input type="hidden" name="func" value="revoke_user" />';
echo '<h2>Granted users</h2>';
echo '<table border="1">';
echo '<thead>';
echo '<tr>';
echo '<th>&nbsp;</th>';
echo '<th>User</th>';
echo '<th>Service</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';
$prev = -1;
foreach ($usm->getGrantedUsers() as $row) {
    if ($row['user_id'] != $prev) {
        if ($prev != -1) {
            echo '</td></tr>';
        }
        echo '<tr>';
        echo '<td><input type="checkbox" name="users_to_revoke[]" value="'.$row['user_id'].'" /></td>';
        echo '<td>'.$uh->getDisplayNameFromUserId($row['user_id']).'</td>';
        echo '<td>'.AdminDelegation_Service::getLabel($row['service_id']);
    } else {
        echo ', '.AdminDelegation_Service::getLabel($row['service_id']);
    }
    $prev = $row['user_id'];
}
echo '</tbody>';
echo '</table>';
echo '<input type="submit" value="'.$GLOBALS['Language']->getText('global', 'btn_delete').'"/>';
echo '</form>';

//echo '<h2>Granted users per permission</h2>';

$GLOBALS['HTML']->footer(array());

?>