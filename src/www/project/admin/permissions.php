<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean 2017 - Present. All rights reserved
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

// Supported object types and related object_id:
//
// type='PACKAGE_READ'             id='package_id'                 table='frs_package'
// type='RELEASE_READ'             id='release_id'                 table='frs_release'
// type='WIKI_READ'                id='group_id'                   table='wiki_page'
// type='WIKIPAGE_READ'            id='id'                         table='wiki_page'
// type='WIKIATTACHMENT_READ'      id='id'                         table='wiki_attachment'

use Tuleap\FRS\FRSPermissionManager;
use Tuleap\User\UserGroup\NameTranslator;

require_once __DIR__ . '/ugroup_utils.php';
require_once __DIR__ . '/project_admin_utils.php';


/**
 * Return a printable name for a given permission type
 */
function permission_get_name($permission_type)
{
    global $Language;
    if ($permission_type == 'PACKAGE_READ') {
        return $Language->getText('project_admin_permissions', 'pack_download');
    } elseif ($permission_type == 'RELEASE_READ') {
        return $Language->getText('project_admin_permissions', 'rel_download');
    } elseif ($permission_type == 'WIKI_READ') {
        return $Language->getText('project_admin_permissions', 'wiki_access');
    } elseif ($permission_type == 'WIKIPAGE_READ') {
        return $Language->getText('project_admin_permissions', 'wiki_access');
    } elseif ($permission_type == 'WIKIATTACHMENT_READ') {
        return $Language->getText('project_admin_permissions', 'wiki_attachment_access');
    } else {
        $em   = EventManager::instance();
        $name = false;
        $em->processEvent('permission_get_name', ['permission_type' => $permission_type, 'name' => &$name]);
        return $name ? $name : $permission_type;
    }
}

/**
 * Return the type of a given object
 */
function permission_get_object_type($permission_type, $object_id)
{
    if ($permission_type == 'PACKAGE_READ') {
        return 'package';
    } elseif ($permission_type == 'RELEASE_READ') {
        return 'release';
    } elseif ($permission_type == 'WIKI_READ') {
        return 'wiki';
    } elseif ($permission_type == 'WIKIPAGE_READ') {
        return 'wikipage';
    } elseif ($permission_type == 'WIKIATTACHMENT_READ') {
        return 'wikiattachment';
    } else {
        $em          = EventManager::instance();
        $object_type = false;
        $em->processEvent('permission_get_object_type', [
            'permission_type' => $permission_type,
            'object_id'       => $object_id,
            'object_type'     => &$object_type,
        ]);
        return $object_type ? $object_type : 'object';
    }
}

/**
 * Return the name of a given object
 */
function permission_get_object_name($permission_type, $object_id)
{
    global $Language,$group_id;

    $pm = ProjectManager::instance();
    if ($permission_type == 'PACKAGE_READ') {
        $package_factory = new FRSPackageFactory();
        $package         = $package_factory->getFRSPackageFromDb($object_id);

        if ($package) {
            return $package->getName();
        }
    } elseif ($permission_type == 'RELEASE_READ') {
        $release_factory = new FRSReleaseFactory();
        $release         = $release_factory->getFRSReleaseFromDb($object_id);

        if ($release) {
            return $release->getName();
        }
    } elseif ($permission_type == 'WIKI_READ') {
        return $Language->getText('project_admin_permissions', 'wiki');
    } elseif ($permission_type == 'WIKIPAGE_READ') {
        // $wikipage= new WikiPage($object_id);
        // return $wikipage->getPagename();
        return "$object_id";
    } elseif ($permission_type == 'WIKIATTACHMENT_READ') {
        return $Language->getText('project_admin_permissions', 'wikiattachment');
    } else {
        $em          = EventManager::instance();
        $object_name = false;
        $em->processEvent('permission_get_object_name', [
            'permission_type' => $permission_type,
            'object_id'       => $object_id,
            'object_name'     => &$object_name,
        ]);
        return $object_name ? $object_name : $object_id;
    }
}

/**
 * Check if the current user is allowed to change permissions, depending on the permission_type
 *
 * @param int $project_id Id of the project
 * @param String  $permission_type Type of the permission
 * @param bool $object_id Object on which permission is applied
 *
 * @return bool
 */
function permission_user_allowed_to_change($project_id, $permission_type, $object_id = 0)
{
    $project_manager = ProjectManager::instance();
    $project         = $project_manager->getProject($project_id);

    // Super-user and project admin has all rights...
    $user = UserManager::instance()->getCurrentUser();

    if (user_is_super_user() || $user->isMember($project_id, 'A')) {
        return true;
    }

    if ($permission_type == 'WIKI_READ') {
        return (user_ismember($project_id, 'W2'));
    } elseif ($permission_type == 'WIKIPAGE_READ') {
        return (user_ismember($project_id, 'W2'));
    } elseif ($permission_type == 'WIKIATTACHMENT_READ') {
        return (user_ismember($project_id, 'W2'));
    } elseif ($permission_type == 'PACKAGE_READ') {
        $permission_manager = FRSPermissionManager::build();

        return $permission_manager->isAdmin($project, $user);
    } elseif ($permission_type == 'RELEASE_READ') {
        $frs_package_factory = new FRSPackageFactory();

        return $frs_package_factory->userCanCreate((int) $project->getID(), $user);
    } else {
        $em      = EventManager::instance();
        $allowed = false;
        $em->processEvent('permission_user_allowed_to_change', [
            'group_id'        => $project_id,
            'permission_type' => $permission_type,
            'object_id'       => $object_id,
            'allowed'         => &$allowed,
        ]);
        return $allowed;
    }
}

/**
 * Return a DB list of ugroup_ids authorized to access the given object
 */
function permission_db_authorized_ugroups($permission_type, $object_id)
{
    $sql = "SELECT ugroup_id FROM permissions WHERE permission_type='" . db_es($permission_type) . "' AND object_id='" . db_es($object_id) . "' ORDER BY ugroup_id";
    // note that 'order by' is needed for comparing ugroup_lists (see permission_equals_to_default)
    return db_query($sql);
}


/**
 * Return a DB list of the default ugroup_ids authorized to access the given permission_type
 * @deprecated
 * @see PermissionManager::getDefaults
 */
function permission_db_get_defaults($permission_type)
{
    $sql = "SELECT ugroup_id FROM permissions_values WHERE permission_type='" . db_es($permission_type) . "' AND is_default='1' ORDER BY ugroup_id";
    return db_query($sql);
}


/**
 * Check if the given object has some permissions defined
 *
 * @return bool true if permissions are defined, false otherwise.
 */
function permission_exist($permission_type, $object_id)
{
    $res = permission_db_authorized_ugroups($permission_type, $object_id);
    if (db_numrows($res) < 1) {
        // No group defined => no permissions set
        return false;
    } else {
        return true;
    }
}




/**
 * Check permissions on the given object
 *
 * @param $permission_type defines the type of permission (e.g. "DOCUMENT_READ")
 * @param $object_id is the ID of the object we want to access (e.g. a docid)
 * @param $user_id is the ID of the user that want to access the object
 * @param $group_id is the group_id the object belongs to; useful for project-specific authorized ugroups (e.g. 'project admins')
 * @return bool true if user is authorized, false otherwise.
 */
function permission_is_authorized($permission_type, $object_id, $user_id, $group_id)
{
    // Super-user has all rights...
    $u = UserManager::instance()->getUserById($user_id);
    if ($u->isSuperUser()) {
        return true;
    }

    $res = permission_db_authorized_ugroups($permission_type, $object_id);
    if (db_numrows($res) < 1) {
        // No ugroup defined => no permissions set => get default permissions
        /** @psalm-suppress DeprecatedFunction */
        $res = permission_db_get_defaults($permission_type);
    }
    // permissions set for this object.
    while ($row = db_fetch_array($res)) {
        // should work even for anonymous users
        if (ugroup_user_is_member($user_id, $row['ugroup_id'], $group_id)) {
            return true;
        }
    }
    return false;
}

/**
 * @returns array the permissions for the ugroups
 */
function permission_get_ugroups_permissions($group_id, $object_id, $permission_types, $use_default_permissions = true)
{
    $cache        = Tuleap\Project\UgroupsPermissionsCache::instance();
    $cached_value = $cache->get($group_id, $object_id, $permission_types, $use_default_permissions);
    if ($cached_value !== null) {
        return $cached_value;
    }

    //We retrive ugroups (user defined)
    $object_id = db_es($object_id);
    $sql       = 'SELECT u.ugroup_id, u.name, p.permission_type ' .
        ' FROM permissions p, ugroup u ' .
        ' WHERE p.ugroup_id = u.ugroup_id ' .
        "       AND p.object_id = '" . $object_id . "' " .
        '       AND p.permission_type in (';
    if (count($permission_types) > 0) {
        $sql .= "'" . db_es($permission_types[0]) . "'";
        $i    = 1;
        while ($i < count($permission_types)) {
            $sql .= ",'" . db_es($permission_types[$i++]) . "'";
        }
    }
    $sql .= ')';
    $res  = db_query($sql);
    if (! $res) {
        $cache->set($group_id, $object_id, $permission_types, $use_default_permissions, false);
        return false;
    } else {
        $return                   = [];
        $show_default_permissions = false;
        //Now we look at the number of results :
        //if < 1 then we have no ugroups permissions (user-defined) => the final ugroups are default values
        if (db_numrows($res) < 1) {
            $show_default_permissions = true;
        } else {
            while ($row = db_fetch_array($res)) {
                //We initialize ugroup entries only once
                if (! isset($return[$row[0]])) {
                    $return[$row[0]] = [
                        'ugroup'      => [
                            'id'   => $row[0],
                            'name' => NameTranslator::getUserGroupDisplayKey((string) $row[1]),
                        ],
                        'permissions' => [],
                    ];
                    //We add link for non-default ugroups
                    if ($row[0] > 100) {
                        $return[$row[0]]['ugroup']['link'] = '/project/admin/editugroup.php?group_id=' . $group_id . '&ugroup_id=' . $row[0] . '&func=edit';
                    }
                }
                //We set permission
                $return[$row[0]]['permissions'][$row[2]] = 1;
            }
        }

        //Now we look at the default ugroups
        $sql = 'SELECT ug.ugroup_id, ug.name, pv.permission_type, pv.is_default ' .
            ' FROM permissions_values pv, ugroup ug ' .
            ' WHERE ug.ugroup_id = pv.ugroup_id ' .
            '       AND pv.permission_type in (';
        if (count($permission_types) > 0) {
            $sql .= "'" . db_es($permission_types[0]) . "'";
            $i    = 1;
            while ($i < count($permission_types)) {
                $sql .= ",'" . db_es($permission_types[$i++]) . "'";
            }
        }
        $sql .= ')';
        $res  = db_query($sql);
        if ($res) {
            while ($row = db_fetch_array($res)) {
                if (! isset($return[$row[0]])) {
                    $return[$row[0]] = [
                        'ugroup'      => [
                            'id'   => $row[0],
                            'name' => NameTranslator::getUserGroupDisplayKey((string) $row[1]),
                        ],
                        'permissions' => [],
                    ];
                }
                //if we have user-defined permissions,
                //the default ugroups which don't have user-defined permission have no-access
                //Only if we have to use default permissions
                if ($show_default_permissions && $row[3] === '1' && $use_default_permissions) {
                    $return[$row[0]]['permissions'][$row[2]] = 1;
                }
            }
        }
        //Now we look at project ugroups that have no permissions yet
        $sql        = 'SELECT ugroup_id, name ' .
            ' FROM ugroup ' .
            " WHERE group_id = '" . db_ei($group_id) . "' " .
            '       AND ugroup_id NOT IN (';
        $ugroup_ids = array_keys($return);
        if (count($ugroup_ids) > 0) {
            $sql .= "'" . db_ei($ugroup_ids[0]) . "'";
            $i    = 1;
            while ($i < count($ugroup_ids)) {
                $sql .= ",'" . db_ei($ugroup_ids[$i++]) . "'";
            }
        }
        $sql .= ')';
        $res  = db_query($sql);
        if ($res) {
            while ($row = db_fetch_array($res)) {
                $return[$row[0]] = [
                    'ugroup'      => [
                        'id'   => $row[0],
                        'name' => NameTranslator::getUserGroupDisplayKey((string) $row[1]),
                    ],
                    'permissions' => [],
                ];
                //We add link for non-default ugroups
                if ($row[0] > 100) {
                    $return[$row[0]]['ugroup']['link'] = '/project/admin/editugroup.php?group_id=' . $group_id . '&ugroup_id=' . $row[0] . '&func=edit';
                }
            }
        }
        $cache->set($group_id, $object_id, $permission_types, $use_default_permissions, $return);
        return $return;
    }
}


/**
 * Display permission selection box for the given object.
 * The result of this form should be parsed with permission_process_selection_form()
 *
 * For the list of supported permission_type and id, see above in file header.
 */
function permission_fetch_selection_form($permission_type, $object_id, $group_id, $post_url)
{
    $html = '';
    if (! $post_url) {
        $post_url = '?';
    }

    $purifier = Codendi_HTMLPurifier::instance();

    // Display form
    $html .= '<FORM ACTION="' . $post_url . '" METHOD="POST" data-test="form-permissions">
        <INPUT TYPE="HIDDEN" NAME="func" VALUE="update_permissions">
        <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="' . $purifier->purify($group_id) . '">
        <INPUT TYPE="HIDDEN" NAME="permission_type" VALUE="' . $purifier->purify($permission_type) . '">
        <INPUT TYPE="HIDDEN" NAME="object_id" VALUE="' . $purifier->purify($object_id) . '">';

    $html .= permission_fetch_selection_field($permission_type, $object_id, $group_id);

    $html .= '<p><br><INPUT TYPE="SUBMIT" NAME="submit" class="tlp-button-primary" data-test="submit-form-permissions" VALUE="' . $GLOBALS['Language']->getText('project_admin_permissions', 'submit_perm') . '"> ';
    $html .= '<INPUT TYPE="SUBMIT" class="tlp-button-primary tlp-button-secondary" NAME="reset" VALUE="' . $GLOBALS['Language']->getText('project_admin_permissions', 'reset_to_def') . '">';
    $html .= '</FORM>';
    $html .= '<p>' . $GLOBALS['Language']->getText(
        'project_admin_permissions',
        'admins_create_modify_ug',
        [
            '/project/admin/ugroup.php?group_id=' . urlencode($group_id),
        ]
    );

    return $html;
}

function permission_fetch_selected_ugroups($permission_type, $object_id, $group_id)
{
    $ugroups     = [];
    $res_ugroups = permission_db_authorized_ugroups($permission_type, $object_id);
    while ($row = db_fetch_array($res_ugroups)) {
        $data      = db_fetch_array(ugroup_db_get_ugroup($row['ugroup_id']));
        $ugroups[] = NameTranslator::getUserGroupDisplayKey((string) $data['name']);
    }
    return $ugroups;
}

function permission_fetch_selected_ugroups_ids($permission_type, $object_id, $group_id)
{
    $ugroups     = [];
    $res_ugroups = permission_db_authorized_ugroups($permission_type, $object_id);
    while ($row = db_fetch_array($res_ugroups)) {
        $ugroups[] = $row['ugroup_id'];
    }
    return $ugroups;
}

function permission_fetch_selection_field(
    $permission_type,
    $object_id,
    $group_id,
    $htmlname = 'ugroups',
    $disabled = false,
    $show_admins = true,
    $show_nobody = true,
) {
    $html = '';

    // Get ugroups already defined for this permission_type
    $res_ugroups = permission_db_authorized_ugroups($permission_type, $object_id);
    $nb_set      = db_numrows($res_ugroups);

    // Now retrieve all possible ugroups for this project, as well as the default values
    $sql = "SELECT ugroup_id, is_default
            FROM permissions_values
            WHERE permission_type='" . db_es($permission_type) . "'";

    if (! $show_admins) {
        $sql .= 'AND ugroup_id <> ' . ProjectUGroup::PROJECT_ADMIN;
    }

    $res                = db_query($sql);
    $predefined_ugroups = '';
    $default_values     = [];

    if (db_numrows($res) < 1) {
        $html .= '<p><b>' . $GLOBALS['Language']->getText('global', 'error') . '</b>: ' . $GLOBALS['Language']->getText('project_admin_permissions', 'perm_type_not_def', $permission_type);
        return $html;
    } else {
        while ($row = db_fetch_array($res)) {
            if ($predefined_ugroups) {
                $predefined_ugroups .= ' ,';
            }
            $predefined_ugroups .= db_ei($row['ugroup_id']);
            if ($row['is_default']) {
                $default_values[] = $row['ugroup_id'];
            }
        }
    }

    $sql = 'SELECT *
              FROM ugroup
              WHERE group_id=' . db_ei($group_id) . '
                OR ugroup_id IN (' . $predefined_ugroups . ')
            ORDER BY ugroup_id';

    $res   = db_query($sql);
    $array = [];

    while ($row = db_fetch_array($res)) {
        $name    = NameTranslator::getUserGroupDisplayKey($row[1]);
        $array[] = [
            'value' => $row[0],
            'text' => $name,
        ];
    }

    $html .= html_build_multiple_select_box(
        $array,
        $htmlname . '[]',
        ($nb_set ? util_result_column_to_array($res_ugroups) : $default_values),
        8,
        $show_nobody,
        NameTranslator::getUserGroupDisplayKey('ugroup_nobody_name_key'),
        false,
        '',
        false,
        '',
        false,
        CODENDI_PURIFIER_CONVERT_HTML,
        $disabled
    );

    return $html;
}

function permission_display_selection_form($permission_type, $object_id, $group_id, $post_url)
{
    echo permission_fetch_selection_form($permission_type, $object_id, $group_id, $post_url);
}

/**
 * Clear all permissions for the given object
 * Access rights to this function are checked (must be project admin!)
 * @return false if error, true otherwise
*/

function permission_clear_all($group_id, $permission_type, $object_id, $log_permission_history = true)
{
    if (! permission_user_allowed_to_change($group_id, $permission_type, $object_id)) {
        return false;
    }
    $sql = "DELETE FROM permissions WHERE permission_type='" . db_es($permission_type) . "' AND object_id='" . db_es($object_id) . "'";
    $res = db_query($sql);
    if (! $res) {
        return false;
    } else {
        // Log permission change
        if ($log_permission_history) {
            permission_add_history($group_id, $permission_type, $object_id);
        }
        return true;
    }
}

/**
 * Clear all permissions for the given ugroup
 * Access rights to this function are checked (must be project admin!)
 * @return false if error, number of permissions deleted+1 otherwise
 * (why +1? because there might be no permission, but no error either,
 *  so '0' means error, and 1 means no error but no permission)
 */

function permission_clear_ugroup($group_id, $ugroup_id)
{
    if (! user_ismember($group_id, 'A')) {
        return false;
    }
    $sql = "DELETE FROM permissions WHERE ugroup_id='" . db_ei($ugroup_id) . "'";
    $res = db_query($sql);
    if (! $res) {
        return false;
    } else {
        return (db_affected_rows($res) + 1);
    }
}


/**
 * Clear all permissions for the given ugroup and the given object
 * Access rights to this function are checked (must be project admin!)
 * @return false if error, number of permissions deleted+1 otherwise
 * (why +1? because there might be no permission, but no error either,
 *  so '0' means error, and 1 means no error but no permission)
 */
function permission_clear_ugroup_object($group_id, $permission_type, $ugroup_id, $object_id)
{
    if (! permission_user_allowed_to_change($group_id, $permission_type, $object_id)) {
        return false;
    }
    $sql = "DELETE FROM permissions WHERE ugroup_id='" . db_ei($ugroup_id) . "' AND object_id='" . db_es($object_id) . "' AND permission_type='" . db_es($permission_type) . "'";
    $res = db_query($sql);
    if (! $res) {
        return false;
    } else {
        return (db_affected_rows($res) + 1);
    }
}

/**
 * Effectively update permissions for the given object.
 * Access rights to this function are checked.
 */
function permission_add_ugroup($group_id, $permission_type, $object_id, $ugroup_id, $force = false)
{
    if (! $force && ! permission_user_allowed_to_change($group_id, $permission_type, $object_id)) {
        return false;
    }
    $sql = "INSERT INTO permissions (permission_type, object_id, ugroup_id) VALUES ('" . db_es($permission_type) . "', '" . db_es($object_id) . "', " . db_ei($ugroup_id) . ')';
    $res = db_query($sql);
    if (! $res) {
        return false;
    } else {
        return true;
    }
}


/**
 * Return true if the permissions set for the given object are the same as the default values
 * Return false if they are different
 */
function permission_equals_to_default($permission_type, $object_id)
{
    $res1 = permission_db_authorized_ugroups($permission_type, $object_id);
    if (db_numrows($res1) == 0) {
        // No ugroup defined means default values
        return true;
    }
    /** @psalm-suppress DeprecatedFunction */
    $res2 = permission_db_get_defaults($permission_type);
    if (db_numrows($res1) != db_numrows($res2)) {
        return false;
    }
    while ($row1 = db_fetch_array($res1)) {
        $row2 = db_fetch_array($res2);
        if ($row1['ugroup_id'] != $row2['ugroup_id']) {
            return false;
        }
    }
    return true;
}


/**
 * Log permission change in project history
 */
function permission_add_history($group_id, $permission_type, $object_id)
{
    global $Language;
    $res  = permission_db_authorized_ugroups($permission_type, $object_id);
    $type = permission_get_object_type($permission_type, $object_id);
    $name = permission_get_object_name($permission_type, $object_id);

    $project_history_dao = new ProjectHistoryDao();

    if (db_numrows($res) < 1) {
        // No ugroup defined => no permissions set
        $project_history_dao->groupAddHistory('perm_reset_for_' . $type, 'default', $group_id, [$name]);
        return;
    }
    $ugroup_list = '';
    $manager     = new UGroupManager();
    while ($row = db_fetch_array($res)) {
        if ($ugroup_list) {
            $ugroup_list .= ', ';
        }
        $ugroup_list .= $manager->getById($row['ugroup_id'])->getTranslatedName();
    }
    $project_history_dao->groupAddHistory('perm_granted_for_' . $type, $ugroup_list, $group_id, [$name]);
}

/**
 * Updated permissions according to form generated by permission_display_selection_form()
 *
 * parameter $ugroups contains the list of ugroups to authorize for this object.
 *
 * @deprecated
 * @see PermissionsManager::savePermissions
 * @return array a two elements array:
 *  - First element is 'true' or 'false', depending on whether permissions where changed
 *  - Second element is an optional message to be displayed (warning or error)
 * Exemples: (false,"Cannot combine 'any registered user' with another group)
 *           (true,"Removed 'nobody' from the list")
 */
function permission_process_selection_form($group_id, $permission_type, $object_id, $ugroups)
{
    global $Language;
    // Check that we have all parameters
    if (! $object_id) {
        return [false, $Language->getText('project_admin_permissions', 'obj_id_missed')];
    }
    if (! $permission_type) {
        return [false, $Language->getText('project_admin_permissions', 'perm_type_missed')];
    }
    if (! $group_id) {
        return [false, $Language->getText('project_admin_permissions', 'g_id_missed')];
    }
    $anon_selected = 0;
    $any_selected  = 0;

    // Check consistency of ugroup list
    $num_ugroups = 0;
    foreach ($ugroups as $selected_ugroup) {
        $num_ugroups++;
        if ($selected_ugroup == $GLOBALS['UGROUP_ANONYMOUS']) {
            $anon_selected = 1;
        }
        if ($selected_ugroup == $GLOBALS['UGROUP_REGISTERED']) {
            $any_selected = 1;
        }
    }

    // Reset permissions for this object, before setting the new ones
    permission_clear_all($group_id, $permission_type, $object_id, false);

    // Set new permissions
    $msg = '';
    if ($anon_selected) {
        if (permission_add_ugroup($group_id, $permission_type, $object_id, $GLOBALS['UGROUP_ANONYMOUS'])) {
            $msg .= $Language->getText('project_admin_permissions', 'all_users_added');
        } else {
            return [false, $Language->getText('project_admin_permissions', 'cant_add_ug_anonymous', $msg)];
        }
        if ($num_ugroups > 1) {
            $msg .= $Language->getText('project_admin_permissions', 'ignore_g');
        }
    } elseif ($any_selected) {
        if (permission_add_ugroup($group_id, $permission_type, $object_id, $GLOBALS['UGROUP_REGISTERED'])) {
            $msg .= $Language->getText('project_admin_permissions', 'all_registered_users_added') . ' ';
        } else {
            return [false, $Language->getText('project_admin_permissions', 'cant_add_ug_reg_users', $msg)];
        }
        if ($num_ugroups > 1) {
            $msg .= $Language->getText('project_admin_permissions', 'ignore_g');
        }
    } else {
        foreach ($ugroups as $selected_ugroup) {
            if ($selected_ugroup == $GLOBALS['UGROUP_NONE']) {
                if ($num_ugroups > 1) {
                    $msg .= $Language->getText('project_admin_permissions', 'g_nobody_ignored') . ' ';
                    continue;
                } else {
                    $msg .= $Language->getText('project_admin_permissions', 'nobody_has_no_access') . ' ';
                }
            }
            if (permission_add_ugroup($group_id, $permission_type, $object_id, $selected_ugroup)) {
                // $msg .= "+g$selected_ugroup ";
            } else {
                return [false, $Language->getText('project_admin_permissions', 'cant_add_ug', [$msg, $selected_ugroup])];
            }
        }
    }
    // If selected permission is the same as default, then don't store it!
    if (permission_equals_to_default($permission_type, $object_id)) {
        permission_clear_all($group_id, $permission_type, $object_id, false);
        $msg .= ' ' . $Language->getText('project_admin_permissions', 'def_val');
    }
    permission_add_history($group_id, $permission_type, $object_id);
    return [true, $Language->getText('project_admin_permissions', 'perm_update_success', $msg)];
}
