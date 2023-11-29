<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * Originally written by Nicolas Guerin 2004, Codendi Team, Xerox
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

use Tuleap\Project\Admin\ProjectUGroup\CannotCreateUGroupException;
use Tuleap\Request\RestrictedUsersAreHandledByPluginEvent;

require_once __DIR__ . '/../../include/utils.php';
require_once __DIR__ . '/project_admin_utils.php';

// Predefined ugroups. Should be consistent with DB (table 'ugroup')
/* @deprecated */
$GLOBALS['UGROUP_NONE']               = ProjectUGroup::NONE;
$GLOBALS['UGROUP_ANONYMOUS']          = ProjectUGroup::ANONYMOUS;
$GLOBALS['UGROUP_REGISTERED']         = ProjectUGroup::REGISTERED;
$GLOBALS['UGROUP_AUTHENTICATED']      = ProjectUGroup::AUTHENTICATED;
$GLOBALS['UGROUP_PROJECT_MEMBERS']    = ProjectUGroup::PROJECT_MEMBERS;
$GLOBALS['UGROUP_PROJECT_ADMIN']      = ProjectUGroup::PROJECT_ADMIN;
$GLOBALS['UGROUP_FILE_MANAGER_ADMIN'] = ProjectUGroup::FILE_MANAGER_ADMIN;
$GLOBALS['UGROUP_WIKI_ADMIN']         = ProjectUGroup::WIKI_ADMIN;
$GLOBALS['UGROUP_TRACKER_ADMIN']      = ProjectUGroup::TRACKER_ADMIN;
$GLOBALS['UGROUPS']                   = [
    'UGROUP_NONE'               => $GLOBALS['UGROUP_NONE'],
    'UGROUP_ANONYMOUS'          => $GLOBALS['UGROUP_ANONYMOUS'],
    'UGROUP_REGISTERED'         => $GLOBALS['UGROUP_REGISTERED'],
    'UGROUP_AUTHENTICATED'      => $GLOBALS['UGROUP_AUTHENTICATED'],
    'UGROUP_PROJECT_MEMBERS'    => $GLOBALS['UGROUP_PROJECT_MEMBERS'],
    'UGROUP_PROJECT_ADMIN'      => $GLOBALS['UGROUP_PROJECT_ADMIN'],
    'UGROUP_FILE_MANAGER_ADMIN' => $GLOBALS['UGROUP_FILE_MANAGER_ADMIN'],
    'UGROUP_WIKI_ADMIN'         => $GLOBALS['UGROUP_WIKI_ADMIN'],
    'UGROUP_TRACKER_ADMIN'      => $GLOBALS['UGROUP_TRACKER_ADMIN'],
];
/*
*      anonymous
*          ^
*          |
*      registered
*          ^
*          |
*     +----+-----+
*     |          |
*  statics    members
*                ^
*                |
*         +------+- - - -   -   -
*         |
*    tracker_tech
*/
function ugroup_get_parent($ugroup_id)
{
    if ($ugroup_id == $GLOBALS['UGROUP_NONE'] || $ugroup_id == $GLOBALS['UGROUP_ANONYMOUS']) {
        $parent_id = false;
    } elseif ($ugroup_id == $GLOBALS['UGROUP_REGISTERED']) {
        $parent_id = $GLOBALS['UGROUP_ANONYMOUS'];
    } elseif ($ugroup_id == $GLOBALS['UGROUP_PROJECT_MEMBERS'] || $ugroup_id > 100) {
        $parent_id = $GLOBALS['UGROUP_REGISTERED'];
    } else {
        $parent_id = $GLOBALS['UGROUP_PROJECT_MEMBERS'];
    }
    return $parent_id;
}

// Return members (user_id + user_name according to user preferences) of given user group
// * $keyword is used to filter the users.
function ugroup_db_get_members(
    $ugroup_id,
    $with_display_preferences = false,
    $keyword = null,
    array $user_ids = [],
) {
    $data_access = CodendiDataAccess::instance();

    $sqlname  = "user.user_name AS full_name";
    $sqlorder = "user.user_name";
    if ($with_display_preferences) {
        $uh       = UserHelper::instance();
        $sqlname  = $uh->getDisplayNameSQLQuery();
        $sqlorder = $uh->getDisplayNameSQLOrder();
    }

    $having_keyword = '';
    if ($keyword) {
        $keyword        = $data_access->quoteLikeValueSurround($keyword);
        $having_keyword = " AND full_name LIKE $keyword ";
    }

    $user_ids_sql = '';
    if ($user_ids) {
        $user_ids_sql = ' AND user.user_id IN (' . $data_access->escapeIntImplode($user_ids) . ')';
    }

    $ugroup_id = $data_access->escapeInt((int) $ugroup_id);
    $sql       = "(SELECT user.user_id, $sqlname, user.realname, user.user_name, user.email, user.status
            FROM ugroup_user, user
            WHERE user.user_id = ugroup_user.user_id
              AND ugroup_user.ugroup_id = $ugroup_id
              $user_ids_sql
              $having_keyword
            ORDER BY $sqlorder)";
    return $sql;
}

/**
 * Return name and id (as DB result) of all ugroups belonging to a specific project.
 *
 * @param int $group_id Id of the project
 * @param Array   $predefined List of predefined ugroup id
 *
 * @deprecated Use UGroupManager::getExistingUgroups() instead
 *
 * @return DB result set
 */
function ugroup_db_get_existing_ugroups($group_id, $predefined = null)
{
    $_extra = '';
    if ($predefined !== null && is_array($predefined)) {
        $_extra = ' OR ugroup_id IN (' . db_ei_implode($predefined) . ')';
    }
    $sql = "SELECT ugroup_id, name FROM ugroup WHERE group_id=" . db_ei($group_id) . " " . $_extra . " ORDER BY name";
    return db_query($sql);
}

/**
 * Returns a list of ugroups for the given group, with their associated members
 */
function ugroup_get_ugroups_with_members($group_id)
{
    $sql = "SELECT ugroup.ugroup_id, ugroup.name, user.user_id, user.user_name FROM ugroup " .
    "NATURAL LEFT JOIN ugroup_user " .
    "NATURAL LEFT JOIN user " .
    "WHERE ugroup.group_id=" . db_ei($group_id) .
    " ORDER BY ugroup.name";

    $return = [];

    $res = db_query($sql);
    while ($data = db_fetch_array($res)) {
        $return[] = $data;
    }

    return $return;
}

// Return DB ugroup from ugroup_id
function ugroup_db_get_ugroup($ugroup_id)
{
    $sql = "SELECT * FROM ugroup WHERE ugroup_id=" . db_ei($ugroup_id);
    return db_query($sql);
}


function ugroup_db_list_all_ugroups_for_user($group_id, $user_id)
{
    $sql = "SELECT ugroup.ugroup_id AS ugroup_id,ugroup.name AS name FROM ugroup, ugroup_user
          WHERE ugroup_user.user_id=" . db_ei($user_id) . " AND ugroup.group_id=" . db_ei($group_id) . " AND ugroup_user.ugroup_id=ugroup.ugroup_id";
    return db_query($sql);
}


/** Return array of ugroup_id for all user-defined ugoups that user is part of
 * and having tracker-related permissions on the $group_artifact_id tracker */
function ugroup_db_list_tracker_ugroups_for_user($group_id, $group_artifact_id, $user_id)
{
    $data_access       = CodendiDataAccess::instance();
    $group_artifact_id = $data_access->quoteLikeValueSuffix($group_artifact_id);
    $sql               = "SELECT distinct ug.ugroup_id FROM ugroup ug, ugroup_user ugu, permissions p " .
      "WHERE ugu.user_id=" . db_ei($user_id) .
      " AND ug.group_id=" . db_ei($group_id) .
      " AND ugu.ugroup_id=ug.ugroup_id " .
      " AND p.ugroup_id = ugu.ugroup_id " .
      " AND p.object_id LIKE $group_artifact_id" .
      " AND p.permission_type LIKE 'TRACKER%'";

    return util_result_column_to_array(db_query($sql));
}

/** Return array of ugroup_id for all dynamic ugoups like
 * (anonymous_user, registered_user, project_member,
 * project_admins, tracker_admins) that user is part of */
function ugroup_db_list_dynamic_ugroups_for_user($group_id, $instances, $user_id)
{
    $user = UserManager::instance()->getUserById($user_id);

    if ($user->isAnonymous()) {
        return [$GLOBALS['UGROUP_ANONYMOUS']];
    }

    $res = [$GLOBALS['UGROUP_ANONYMOUS'], $GLOBALS['UGROUP_REGISTERED']];

    if (ForgeConfig::areRestrictedUsersAllowed()) {
        $res[] = $GLOBALS['UGROUP_AUTHENTICATED'];
    }
    if ($user->isMember($group_id)) {
        $res[] = $GLOBALS['UGROUP_PROJECT_MEMBERS'];
    }
    if ($user->isMember($group_id, 'A')) {
        $res[] = $GLOBALS['UGROUP_PROJECT_ADMIN'];
    }
    if ($user->isMember($group_id, 'W2')) {
        $res[] = $GLOBALS['UGROUP_WIKI_ADMIN'];
    }
    if (is_int($instances)) {
        if ($user->isTrackerAdmin($group_id, $instances)) {
            $res[] = $GLOBALS['UGROUP_TRACKER_ADMIN'];
        }
    } elseif (is_array($instances)) {
        if (isset($instances['artifact_type'])) {
            if ($user->isTrackerAdmin($group_id, $instances['artifact_type'])) {
                $res[] = $GLOBALS['UGROUP_TRACKER_ADMIN'];
            }
        }
    }

    return $res;
}

/** Return user group name from ID */
function ugroup_get_name_from_id($ugroup_id)
{
    $res = ugroup_db_get_ugroup($ugroup_id);
    return db_result($res, 0, 'name');
}

/**
 * Check membership of the user to a specified ugroup
 * $group_id is necessary for automatic project groups like project member, release admin, etc.
 * $atid is necessary for trackers since the tracker admin role is different for each tracker.
 * @return bool true if user is member of the ugroup, false otherwise.
 */
function ugroup_user_is_member($user_id, $ugroup_id, $group_id, $atid = 0)
{
    $user = UserManager::instance()->getUserById($user_id);
    // Special Cases
    if ($ugroup_id == $GLOBALS['UGROUP_NONE']) {
        // Empty group
        return false;
    } elseif ($ugroup_id == $GLOBALS['UGROUP_ANONYMOUS']) {
        // Anonymous user
        return true;
    } elseif ($ugroup_id == $GLOBALS['UGROUP_AUTHENTICATED']) {
        // Registered user
        return $user_id != 0;
    } elseif ($ugroup_id == $GLOBALS['UGROUP_REGISTERED'] && ! ForgeConfig::areRestrictedUsersAllowed()) {
        // Registered user
        return $user_id != 0;
    } elseif ($ugroup_id == $GLOBALS['UGROUP_REGISTERED'] && ForgeConfig::areRestrictedUsersAllowed()) {
        if (! isset($_SERVER['REQUEST_URI'])) {
            $called_script_handles_restricted = false;
        } else {
            $event = new RestrictedUsersAreHandledByPluginEvent($_SERVER['REQUEST_URI']);
            EventManager::instance()->processEvent($event);
            $called_script_handles_restricted = $event->getPluginHandleRestricted();
        }

        // Non-restricted user or restricted member in service that doesn't yet handle restricted users independently
        return ! $user->isAnonymous() && (! $user->isRestricted() || ! $called_script_handles_restricted);
    } elseif ($ugroup_id == $GLOBALS['UGROUP_PROJECT_MEMBERS']) {
        // Project members
        if ($user->isMember($group_id)) {
            return true;
        }
    } elseif ($ugroup_id == $GLOBALS['UGROUP_WIKI_ADMIN']) {
        // Wiki admins
        if ($user->isMember($group_id, 'W2')) {
            return true;
        }
    } elseif ($ugroup_id == $GLOBALS['UGROUP_PROJECT_ADMIN']) {
        // Project admins
        if ($user->isMember($group_id, 'A')) {
            return true;
        }
    } elseif ($ugroup_id == $GLOBALS['UGROUP_TRACKER_ADMIN']) {
        // Tracker admins
        $pm    = ProjectManager::instance();
        $group = $pm->getProject($group_id);
        $at    = new ArtifactType($group, $atid);
        return $at->userIsAdmin($user_id);
    } else {
        // Normal ugroup
        $sql = "SELECT * from ugroup_user where ugroup_id='" . db_ei($ugroup_id) . "' and user_id='" . db_ei($user_id) . "'";
        $res = db_query($sql);
        if (db_numrows($res) > 0) {
            return true;
        }
    }
    return false;
}


/**
 * Check membership of the user to a specified ugroup
 * $group_id is necessary for automatic project groups like project member, release admin, etc.
 * $atid is necessary for trackers since the tracker admin role is different for each tracker.
 * $keword is used to filter the users
 */
function ugroup_db_get_dynamic_members(
    $ugroup_id,
    $atid,
    $group_id,
    $with_display_preferences = false,
    $show_suspended = false,
    bool $show_deleted = false,
    array $user_ids = [],
): ?string {
    $data_access = CodendiDataAccess::instance();

    $sqlname  = "user.user_name AS full_name";
    $sqlorder = "user.user_name";
    if ($with_display_preferences) {
        $uh       = UserHelper::instance();
        $sqlname  = $uh->getDisplayNameSQLQuery();
        $sqlorder = $uh->getDisplayNameSQLOrder();
    }

    $user_status = "( status='A' OR status='R' ";
    if ($show_suspended) {
        $user_status .= "OR status='S' ";
    }
    if ($show_deleted) {
        $user_status .= "OR status='D'";
    }
    $user_status .= ")";
    if ($user_ids) {
        $user_status .= ' AND user.user_id IN (' . $data_access->escapeIntImplode($user_ids) . ')';
    }
    $group_id = $data_access->escapeInt($group_id);
    $atid     = $data_access->escapeInt($atid);
    // Special Cases
    if ($ugroup_id == $GLOBALS['UGROUP_NONE']) {
        // Empty group
        return null;
    } elseif ($ugroup_id == $GLOBALS['UGROUP_ANONYMOUS']) {
        // Anonymous user
        return null;
    } elseif ($ugroup_id == $GLOBALS['UGROUP_REGISTERED']) {
        // Registered user
        return "(SELECT user.user_id, " . $sqlname . ", user.realname, user.user_name, user.email, user.status FROM user WHERE " . $user_status . " ORDER BY " . $sqlorder . " )";
    } elseif ($ugroup_id == $GLOBALS['UGROUP_PROJECT_MEMBERS']) {
        // Project members
        return "(SELECT user.user_id, " . $sqlname . ", user.realname, user.user_name, user.email, user.status FROM user, user_group ug WHERE user.user_id = ug.user_id AND ug.group_id = $group_id AND " . $user_status . " ORDER BY " . $sqlorder . ")";
    } elseif ($ugroup_id == $GLOBALS['UGROUP_WIKI_ADMIN']) {
        // Wiki admins
        return "(SELECT user.user_id, " . $sqlname . ", user.realname, user.user_name, user.email, user.status FROM user, user_group ug WHERE user.user_id = ug.user_id AND ug.group_id = $group_id AND wiki_flags = '2' AND " . $user_status . "  ORDER BY " . $sqlorder . ")";
    } elseif ($ugroup_id == $GLOBALS['UGROUP_PROJECT_ADMIN']) {
        // Project admins
        return "(SELECT user.user_id, " . $sqlname . ", user.realname, user.user_name, user.email, user.status FROM user, user_group ug WHERE user.user_id = ug.user_id AND ug.group_id = $group_id AND admin_flags = 'A' AND " . $user_status . "  ORDER BY " . $sqlorder . ")";
    } elseif ($atid && $ugroup_id == $GLOBALS['UGROUP_TRACKER_ADMIN']) {
        // Tracker admins
        return "(SELECT user.user_id, " . $sqlname . ", user.realname, user.user_name,  user.email, user.status FROM artifact_perm ap, user WHERE (user.user_id = ap.user_id) and group_artifact_id=$atid AND perm_level in (2,3) AND " . $user_status . "  ORDER BY " . $sqlorder . ")";
    } elseif ((int) $ugroup_id === ProjectUGroup::FORUM_ADMIN) {
        // Forum admins
        return "(SELECT user.user_id, $sqlname, user.realname, user.user_name, user.email, user.status
                    FROM user, user_group ug
                    WHERE user.user_id = ug.user_id
                    AND ug.group_id = $group_id
                    AND forum_flags = '2'
                    AND " . $user_status . "
                    ORDER BY " . $sqlorder . " )";
    } elseif ((int) $ugroup_id === ProjectUGroup::NEWS_WRITER) {
        // News writer
        return "(SELECT user.user_id, $sqlname, user.realname, user.user_name,  user.email, user.status
                    FROM user, user_group ug
                    WHERE user.user_id = ug.user_id
                    AND ug.group_id = $group_id
                    AND (ug.news_flags = '1' OR ug.news_flags = '2')
                    AND " . $user_status . "
                    ORDER BY " . $sqlorder . " )";
    } elseif ((int) $ugroup_id === ProjectUGroup::NEWS_ADMIN) {
        // News admin
        return "(SELECT user.user_id, $sqlname, user.realname, user.user_name, user.email, user.status
                    FROM user, user_group ug
                    WHERE user.user_id = ug.user_id
                    AND ug.group_id = $group_id
                    AND ug.news_flags = '2'
                    AND " . $user_status . "
                    ORDER BY " . $sqlorder . " )";
    }
    return null;
}

/**
 * Retrieve all dynamic groups' members except ANONYMOUS, NONE, REGISTERED
 * @param int $group_id
 * @param int $atid
 * @return Array
 */
function ugroup_get_all_dynamic_members($group_id, $atid = 0)
{
    $members = [];
    $sql     = [];
    $ugroups = [];
    //retrieve dynamic ugroups id and name
    $rs = db_query("SELECT ugroup_id, name FROM ugroup WHERE ugroup_id IN (" . implode(',', $GLOBALS['UGROUPS']) . ") ");
    while ($row = db_fetch_array($rs)) {
        $ugroups[$row['ugroup_id']] = $row['name'];
    }
    foreach ($GLOBALS['UGROUPS'] as $ugroup_id) {
        if ($ugroup_id == $GLOBALS['UGROUP_ANONYMOUS'] || $ugroup_id == $GLOBALS['UGROUP_REGISTERED'] || $ugroup_id == $GLOBALS['UGROUP_NONE']) {
            continue;
        }
        $sql = ugroup_db_get_dynamic_members($ugroup_id, $atid, $group_id);
        if ($sql === null) {
            continue;
        }
        $rs = db_query($sql);
        while ($row = db_fetch_array($rs)) {
            $members[] = [
                'ugroup_id' => $ugroup_id,
                'name'      => \Tuleap\User\UserGroup\NameTranslator::getUserGroupDisplayKey((string) $ugroups[$ugroup_id]),
                'user_id'   => $row['user_id'],
                'user_name' => $row['user_name'],
            ];
        }
    }
    return $members;
}

/**
 * Create a new ugroup
 *
 * @return ugroup_id
 */
function ugroup_create($group_id, $ugroup_name, $ugroup_description, $group_templates)
{
    global $Language;

    // Sanity check
    if (! $ugroup_name) {
        throw new CannotCreateUGroupException(_('The group name is missing'));
    }
    if (! preg_match("/^[a-zA-Z0-9_\-]+$/i", $ugroup_name)) {
        throw new CannotCreateUGroupException(sprintf(_('Invalid group name: %s. Please use only alphanumerical characters.'), $ugroup_name));
    }
    // Check that there is no ugroup with the same name in this project
    $sql    = "SELECT * FROM ugroup WHERE name='" . db_es($ugroup_name) . "' AND group_id='" . db_ei($group_id) . "'";
    $result = db_query($sql);
    if (db_numrows($result) > 0) {
        throw new CannotCreateUGroupException(_('User group already exists in this project. Please choose another name.'));
    }

    // Create
    $sql    = "INSERT INTO ugroup (name,description,group_id) VALUES ('" . db_es($ugroup_name) . "', '" . db_es($ugroup_description) . "'," . db_ei($group_id) . ")";
    $result = db_query($sql);

    if (! $result) {
        throw new CannotCreateUGroupException(_('An error occurred when saving user group.'));
    } else {
        $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_ugroup_utils', 'ug_create_success'));
    }
    // Now get the corresponding ugroup_id
    $sql    = "SELECT ugroup_id FROM ugroup WHERE group_id=" . db_ei($group_id) . " AND name='" . db_es($ugroup_name) . "'";
    $result = db_query($sql);
    if (! $result) {
        throw new CannotCreateUGroupException(_('User group created but cannot get Id.'));
    }
    $ugroup_id = db_result($result, 0, 0);
    if (! $ugroup_id) {
        throw new CannotCreateUGroupException(_('User group created but cannot get Id.'));
    }

    // Now populate new group if a 'template' was selected
    if ($group_templates == "cx_empty") {
        // Do nothing, the group should be empty
        $query = '';
    } elseif ($group_templates == "cx_empty2") {
        // The user selected '----'
        $query = '';
        $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_ugroup_utils', 'no_g_template'));
    } elseif ($group_templates == "cx_members") {
        // Get members from predefined groups
        $query = "SELECT user_id FROM user_group WHERE group_id=" . db_ei($group_id);
    } elseif ($group_templates == "cx_admins") {
        $query = "SELECT user_id FROM user_group WHERE group_id=" . db_ei($group_id) . " AND admin_flags='A'";
    } else {
        // $group_templates should contain the ID of an exiting group
        // Copy members from an existing group
        $query = "SELECT user_id FROM ugroup_user WHERE ugroup_id=" . db_ei($group_templates);
    }

    // Copy user IDs in new group
    if ($query) {
        $res       = db_query($query);
        $countuser = 0;
        while ($row = db_fetch_array($res)) {
            $sql = "INSERT INTO ugroup_user (ugroup_id,user_id) VALUES (" . db_ei($ugroup_id) . "," . db_ei($row['user_id']) . ")";
            if (! db_query($sql)) {
                exit_error($Language->getText('global', 'error'), $Language->getText('project_admin_ugroup_utils', 'cant_insert_u_in_g', [$row['user_id'], $ugroup_id, db_error()]));
            }
            $countuser++;
        }
        $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_ugroup_utils', 'u_added', $countuser));
    }

    // raise an event for ugroup creation
    $em = EventManager::instance();
    $em->processEvent('project_admin_ugroup_creation', [
        'group_id'  => $group_id,
        'ugroup_id' => $ugroup_id,
    ]);

    return $ugroup_id;
}



/**
 * Update ugroup with list of members
 */
function ugroup_update($group_id, $ugroup_id, $ugroup_name, $ugroup_description)
{
    global $Language;
    $purifier = Codendi_HTMLPurifier::instance();

    // Sanity check
    if (! $ugroup_name) {
        throw new CannotCreateUGroupException(_('The group name is missing'));
    }
    if (! preg_match("/^[a-zA-Z0-9_\-]+$/i", $ugroup_name)) {
        throw new CannotCreateUGroupException(sprintf(_('Invalid group name: %s. Please use only alphanumerical characters.'), $ugroup_name));
    }
    if (! $ugroup_id) {
        throw new CannotCreateUGroupException(_('The group id is missing'));
    }
    // Retrieve ugroup old name before updating
    $sql    = "SELECT name FROM ugroup WHERE group_id='" . db_ei($group_id) . "' AND ugroup_id ='" . db_ei($ugroup_id) . "'";
    $result = db_query($sql);
    if ($result && ! db_error($result)) {
        $row             = db_fetch_array($result);
        $ugroup_old_name = $row['name'];
    }

    // Check that there is no ugroup with the same name and a different id in this project
    $sql    = "SELECT * FROM ugroup WHERE name='" . db_es($ugroup_name) . "' AND group_id='" . db_ei($group_id) . "' AND ugroup_id!='" . db_ei($ugroup_id) . "'";
    $result = db_query($sql);
    if (db_numrows($result) > 0) {
        throw new CannotCreateUGroupException(_('User group already exists in this project. Please choose another name.'));
    }

    // Update
    $sql    = "UPDATE ugroup SET name='" . db_es($ugroup_name) . "', description='" . db_es($ugroup_description) . "' WHERE ugroup_id=" . db_ei($ugroup_id);
    $result = db_query($sql);

    if (! $result) {
        throw new CannotCreateUGroupException(_('Cannot update users group.'));
    }

    // Search for all members of this ugroup
    $pickList = [];
    $sql      = "SELECT user_id FROM ugroup_user WHERE ugroup_id = " . db_ei($ugroup_id);
    if ($res = db_query($sql)) {
        while ($row = db_fetch_array($res)) {
            $pickList[] = $row['user_id'];
        }
    }

    // raise an event for ugroup edition
    $em = EventManager::instance();
    $em->processEvent('project_admin_ugroup_edition', [
        'group_id'  => $group_id,
        'ugroup_id' => $ugroup_id,
        'ugroup_name' => $ugroup_name,
        'ugroup_old_name' => $ugroup_old_name,
        'ugroup_desc' => $ugroup_description,
        'pick_list' => $pickList,
    ]);

    // Now log in project history
    (new ProjectHistoryDao())->groupAddHistory('upd_ug', '', $group_id, [$ugroup_name]);

    $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_ugroup_utils', 'ug_upd_success', [$ugroup_name, count($pickList)]));
}

function ugroup_remove_user_from_ugroup($group_id, $ugroup_id, $user_id)
{
    $sql = "DELETE FROM ugroup_user
    WHERE ugroup_id = " . db_ei($ugroup_id) . "
      AND user_id = " . db_ei($user_id);
    $res = db_query($sql);
    if (! $res) {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('project_admin_ugroup_utils', 'cant_update_ug', db_error()));
    }
    if ($rows = db_affected_rows($res)) {
        // Now log in project history
        $res = ugroup_db_get_ugroup($ugroup_id);
        (new ProjectHistoryDao())->groupAddHistory('upd_ug', '', $group_id, [db_result($res, 0, 'name')]);
        // Search for all members of this ugroup
        $sql        = "SELECT count(user.user_id)" .
             "FROM ugroup_user, user " .
             "WHERE user.user_id = ugroup_user.user_id " .
             "AND user.status IN ('A', 'R') " .
             "AND ugroup_user.ugroup_id=" . db_ei($ugroup_id);
        $result     = db_query($sql);
        $usersCount = db_result($result, 0, 0);
        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('project_admin_ugroup_utils', 'ug_upd_success', [db_result($res, 0, 'name'), $usersCount]));
        if ($usersCount == 0) {
            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('project_admin_ugroup_utils', 'ug_upd_empty'));
        }
        // Raise event for ugroup modification
        EventManager::instance()->processEvent('project_admin_ugroup_remove_user', [
            'group_id' => $group_id,
            'ugroup_id' => $ugroup_id,
            'user_id' => $user_id,
        ]);
    }
}
function ugroup_add_user_to_ugroup($group_id, $ugroup_id, $user_id)
{
    if (! ugroup_user_is_member($user_id, $ugroup_id, $group_id)) {
        $sql = "INSERT INTO ugroup_user (ugroup_id, user_id) VALUES(" . db_ei($ugroup_id) . ", " . db_ei($user_id) . ")";
        $res = db_query($sql);
        if (! $res) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('project_admin_ugroup_utils', 'cant_update_ug', db_error()));
        }
        if ($rows = db_affected_rows($res)) {
            // Now log in project history
            $res = ugroup_db_get_ugroup($ugroup_id);
            (new ProjectHistoryDao())->groupAddHistory('upd_ug', '', $group_id, [db_result($res, 0, 'name')]);
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('project_admin_ugroup_utils', 'ug_upd_success', [db_result($res, 0, 'name'), 1]));
            // Raise event for ugroup modification
            EventManager::instance()->processEvent('project_admin_ugroup_add_user', [
                'group_id' => $group_id,
                'ugroup_id' => $ugroup_id,
                'user_id' => $user_id,
            ]);
        }
    } else {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            $GLOBALS['Language']->getText('include_account', 'user_already_member')
        );
    }
}

/**
 * Delete ugroup
 *
 * @return false if error
 */
function ugroup_delete($group_id, $ugroup_id)
{
    global $Language;
    if (! $ugroup_id) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_ugroup_utils', 'ug_not_given'));
        return false;
    }
    $project        = ProjectManager::instance()->getProject($group_id);
    $ugroup_manager = new UGroupManager();
    $ugroup         = $ugroup_manager->getUGroupWithMembers($project, $ugroup_id);

    if (! $ugroup) {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            dgettext('tuleap-core', 'User group does not exist')
        );
    }

    $sql    = "DELETE FROM ugroup WHERE group_id=" . db_ei($group_id) . " AND ugroup_id=" . db_ei($ugroup_id);
    $result = db_query($sql);
    if (! $result || db_affected_rows($result) < 1) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_editgroupinfo', 'upd_fail', (db_error() ? db_error() : ' ' )));
         return false;
    }
    $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_ugroup_utils', 'g_del'));
    // Now remove users
    $sql = "DELETE FROM ugroup_user WHERE ugroup_id=" . db_ei($ugroup_id);

    $result = db_query($sql);
    if (! $result) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_ugroup_utils', 'cant_remove_u', db_error()));
        return false;
    }
    $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_ugroup_utils', 'all_u_removed'));

    // raise an event for ugroup deletion
    $em = EventManager::instance();
    $em->processEvent('project_admin_ugroup_deletion', [
        'group_id'  => $group_id,
        'ugroup_id' => $ugroup_id,
        'ugroup'    => $ugroup,
    ]);

    // Last, remove permissions for this group
    $perm_cleared = permission_clear_ugroup($group_id, $ugroup_id);
    if (! ($perm_cleared)) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_ugroup_utils', 'cant_remove_perm', db_error()));
        return false;
    } elseif ($perm_cleared > 1) {
        $perm_cleared--;
        $GLOBALS['Response']->addFeedback('warning', $Language->getText('project_admin_ugroup_utils', 'perm_warning', $perm_cleared));
    }
    // Now log in project history
    (new ProjectHistoryDao())->groupAddHistory('del_ug', '', $group_id, [$ugroup->getName()]);

    return true;
}

/**
 * Wrapper for tests
 *
 * @return ProjectUGroup
 */
function ugroup_get_ugroup()
{
    return new ProjectUGroup();
}

/**
 * Calculate the number of project admins and non project admins of the ugroup
 *
 * @param int $groupId
 * @param String  $usersSql
 *
 * @return Array
 */
function ugroup_count_project_admins($groupId, $usersSql)
{
    $admins    = 0;
    $nonAdmins = 0;
    if ($usersSql !== null) {
        $um  = UserManager::instance();
        $res = db_query($usersSql);
        while ($row = db_fetch_array($res)) {
            $user = $um->getUserById($row['user_id']);
            if ($user->isMember($groupId, 'A')) {
                $admins++;
            } else {
                $nonAdmins++;
            }
        }
    }
    return ['admins' => $admins, 'non_admins' => $nonAdmins];
}

/**
 * Filter static ugroups that contain project admins.
 * Retun value is the number of non project admins
 * in the filtered ugroups.
 *
 * @param int $groupId
 * @param Array   $ugroups
 * @param Array   &$validUGroups
 *
 * @return int
 */
function ugroup_count_non_admin_for_static_ugroups($groupId, $ugroups, &$validUGroups)
{
    $containNonAdmin = 0;
    $uGroup          = ugroup_get_ugroup();
    foreach ($ugroups as $ugroupId) {
        if ($uGroup->exists($groupId, $ugroupId)) {
            $sql          = ugroup_db_get_members($ugroupId);
            $arrayUsers   = ugroup_count_project_admins($groupId, $sql);
            $nonAdmin     = $arrayUsers['non_admins'];
            $containAdmin = $arrayUsers['admins'];
            if ($containAdmin > 0) {
                $validUGroups[]   = $ugroupId;
                $containNonAdmin += $nonAdmin;
            }
        }
    }
    return $containNonAdmin;
}

/**
 * Filter dynamic ugroups that contain project admins.
 * Retun is the number of non project admins
 * in the filtered ugroups.
 *
 * @param int $groupId
 * @param Array   $ugroups
 * @param Array   &$validUGroups
 *
 * @return int
 */
function ugroup_count_non_admin_for_dynamic_ugroups($groupId, $ugroups, &$validUGroups)
{
    $containNonAdmin = 0;
    foreach ($ugroups as $ugroupId) {
        $sql = ugroup_db_get_dynamic_members($ugroupId, null, $groupId);
        if ($sql === null) {
            continue;
        }
        $arrayUsers = ugroup_count_project_admins($groupId, $sql);
        if ($arrayUsers['admins'] > 0) {
            $validUGroups[]   = $ugroupId;
            $containNonAdmin += $arrayUsers['non_admins'];
        }
    }
    return $containNonAdmin;
}

/**
 * Validate the ugroup list containing group admins.
 * Remove ugroups that are empty or contain no project admins.
 * Don't remove ugroups containing both project admins and non project admins
 * just indicate the total number of non project admins.
 *
 * @param int $groupId
 * @param Array   $ugroups
 *
 * @return Array
 */
function ugroup_filter_ugroups_by_project_admin($groupId, $ugroups)
{
    $validUGroups = [];
    // Check static ugroups
    $nonAdmins = ugroup_count_non_admin_for_static_ugroups($groupId, $ugroups, $validUGroups);
    // Check dynamic ugroups
    $nonAdmins += ugroup_count_non_admin_for_dynamic_ugroups($groupId, $ugroups, $validUGroups);
    return ['non_admins' => $nonAdmins, 'ugroups' => $validUGroups];
}
