<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

require_once('user.php');


function groups_to_soap($groups)
{
    $return = array();
    foreach ($groups as $group_id => $group) {
        if (!$group || $group->isError()) {
            //skip if error
        } else {
            $return[] = group_to_soap($group);
        }
    }
    return $return;
}

/**
 * Check if the user can access the project $group,
 * regarding the restricted access
 *
 * @param Object{Group} $group the Group object
 * @return bool true if the current session user has access to this project, false otherwise
 */
function checkRestrictedAccess($group)
{
    if (ForgeConfig::areRestrictedUsersAllowed()) {
        if ($group) {
            $user = UserManager::instance()->getCurrentUser();
            if ($user) {
                if ($user->isRestricted()) {
                    return $group->userIsMember();
                } else {
                    return true;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    } else {
        return true;
    }
}

/**
 * Returns true is the current user is a member of the given group
 */
function checkGroupMemberAccess($group)
{
    if ($group) {
        $user = UserManager::instance()->getCurrentUser();
        if ($user) {
            return $group->userIsMember();
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function ugroups_to_soap($ugroups)
{
    $return = array();

    foreach ($ugroups as $ugroup) {
        $ugroup_id = $ugroup['ugroup_id'];
        if (!isset($return[$ugroup_id])) {
            $return[$ugroup_id]['ugroup_id'] = $ugroup_id;
            $return[$ugroup_id]['name'] = $ugroup['name'];
            $return[$ugroup_id]['members'] = array();
        }

        if ($ugroup['user_id']) {
            $return[$ugroup_id]['members'][] = array('user_id' => $ugroup['user_id'],
                                                     'user_name' => $ugroup['user_name']);
        }
    }

    return $return;
}

/**
 * User can get information about other users if they are active, retricted or suspended
 *
 * Suspended is needed to be coherent with the GUI where the suspended users are displayed
 * like active users to people (otherwise it breaks Mylyn on trackers due to workflow manager)
 *
 * @param string $identifier
 * @return array
 */
function user_to_soap($identifier, ?PFUser $user = null, PFUser $current_user)
{
    if ($user !== null && ($user->isActive() || $user->isRestricted() || $user->isSuspended())) {
        if ($current_user->canSee($user)) {
            return array(
                'identifier' => $identifier,
                'username'   => $user->getUserName(),
                'id'         => $user->getId(),
                'real_name'  => $user->getRealName(),
                'email'      => $user->getEmail(),
                'ldap_id'    => $user->getLdapId()
            );
        }
    }
}
