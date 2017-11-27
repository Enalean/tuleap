<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

$plugin_manager = PluginManager::instance();
/** @var ldapPlugin $ldap_plugin */
$ldap_plugin = $plugin_manager->getPluginByName('ldap');
if (! $ldap_plugin || ! $plugin_manager->isPluginAvailable($ldap_plugin)) {
    $GLOBALS['Response']->sendStatusCode(403);
    exit;
}

$request   = HTTPRequest::instance();
$ugroup_id = $request->getValidated('ugroup_id', 'uint', 0);
if (! $ugroup_id) {
    $GLOBALS['Response']->send400JSONErrors('The ugroup ID is missing');
    exit;
}
$ugroup_manager = new UGroupManager();
$ugroup = $ugroup_manager->getById($ugroup_id);

session_require(array('group' => $ugroup->getProjectId(), 'admin_flags' => 'A'));

$bind_option = LDAP_GroupManager::BIND_OPTION;
if ($request->get('preserve_members')) {
    $bind_option = LDAP_GroupManager::PRESERVE_MEMBERS_OPTION;
}
$bind_with_ugroup = $request->get('bind_with_group');

$user_group_manager = $ldap_plugin->getLdapUserGroupManager();
$user_group_manager->setGroupName($bind_with_ugroup);
$user_group_manager->setId($ugroup->getId());
$user_group_manager->setProjectId($ugroup->getProjectId());

if (! $user_group_manager->getGroupDn()) {
    $GLOBALS['Response']->send400JSONErrors(
        $GLOBALS['Language']->getText('project_ugroup_binding', 'ldap_group_error', $bind_with_ugroup)
    );
    exit;
}

$user_manager = UserManager::instance();
$user_helper  = UserHelper::instance();

$to_remove = array();
foreach ($user_group_manager->getUsersToBeRemoved($bind_option) as $user_id) {
    $user = $user_manager->getUserById($user_id);
    $to_remove[] = array(
        'display_name' => $user_helper->getDisplayNameFromUser($user),
        'has_avatar'   => $user->hasAvatar(),
        'avatar_url'   => $user->getAvatarUrl()
    );
}

$to_add = array();
foreach ($user_group_manager->getUsersToBeAdded($bind_option) as $user_id) {
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
        'nb_not_impacted' => count($user_group_manager->getUsersNotImpacted($bind_option)),
    )
);
