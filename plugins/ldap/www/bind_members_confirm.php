<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
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

require_once 'pre.php';
require_once dirname(__FILE__).'/../include/LDAP_ProjectGroupManager.class.php';
require_once 'www/project/admin/project_admin_utils.php';

// Import very long user group may takes very long time.
ini_set('max_execution_time', 0);

$request = HTTPRequest::instance();

// Get group id
$vGroupId = new Valid_GroupId();
$vGroupId->required();

if (! $request->valid($vGroupId)) {
    $GLOBALS['Response']->send400JSONErrors("Group ID is missing");

    return;
}
$groupId = $request->get('group_id');

// Must be a project admin
$membership_delegation_dao = new \Tuleap\Project\Admin\MembershipDelegationDao();
$user                      = $request->getCurrentUser();
if (! $user->isAdmin($groupId) && ! $membership_delegation_dao->doesUserHasMembershipDelegation($user->getId(), $groupId)) {
    exit_error(
        $Language->getText('include_session', 'insufficient_g_access'),
        $Language->getText('include_session', 'no_perm_to_view')
    );
}

// Ensure LDAP plugin is active
$pluginManager = PluginManager::instance();
$ldapPlugin    = $pluginManager->getPluginByName('ldap');
if (!$ldapPlugin || !$pluginManager->isPluginAvailable($ldapPlugin)) {
    $GLOBALS['Response']->sendStatusCode(403);
    exit;
}

// Check if user have choosen the preserve members option.
$bind_option = LDAP_GroupManager::BIND_OPTION;
if ($request->get('preserve_members')) {
    $bind_option = LDAP_GroupManager::PRESERVE_MEMBERS_OPTION;
}

// Get LDAP group name
$vLdapGroup = new Valid_String('ldap_group');
$vLdapGroup->required();

if (! $request->valid($vLdapGroup)) {
    $GLOBALS['Response']->send400JSONErrors("Group ID is missing");
    exit;
}

$ldap_group_manager = $ldapPlugin->getLdapProjectGroupManager();

$ldap_group_manager->setId($groupId);
$ldap_group_manager->setGroupName($request->get('ldap_group'));

$user_manager = UserManager::instance();
$user_helper  = UserHelper::instance();

$to_remove = array();
foreach ($ldap_group_manager->getUsersToBeRemoved($bind_option) as $user_id) {
    $user = $user_manager->getUserById($user_id);
    $to_remove[] = array(
        'display_name' => $user_helper->getDisplayNameFromUser($user),
        'has_avatar'   => $user->hasAvatar(),
        'avatar_url'   => $user->getAvatarUrl()
    );
}

$to_add = array();
foreach ($ldap_group_manager->getUsersToBeAdded($bind_option) as $user_id) {
    $user = $user_manager->getUserById($user_id);
    $to_add[] = array(
        'display_name' => $user_helper->getDisplayNameFromUser($user),
        'has_avatar'   => $user->hasAvatar(),
        'avatar_url'   => $user->getAvatarUrl()
    );
}

$GLOBALS['Response']->sendJSON(
    array(
        'users_to_remove' => $to_remove,
        'users_to_add'    => $to_add,
        'nb_not_impacted' => count($ldap_group_manager->getUsersNotImpacted($bind_option)),
    )
);
