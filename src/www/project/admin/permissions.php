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
//type='NEWS_READ'                 id='forum_id'                   table='news_bytes'
// type='PACKAGE_READ'             id='package_id'                 table='frs_package'
// type='RELEASE_READ'             id='release_id'                 table='frs_release'
// type='WIKI_READ'                id='group_id'                   table='wiki_page'
// type='WIKIPAGE_READ'            id='id'                         table='wiki_page'
// type='WIKIATTACHMENT_READ'      id='id'                         table='wiki_attachment'
// type='TRACKER_FIELD_SUBMIT'     id='field_id_group_artifact_id' table='artifact_field'
// type='TRACKER_FIELD_READ'       id='field_id_group_artifact_id' table='artifact_field'
// type='TRACKER_FIELD_UPDATE'     id='field_id_group_artifact_id' table='artifact_field'
// type='TRACKER_ACCESS_SUBMITTER' id='group_artifact_id'          table='artifact_group_list'
// type='TRACKER_ACCESS_ASSIGNEE'  id='group_artifact_id'          table='artifact_group_list'
// type='TRACKER_ACCESS_FULL'      id='group_artifact_id'          table='artifact_group_list'
// type='TRACKER_ARTIFACT_ACCESS'  id='artifact_id'                table='artifact'

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
    if ($permission_type == 'NEWS_READ') {
        return $Language->getText('project_admin_permissions', 'news_access');
    } elseif ($permission_type == 'PACKAGE_READ') {
        return $Language->getText('project_admin_permissions', 'pack_download');
    } elseif ($permission_type == 'RELEASE_READ') {
        return $Language->getText('project_admin_permissions', 'rel_download');
    } elseif ($permission_type == 'WIKI_READ') {
        return $Language->getText('project_admin_permissions', 'wiki_access');
    } elseif ($permission_type == 'WIKIPAGE_READ') {
        return $Language->getText('project_admin_permissions', 'wiki_access');
    } elseif ($permission_type == 'WIKIATTACHMENT_READ') {
        return $Language->getText('project_admin_permissions', 'wiki_attachment_access');
    } elseif ($permission_type == 'TRACKER_FIELD_SUBMIT') {
        return $Language->getText('project_admin_permissions', 'tracker_field_submit');
    } elseif ($permission_type == 'TRACKER_FIELD_READ') {
        return $Language->getText('project_admin_permissions', 'tracker_field_read');
    } elseif ($permission_type == 'TRACKER_FIELD_UPDATE') {
        return $Language->getText('project_admin_permissions', 'tracker_field_update');
    } elseif ($permission_type == 'TRACKER_ACCESS_SUBMITTER') {
        return $Language->getText('project_admin_permissions', 'tracker_submitter_access');
    } elseif ($permission_type == 'TRACKER_ACCESS_ASSIGNEE') {
        return $Language->getText('project_admin_permissions', 'tracker_assignee_access');
    } elseif ($permission_type == 'TRACKER_ACCESS_FULL') {
        return $Language->getText('project_admin_permissions', 'tracker_full_access');
    } elseif ($permission_type == 'TRACKER_ARTIFACT_ACCESS') {
        return $Language->getText('project_admin_permissions', 'tracker_artifact_access');
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
    if ($permission_type == 'NEWS_READ') {
        return 'news';
    } elseif ($permission_type == 'PACKAGE_READ') {
        return 'package';
    } elseif ($permission_type == 'RELEASE_READ') {
        return 'release';
    } elseif ($permission_type == 'WIKI_READ') {
        return 'wiki';
    } elseif ($permission_type == 'WIKIPAGE_READ') {
        return 'wikipage';
    } elseif ($permission_type == 'WIKIATTACHMENT_READ') {
        return "wikiattachment";
    } elseif ($permission_type == 'TRACKER_FIELD_SUBMIT') {
        return 'field';
    } elseif ($permission_type == 'TRACKER_FIELD_READ') {
        return 'field';
    } elseif ($permission_type == 'TRACKER_FIELD_UPDATE') {
        return 'field';
    } elseif ($permission_type == 'TRACKER_ACCESS_SUBMITTER') {
        return 'tracker';
    } elseif ($permission_type == 'TRACKER_ACCESS_ASSIGNEE') {
        return 'tracker';
    } elseif ($permission_type == 'TRACKER_ACCESS_FULL') {
        return 'tracker';
    } elseif ($permission_type == 'TRACKER_ACCESS_FULL') {
        return 'artefact';
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
    if ($permission_type == 'NEWS_READ') {
        require_once __DIR__ . '/../../news/news_utils.php';
        return get_news_name_from_forum_id($object_id);
    } elseif ($permission_type == 'PACKAGE_READ') {
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
    } elseif (strpos($permission_type, 'TRACKER_ACCESS') === 0) {
        $group = $pm->getProject($group_id);
        $at    = new ArtifactType($group, $object_id);
        return $at->getName();
    } elseif (strpos($permission_type, 'TRACKER_FIELD') === 0) {
        $ret = "$object_id";
        if ($group = $pm->getProject($group_id)) {
            $atid = permission_extract_atid($object_id);
            $at   = new ArtifactType($group, $atid);
            $ret  = $at->getName();
            if ($ath = new ArtifactTypeHtml($group, $atid)) {
                if ($art_field_fact = new ArtifactFieldFactory($ath)) {
                    $field_id = permission_extract_field_id($object_id);
                    if ($field = $art_field_fact->getFieldFromId($field_id)) {
                        $ret = $field->getName() . ' (' . $ret . ')';
                    }
                }
            }
        }
        return $ret;
    } elseif ($permission_type == 'TRACKER_ARTIFACT_ACCESS') {
        $ret    = $object_id;
        $sql    = "SELECT group_artifact_id FROM artifact WHERE artifact_id= " . db_ei($object_id);
        $result = db_query($sql);
        if (db_numrows($result) > 0) {
            $row  = db_fetch_array($result);
            $atid = $row['group_artifact_id'];
        }
        $group = $pm->getProject($group_id);
        $at    = new ArtifactType($group, $atid);
        $a     = new Artifact($at, $object_id);
        return 'art #' . $a->getId() . ' - ' . util_unconvert_htmlspecialchars($a->getSummary());
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

    if ($permission_type == 'NEWS_READ') {
        //special case : if user has write (or admin) perms on News, he can submit news ==> he can submit private news ==> he can define news perms
        return (user_ismember($project_id, 'N1') || user_ismember($project_id, 'N2'));
    } elseif ($permission_type == 'WIKI_READ') {
        return (user_ismember($project_id, 'W2'));
    } elseif ($permission_type == 'WIKIPAGE_READ') {
        return (user_ismember($project_id, 'W2'));
    } elseif ($permission_type == 'WIKIATTACHMENT_READ') {
        return (user_ismember($project_id, 'W2'));
    } elseif (strpos($permission_type, 'TRACKER') === 0) { // Starts with 'TRACKER'
        //The object_id stored in the permission table when permission_type ='TRACKER_ARTIFACT_ACCESS'
        //corresponds to the artifact_id
        if ($permission_type == 'TRACKER_ARTIFACT_ACCESS') {
            $sql = 'SELECT group_artifact_id from artifact WHERE artifact_id = ' . db_ei($object_id);
            $res = db_query($sql);
            if ($res && db_numrows($res) == 1) {
                $row       = db_fetch_array($res);
                $object_id = $row['group_artifact_id'];
            } else {
                return false;
            }
        }

        $at = new ArtifactType($project, (int) $object_id);
        return $at->userIsAdmin();
    } elseif ($permission_type == 'PACKAGE_READ') {
        $permission_manager = FRSPermissionManager::build();

        return $permission_manager->isAdmin($project, $user);
    } elseif ($permission_type == 'RELEASE_READ') {
        $frs_package_factory = new FRSPackageFactory();

        return $frs_package_factory->userCanCreate($project_id, $user->getId());
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
 * WARNING: don't use this method to check access permission on trackers ('TRACKER_ACCESS*' and 'TRACKER_FIELD*' permission types)
 * Why? because trackers don't use default permissions, and they need an additional parameter for field permissions.
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



function permission_extract_field_id($special_id)
{
    $pos = strpos($special_id, '#');
    if ($pos === false) {
        return $special_id;
    } else {
        return substr($special_id, $pos + 1);
    }
}

function permission_extract_atid($special_id)
{
    $pos = strpos($special_id, '#');
    if ($pos === false) {
        return $special_id;
    } else {
        return substr($special_id, 0, $pos);
    }
}

function permission_build_field_id($object_id, $field_id)
{
    return $object_id . "#" . $field_id;
}

/**
 * @returns array the permissions for the ugroups
 */
function permission_get_field_tracker_ugroups_permissions($group_id, $atid, $fields)
{
    $tracker_permissions = permission_get_tracker_ugroups_permissions($group_id, $atid);
    //Anonymous can access ?
    if (
        isset($tracker_permissions[$GLOBALS['UGROUP_ANONYMOUS']])
        && isset($tracker_permissions[$GLOBALS['UGROUP_ANONYMOUS']]['permissions'])
        && count($tracker_permissions[$GLOBALS['UGROUP_ANONYMOUS']]['permissions']) > 0
    ) {
        //Do nothing
    } else {
        //We remove the id
        if (isset($tracker_permissions[$GLOBALS['UGROUP_ANONYMOUS']])) {
            unset($tracker_permissions[$GLOBALS['UGROUP_ANONYMOUS']]);
        }

        //Registered can access ?
        if (
            isset($tracker_permissions[$GLOBALS['UGROUP_REGISTERED']])
            && isset($tracker_permissions[$GLOBALS['UGROUP_REGISTERED']]['permissions'])
            && count($tracker_permissions[$GLOBALS['UGROUP_REGISTERED']]['permissions']) > 0
        ) {
            //Do nothing
        } else {
            //We remove the id
            if (isset($tracker_permissions[$GLOBALS['UGROUP_REGISTERED']])) {
                unset($tracker_permissions[$GLOBALS['UGROUP_REGISTERED']]);
            }

            //Each group can access ?
            foreach ($tracker_permissions as $key => $value) {
                if (! isset($value['permissions']) || count($value['permissions']) < 1) {
                    unset($tracker_permissions[$key]);
                }
            }
        }
    }
    $ugroups_that_can_access_to_tracker = $tracker_permissions;

    $ugroups_permissions = [];
    foreach ($fields as $field) {
        $fake_id = permission_build_field_id($atid, $field->getID());
        $ugroups = permission_get_ugroups_permissions($group_id, $fake_id, ['TRACKER_FIELD_READ', 'TRACKER_FIELD_UPDATE', 'TRACKER_FIELD_SUBMIT'], false);

        //{{{ We remove the ugroups which can't access to tracker and don't have permissions
        /*foreach($ugroups as $key => $value) {
            if (!isset($ugroups_that_can_access_to_tracker[$key]) && count($ugroups[$key]['permissions']) == 0) {
                unset($ugroups[$key]);
            }
        }*/
        //}}}

        //We store permission for the current field
        $ugroups_permissions[$field->getID()] = [
            'field' => [
                'shortname'  => $field->getName(),
                'name'       => $field->getLabel(),
                'id'         => $field->getID(),
                'link'       => '/tracker/admin/index.php?group_id=' . $group_id . '&atid=' . $atid . '&func=display_field_update&field_id=' . $field->getID(),
            ],
            'ugroups' => $ugroups,
        ];

        //{{{ We store tracker permissions
        foreach ($ugroups_permissions[$field->getID()]['ugroups'] as $key => $ugroup) {
            if (isset($tracker_permissions[$key])) {
                $ugroups_permissions[$field->getID()]['ugroups'][$key]['tracker_permissions'] = $tracker_permissions[$key]['permissions'];
            } else {
                $ugroups_permissions[$field->getID()]['ugroups'][$key]['tracker_permissions'] = [];
            }
        }
        //}}}
    }
    return $ugroups_permissions;
}

/**
 * @returns array the permissions for the ugroups
 */
function permission_get_tracker_ugroups_permissions($group_id, $object_id)
{
    return permission_get_ugroups_permissions($group_id, $object_id, ['TRACKER_ACCESS_FULL', 'TRACKER_ACCESS_ASSIGNEE', 'TRACKER_ACCESS_SUBMITTER'], false);
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
    $sql       = "SELECT u.ugroup_id, u.name, p.permission_type " .
        " FROM permissions p, ugroup u " .
        " WHERE p.ugroup_id = u.ugroup_id " .
        "       AND p.object_id = '" . $object_id . "' " .
        "       AND p.permission_type in (";
    if (count($permission_types) > 0) {
        $sql .= "'" . db_es($permission_types[0]) . "'";
        $i    = 1;
        while ($i < count($permission_types)) {
            $sql .= ",'" . db_es($permission_types[$i++]) . "'";
        }
    }
    $sql .= ")";
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
        $sql = "SELECT ug.ugroup_id, ug.name, pv.permission_type, pv.is_default " .
            " FROM permissions_values pv, ugroup ug " .
            " WHERE ug.ugroup_id = pv.ugroup_id " .
            "       AND pv.permission_type in (";
        if (count($permission_types) > 0) {
            $sql .= "'" . db_es($permission_types[0]) . "'";
            $i    = 1;
            while ($i < count($permission_types)) {
                $sql .= ",'" . db_es($permission_types[$i++]) . "'";
            }
        }
        $sql .= ")";
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
                if ($show_default_permissions && $row[3] === "1" && $use_default_permissions) {
                    $return[$row[0]]['permissions'][$row[2]] = 1;
                }
            }
        }
        //Now we look at project ugroups that have no permissions yet
        $sql        = "SELECT ugroup_id, name " .
            " FROM ugroup " .
            " WHERE group_id = '" . db_ei($group_id) . "' " .
            "       AND ugroup_id NOT IN (";
        $ugroup_ids = array_keys($return);
        if (count($ugroup_ids) > 0) {
            $sql .= "'" . db_ei($ugroup_ids[0]) . "'";
            $i    = 1;
            while ($i < count($ugroup_ids)) {
                $sql .= ",'" . db_ei($ugroup_ids[$i++]) . "'";
            }
        }
        $sql .= ")";
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

    $html .= '<p><INPUT TYPE="SUBMIT" NAME="submit" data-test="submit-form-permissions" VALUE="' . $GLOBALS['Language']->getText('project_admin_permissions', 'submit_perm') . '">';
    $html .= '<INPUT TYPE="SUBMIT" NAME="reset" VALUE="' . $GLOBALS['Language']->getText('project_admin_permissions', 'reset_to_def') . '">';
    $html .= '</FORM>';
    $html .= '<p>' . $GLOBALS['Language']->getText(
        'project_admin_permissions',
        'admins_create_modify_ug',
        [
            "/project/admin/ugroup.php?group_id=" . urlencode($group_id),
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

function permission_fetch_selection_field_without_project_admins_and_nobody(
    $permission_type,
    $object_id,
    $group_id,
    $htmlname = 'ugroups',
    $disabled = false,
) {
    return permission_fetch_selection_field($permission_type, $object_id, $group_id, $htmlname, $disabled, false, false);
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
        $html .= "<p><b>" . $GLOBALS['Language']->getText('global', 'error') . "</b>: " . $GLOBALS['Language']->getText('project_admin_permissions', 'perm_type_not_def', $permission_type);
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

    $sql = "SELECT *
              FROM ugroup
              WHERE group_id=" . db_ei($group_id) . "
                OR ugroup_id IN (" . $predefined_ugroups . ")
            ORDER BY ugroup_id";

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
        $htmlname . "[]",
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

function permission_copy_tracker_and_field_permissions($from, $to, $group_id_from, $group_id_to, $ugroup_mapping = false)
{
    $result = true;

    //We remove ugroups if 'from' and 'to' are not part of the same project
    $and_remove_ugroups = "";
    if ($group_id_from != $group_id_to) {
        $and_remove_ugroups = " AND ugroup_id <= '100' ";
    }

    $to   = db_es($to);
    $from = db_es($from);

    //Copy of tracker permissions
    $sql = <<<EOS
INSERT INTO `permissions` ( `permission_type`, `object_id`, `ugroup_id`)
    SELECT `permission_type`, '$to', `ugroup_id`
    FROM `permissions`
    WHERE `object_id` = '$from' $and_remove_ugroups
EOS;

    $res = db_query($sql);
    if (! $res) {
        $result = false;
    }

   //Copy of field permissions
    $sql = <<<EOS
INSERT INTO `permissions` ( `permission_type`, `object_id`, `ugroup_id`)
    SELECT `permission_type`, CONCAT('$to#',RIGHT(`object_id`, LENGTH(`object_id`)-LENGTH('$from#'))), `ugroup_id`
    FROM `permissions`
    WHERE `object_id` LIKE '$from#%' $and_remove_ugroups
EOS;

    $res = db_query($sql);
    if (! $res) {
        $result = false;
    }

    //look after special groups in $ugroup_mapping
    if (($group_id_from != $group_id_to) && ($ugroup_mapping !== false)) {
        foreach ($ugroup_mapping as $key => $val) {
            $sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) " .
            "SELECT permission_type, $to, " . db_ei($val) . " " .
            "FROM permissions " .
            "WHERE object_id = '$from' AND ugroup_id = '" . db_ei($key) . "'";
            $res = db_query($sql);
            if (! $res) {
                     $result = false;
            }

            $sql = "INSERT INTO permissions (permission_type,object_id,ugroup_id) " .
            "SELECT permission_type, CONCAT('$to#',RIGHT(`object_id`, LENGTH(`object_id`)-LENGTH('$from#'))), " . db_ei($val) . " " .
            "FROM permissions " .
            "WHERE object_id LIKE '$from#%' AND ugroup_id = '" . db_ei($key) . "'";
            $res = db_query($sql);
            if (! $res) {
                     $result = false;
            }
        }
    }

    //look for missing ugroups
    $sql            = "SELECT count(ugroup_id) FROM `permissions` WHERE permission_type LIKE 'TRACKER_%' AND ( `object_id` = '$from' OR `object_id` LIKE '$from#%')";
    $res            = db_query($sql);
    $row            = db_fetch_array($res);
    $nb_ugroup_from = $row[0];
    $sql            = "SELECT count(ugroup_id) FROM `permissions` WHERE permission_type LIKE 'TRACKER_%' AND ( `object_id` = '$to' OR `object_id` LIKE '$to#%')";
    $res            = db_query($sql);
    $row            = db_fetch_array($res);
    $nb_ugroup_to   = $row[0];
    if (($nb_ugroup_from - $nb_ugroup_to) != 0) {
        $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_ug_during_copy'));
    }

    if (! $result) {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'error'));
    }
    return $result;
}


function permission_clear_all_tracker($group_id, $object_id)
{
    permission_clear_all($group_id, 'TRACKER_ACCESS_FULL', $object_id, false);
    permission_clear_all($group_id, 'TRACKER_ACCESS_ASSIGNEE', $object_id, false);
    permission_clear_all($group_id, 'TRACKER_ACCESS_SUBMITTER', $object_id, false);
}

function permission_clear_all_fields_tracker($group_id, $tracker_id, $field_id)
{
    $object_id = permission_build_field_id($tracker_id, $field_id);
    permission_clear_all($group_id, 'TRACKER_FIELD_SUBMIT', $object_id, false);
    permission_clear_all($group_id, 'TRACKER_FIELD_READ', $object_id, false);
    permission_clear_all($group_id, 'TRACKER_FIELD_UPDATE', $object_id, false);
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

function permission_clear_ugroup_tracker($group_id, $ugroup_id, $object_id)
{
    permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_FULL', $ugroup_id, $object_id);//TODO: traitements des erreurs
    permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_SUBMITTER', $ugroup_id, $object_id);//TODO: traitements des erreurs
    permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_ASSIGNEE', $ugroup_id, $object_id);//TODO: traitements des erreurs
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
    $sql = "INSERT INTO permissions (permission_type, object_id, ugroup_id) VALUES ('" . db_es($permission_type) . "', '" . db_es($object_id) . "', " . db_ei($ugroup_id) . ")";
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
            $msg .= $Language->getText('project_admin_permissions', 'all_registered_users_added') . " ";
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
                    $msg .= $Language->getText('project_admin_permissions', 'g_nobody_ignored') . " ";
                    continue;
                } else {
                    $msg .= $Language->getText('project_admin_permissions', 'nobody_has_no_access') . " ";
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

function permission_get_input_value_from_permission($perm)
{
    $ret = false;
    switch ($perm) {
        case 'TRACKER_FIELD_SUBMIT':
            $ret = ['submit' => 'on'];
            break;
        case 'TRACKER_FIELD_READ':
            $ret = ['others' => '0'];
            break;
        case 'TRACKER_FIELD_UPDATE':
            $ret = ['others' => '1'];
            break;
        default:
            //Do nothing
            break;
    }
    return $ret;
}

function permission_process_update_fields_permissions($group_id, $atid, $fields, $permissions_wanted_by_user)
{
    //The actual permissions
    $stored_ugroups_permissions = permission_get_field_tracker_ugroups_permissions($group_id, $atid, $fields);
    $permissions_updated        = false;

    //some special ugroup names
    $anonymous_name  = NameTranslator::getUserGroupDisplayKey(ugroup_get_name_from_id($GLOBALS['UGROUP_ANONYMOUS']));
    $registered_name = NameTranslator::getUserGroupDisplayKey(ugroup_get_name_from_id($GLOBALS['UGROUP_REGISTERED']));

    //We process the request
    foreach ($permissions_wanted_by_user as $field_id => $ugroups_permissions) {
        if (
            is_numeric($field_id)
            && isset($stored_ugroups_permissions[$field_id])
            && $stored_ugroups_permissions[$field_id]['field']['shortname'] !== "comment_type_id"
        ) { //comment_type is not a "real" field
            $field_name                            = $stored_ugroups_permissions[$field_id]['field']['shortname'];
            $the_field_can_be_submitted_or_updated = $field_name !== "artifact_id" && $field_name !== "submitted_by" && $field_name !== "open_date";
            $the_field_can_be_submitted            = $the_field_can_be_submitted_or_updated; //(And add here those who can only be submitted)
            $the_field_can_be_updated              = $the_field_can_be_submitted_or_updated; //(And add here those who can only be updated)

            //artifact_id#field_id
            $fake_object_id = permission_build_field_id($atid, $field_id);

            //small variables for history
            $add_submit_to_history = false;
            $add_read_to_history   = false;
            $add_update_to_history = false;

            //We look for anonymous and registered users' permissions, both in the user's request and in the db
            $user_set_anonymous_to_submit  = isset($ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]) &&
                isset($ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]['submit']) &&
                $ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]['submit'] === "on";
            $user_set_anonymous_to_read    = isset($ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]) &&
                isset($ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]['others']) &&
                $ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]['others'] === "0";
            $user_set_anonymous_to_update  = isset($ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]) &&
                isset($ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]['others']) &&
                $ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]['others'] === "1";
            $user_set_registered_to_submit = isset($ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]) &&
                isset($ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['submit']) &&
                $ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['submit'] === "on";
            $user_set_registered_to_read   = isset($ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]) &&
                isset($ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['others']) &&
                $ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['others'] === "0";
            $user_set_registered_to_update = isset($ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]) &&
                isset($ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['others']) &&
                $ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['others'] === "1";

            $anonymous_is_already_set_to_submit  = isset($stored_ugroups_permissions[$field_id]['ugroups'][$GLOBALS['UGROUP_ANONYMOUS']]['permissions']['TRACKER_FIELD_SUBMIT']);
            $anonymous_is_already_set_to_read    = isset($stored_ugroups_permissions[$field_id]['ugroups'][$GLOBALS['UGROUP_ANONYMOUS']]['permissions']['TRACKER_FIELD_READ']);
            $anonymous_is_already_set_to_update  = isset($stored_ugroups_permissions[$field_id]['ugroups'][$GLOBALS['UGROUP_ANONYMOUS']]['permissions']['TRACKER_FIELD_UPDATE']);
            $registered_is_already_set_to_submit = isset($stored_ugroups_permissions[$field_id]['ugroups'][$GLOBALS['UGROUP_REGISTERED']]['permissions']['TRACKER_FIELD_SUBMIT']);
            $registered_is_already_set_to_read   = isset($stored_ugroups_permissions[$field_id]['ugroups'][$GLOBALS['UGROUP_REGISTERED']]['permissions']['TRACKER_FIELD_READ']);
            $registered_is_already_set_to_update = isset($stored_ugroups_permissions[$field_id]['ugroups'][$GLOBALS['UGROUP_REGISTERED']]['permissions']['TRACKER_FIELD_UPDATE']);

            //ANONYMOUS
            ////////////////////////////////////////////////////////////////
            //Firstly we set permissions for anonymous users
            if (isset($ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']])) {
                $ugroup_permissions = $ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']];

                //SUBMIT Permission
                //-----------------
                if ($the_field_can_be_submitted && ! $anonymous_is_already_set_to_submit && $user_set_anonymous_to_submit) {
                    //if the ugroup is anonymous, we have to erase submit permissions for other ugroups
                    foreach ($stored_ugroups_permissions[$field_id]['ugroups'] as $stored_ugroup_id => $stored_ugroup_permissions) {
                        if ($stored_ugroup_id === $GLOBALS['UGROUP_ANONYMOUS']) {
                            permission_add_ugroup($group_id, 'TRACKER_FIELD_SUBMIT', $fake_object_id, $stored_ugroup_id);
                            $add_submit_to_history              = true;
                            $anonymous_is_already_set_to_submit = true;
                        } else {
                            if (
                                isset($stored_ugroup_permissions['permissions']['TRACKER_FIELD_SUBMIT'])
                                && (! isset($ugroups_permissions[$stored_ugroup_id])
                                    || ! isset($ugroups_permissions[$stored_ugroup_id]['submit'])
                                    || $ugroups_permissions[$stored_ugroup_id]['submit'] !== "on")
                            ) {
                                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_submit', [$stored_ugroup_permissions['ugroup']['name'], $anonymous_name]));
                                permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_SUBMIT', $stored_ugroup_id, $fake_object_id);
                                $add_submit_to_history = true;
                            }
                        }
                    }
                } elseif ($anonymous_is_already_set_to_submit && ! $user_set_anonymous_to_submit) {
                    permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_SUBMIT', $GLOBALS['UGROUP_ANONYMOUS'], $fake_object_id);
                    $add_submit_to_history              = true;
                    $anonymous_is_already_set_to_submit = false;
                }

                //UPDATE Permission
                //---------------
                if ($the_field_can_be_updated && ! $anonymous_is_already_set_to_update && $user_set_anonymous_to_update) {
                    //if the ugroup is anonymous, we have to erase submt permissions for other ugroups
                    foreach ($stored_ugroups_permissions[$field_id]['ugroups'] as $stored_ugroup_id => $stored_ugroup_permissions) {
                        if ($stored_ugroup_id === $GLOBALS['UGROUP_ANONYMOUS']) {
                            permission_add_ugroup($group_id, 'TRACKER_FIELD_UPDATE', $fake_object_id, $stored_ugroup_id);
                            $add_update_to_history              = true;
                            $anonymous_is_already_set_to_update = true;
                        } else {
                            if (
                                ! isset($ugroups_permissions[$stored_ugroup_id])
                                || ! isset($ugroups_permissions[$stored_ugroup_id]['others'])
                                || $ugroups_permissions[$stored_ugroup_id]['others'] !== "100"
                            ) {
                                if (isset($stored_ugroup_permissions['permissions']['TRACKER_FIELD_UPDATE'])) {
                                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_update', [$stored_ugroup_permissions['ugroup']['name'], $anonymous_name]));
                                    permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_UPDATE', $stored_ugroup_id, $fake_object_id);
                                    $add_update_to_history = true;
                                }
                                if (isset($stored_ugroup_permissions['permissions']['TRACKER_FIELD_READ'])) {
                                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_update', [$stored_ugroup_permissions['ugroup']['name'], $anonymous_name]));
                                    permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_READ', $stored_ugroup_id, $fake_object_id);
                                    $add_read_to_history = true;
                                }
                            }
                        }
                    }
                } elseif ($anonymous_is_already_set_to_update && ! $user_set_anonymous_to_update) {
                    permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_UPDATE', $GLOBALS['UGROUP_ANONYMOUS'], $fake_object_id);
                    $add_update_to_history              = true;
                    $anonymous_is_already_set_to_update = false;
                }

                //READ Permission
                //---------------
                if (! $anonymous_is_already_set_to_read && $user_set_anonymous_to_read) {
                    //if the ugroup is anonymous, we have to erase submit permissions for other ugroups
                    foreach ($stored_ugroups_permissions[$field_id]['ugroups'] as $stored_ugroup_id => $stored_ugroup_permissions) {
                        if ($stored_ugroup_id === $GLOBALS['UGROUP_ANONYMOUS']) {
                            permission_add_ugroup($group_id, 'TRACKER_FIELD_READ', $fake_object_id, $stored_ugroup_id);
                            $add_read_to_history              = true;
                            $anonymous_is_already_set_to_read = true;
                        } else {
                            if (
                                ! isset($ugroups_permissions[$stored_ugroup_id])
                                || ! isset($ugroups_permissions[$stored_ugroup_id]['others'])
                                || $ugroups_permissions[$stored_ugroup_id]['others'] !== "100"
                            ) {
                                if (isset($stored_ugroup_permissions['permissions']['TRACKER_FIELD_READ'])) {
                                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_read', [$stored_ugroup_permissions['ugroup']['name'], $anonymous_name]));
                                    permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_READ', $stored_ugroup_id, $fake_object_id);
                                    $add_read_to_history = true;
                                }
                            }
                        }
                    }
                } elseif ($anonymous_is_already_set_to_read && ! $user_set_anonymous_to_read) {
                    permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_READ', $GLOBALS['UGROUP_ANONYMOUS'], $fake_object_id);
                    $add_read_to_history              = true;
                    $anonymous_is_already_set_to_read = false;
                }
            }

            //REGISTERED
            ////////////////////////////////////////////////////////////////
            //Secondly we set permissions for registered users
            if (isset($ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']])) {
                $ugroup_permissions = $ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']];

                //SUBMIT Permission
                //-----------------
                if ($the_field_can_be_submitted && ! $registered_is_already_set_to_submit && $user_set_registered_to_submit) {
                    //if the ugroup is registered, we have to:
                    // 1. check consistency with current permissions for anonymous users
                    if ($user_set_anonymous_to_submit || $anonymous_is_already_set_to_submit) {
                        $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_submit', [$stored_ugroups_permissions[$field_id]['ugroups'][$GLOBALS['UGROUP_REGISTERED']]['ugroup']['name'], $anonymous_name]));
                    } else {
                        // 2. erase submit permissions for other ugroups
                        foreach ($stored_ugroups_permissions[$field_id]['ugroups'] as $stored_ugroup_id => $stored_ugroup_permissions) {
                            if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                                permission_add_ugroup($group_id, 'TRACKER_FIELD_SUBMIT', $fake_object_id, $stored_ugroup_id);
                                $add_submit_to_history               = true;
                                $registered_is_already_set_to_submit = true;
                            } elseif ($stored_ugroup_id !== $GLOBALS['UGROUP_ANONYMOUS']) {
                                if (
                                    isset($stored_ugroup_permissions['permissions']['TRACKER_FIELD_SUBMIT'])
                                    && (! isset($ugroups_permissions[$stored_ugroup_id])
                                        || ! isset($ugroups_permissions[$stored_ugroup_id]['submit'])
                                        || $ugroups_permissions[$stored_ugroup_id]['submit'] !== "on")
                                ) {
                                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_regis_submit', [$stored_ugroup_permissions['ugroup']['name'], $registered_name]));
                                    permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_SUBMIT', $stored_ugroup_id, $fake_object_id);
                                    $add_submit_to_history = true;
                                }
                            }
                        }
                    }
                } elseif ($registered_is_already_set_to_submit && ! $user_set_registered_to_submit) {
                    permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_SUBMIT', $GLOBALS['UGROUP_REGISTERED'], $fake_object_id);
                    $add_submit_to_history               = true;
                    $registered_is_already_set_to_submit = false;
                }

                //UPDATE Permission
                //---------------
                if ($the_field_can_be_updated && ! $registered_is_already_set_to_update && $user_set_registered_to_update) {
                    //if the ugroup is registered, we have to:
                    // 1. check consistency with current permissions for anonymous users
                    if ($user_set_anonymous_to_update || $anonymous_is_already_set_to_update) {
                        $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_update', [$stored_ugroups_permissions[$field_id]['ugroups'][$GLOBALS['UGROUP_REGISTERED']]['ugroup']['name'], $anonymous_name]));
                    } else {
                        // 2. erase update permissions for other ugroups
                        foreach ($stored_ugroups_permissions[$field_id]['ugroups'] as $stored_ugroup_id => $stored_ugroup_permissions) {
                            if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                                permission_add_ugroup($group_id, 'TRACKER_FIELD_UPDATE', $fake_object_id, $stored_ugroup_id);
                                $add_update_to_history               = true;
                                $registered_is_already_set_to_update = true;
                            } elseif ($stored_ugroup_id !== $GLOBALS['UGROUP_ANONYMOUS']) { //ugroups other than anonymous
                                if (
                                    ! isset($ugroups_permissions[$stored_ugroup_id])
                                    || ! isset($ugroups_permissions[$stored_ugroup_id]['others'])
                                    || $ugroups_permissions[$stored_ugroup_id]['others'] !== "100"
                                ) {
                                    if (isset($stored_ugroup_permissions['permissions']['TRACKER_FIELD_UPDATE'])) {
                                        $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_regis_update', [$stored_ugroup_permissions['ugroup']['name'], $registered_name]));
                                        permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_UPDATE', $stored_ugroup_id, $fake_object_id);
                                        $add_update_to_history = true;
                                    }
                                    if (isset($stored_ugroup_permissions['permissions']['TRACKER_FIELD_READ'])) {
                                        $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_regis_update', [$stored_ugroup_permissions['ugroup']['name'], $registered_name]));
                                        permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_READ', $stored_ugroup_id, $fake_object_id);
                                        $add_read_to_history = true;
                                    }
                                }
                            }
                        }
                    }
                } elseif ($registered_is_already_set_to_update && ! $user_set_registered_to_update) {
                    permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_UPDATE', $GLOBALS['UGROUP_REGISTERED'], $fake_object_id);
                    $add_update_to_history               = true;
                    $registered_is_already_set_to_update = false;
                }

                //READ Permission
                //---------------
                if (! $registered_is_already_set_to_read && $user_set_registered_to_read) {
                    //if the ugroup is registered, we have to:
                    // 1. check consistency with current permissions for anonymous users
                    if ($user_set_anonymous_to_read || $anonymous_is_already_set_to_read || $anonymous_is_already_set_to_update) {
                        $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_read', [$stored_ugroups_permissions[$field_id]['ugroups'][$GLOBALS['UGROUP_REGISTERED']]['ugroup']['name'], $anonymous_name]));
                    } else {
                        // 2. erase read permissions for other ugroups
                        foreach ($stored_ugroups_permissions[$field_id]['ugroups'] as $stored_ugroup_id => $stored_ugroup_permissions) {
                            if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                                permission_add_ugroup($group_id, 'TRACKER_FIELD_READ', $fake_object_id, $stored_ugroup_id);
                                $add_read_to_history               = true;
                                $registered_is_already_set_to_read = true;
                            } elseif ($stored_ugroup_id !== $GLOBALS['UGROUP_ANONYMOUS']) { //ugroups other than anonymous
                                if (
                                    ! isset($ugroups_permissions[$stored_ugroup_id])
                                    || ! isset($ugroups_permissions[$stored_ugroup_id]['others'])
                                    || $ugroups_permissions[$stored_ugroup_id]['others'] !== "100"
                                ) {
                                    if (isset($stored_ugroup_permissions['permissions']['TRACKER_FIELD_READ'])) {
                                        $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_regis_read', [$stored_ugroup_permissions['ugroup']['name'], $registered_name]));
                                        permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_READ', $stored_ugroup_id, $fake_object_id);
                                        $add_read_to_history = true;
                                    }
                                }
                            }
                        }
                    }
                } elseif ($registered_is_already_set_to_read && ! $user_set_registered_to_read) {
                    permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_READ', $GLOBALS['UGROUP_REGISTERED'], $fake_object_id);
                    $registered_is_already_set_to_read = false;
                }
            }

            //OTHER INSIGNIFIANT UGROUPS
            ////////////////////////////////////////////////////////////////
            foreach ($ugroups_permissions as $ugroup_id => $ugroup_permissions) {
                if (is_numeric($ugroup_id) && $ugroup_id != $GLOBALS['UGROUP_REGISTERED'] && $ugroup_id != $GLOBALS['UGROUP_ANONYMOUS']) {
                    $name_of_ugroup = $stored_ugroups_permissions[$field_id]['ugroups'][$ugroup_id]['ugroup']['name'];

                    //SUBMIT Permission
                    //-----------------
                    if (
                        $the_field_can_be_submitted && ! isset($stored_ugroups_permissions[$field_id]['ugroups'][$ugroup_id]['permissions']['TRACKER_FIELD_SUBMIT'])
                        && isset($ugroup_permissions['submit'])
                        && $ugroup_permissions['submit'] === "on"
                    ) {
                        //if the ugroup is not anonymous and not registered, we have to:
                        // check consistency with current permissions for anonymous users
                        // and current permissions for registered users
                        if ($user_set_anonymous_to_submit || $anonymous_is_already_set_to_submit) {
                            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_submit', [$name_of_ugroup, $anonymous_name]));
                        } elseif ($user_set_registered_to_submit || $registered_is_already_set_to_submit) {
                            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_regis_submit', [$name_of_ugroup, $registered_name]));
                        } else {
                            permission_add_ugroup($group_id, 'TRACKER_FIELD_SUBMIT', $fake_object_id, $ugroup_id);
                            $add_submit_to_history = true;
                        }
                    } elseif (
                        isset($stored_ugroups_permissions[$field_id]['ugroups'][$ugroup_id]['permissions']['TRACKER_FIELD_SUBMIT'])
                              && isset($ugroup_permissions['submit'])
                              && $ugroup_permissions['submit'] !== "on"
                    ) {
                        //If we don't have already clear the permissions
                        if (! $user_set_anonymous_to_submit && ! $user_set_registered_to_submit) {
                            permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_SUBMIT', $ugroup_id, $fake_object_id);
                            $add_submit_to_history = true;
                        }
                    }

                    //UPDATE Permission
                    //-----------------
                    if (
                        $the_field_can_be_updated && ! isset($stored_ugroups_permissions[$field_id]['ugroups'][$ugroup_id]['permissions']['TRACKER_FIELD_UPDATE'])
                        && isset($ugroup_permissions['others'])
                        && $ugroup_permissions['others'] === "1"
                    ) {
                        //if the ugroup is not anonymous and not registered, we have to:
                        // check consistency with current permissions for anonymous users
                        // and current permissions for registered users
                        if ($user_set_anonymous_to_update || $anonymous_is_already_set_to_update) {
                            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_update', [$name_of_ugroup, $anonymous_name]));
                        } elseif ($user_set_registered_to_update || $registered_is_already_set_to_update) {
                            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_regis_update', [$name_of_ugroup, $registered_name]));
                        } else {
                            permission_add_ugroup($group_id, 'TRACKER_FIELD_UPDATE', $fake_object_id, $ugroup_id);
                            $add_update_to_history = true;
                        }
                    } elseif (
                        isset($stored_ugroups_permissions[$field_id]['ugroups'][$ugroup_id]['permissions']['TRACKER_FIELD_UPDATE'])
                              && isset($ugroup_permissions['others'])
                              && $ugroup_permissions['others'] !== "1"
                    ) {
                        //If we don't have already clear the permissions
                        if (! $user_set_anonymous_to_update && ! $user_set_registered_to_update) {
                            permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_UPDATE', $ugroup_id, $fake_object_id);
                            $add_update_to_history = true;
                        }
                    }

                    //READ Permission
                    //-----------------
                    if (
                        ! isset($stored_ugroups_permissions[$field_id]['ugroups'][$ugroup_id]['permissions']['TRACKER_FIELD_READ'])
                        && isset($ugroup_permissions['others'])
                        && $ugroup_permissions['others'] === "0"
                    ) {
                        //if the ugroup is not anonymous and not registered, we have to:
                        // check consistency with current permissions for anonymous users
                        // and current permissions for registered users
                        if ($user_set_anonymous_to_read || $anonymous_is_already_set_to_read) {
                            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_read', [$name_of_ugroup, $anonymous_name]));
                        } elseif ($user_set_registered_to_read || $registered_is_already_set_to_read) {
                            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_regis_read', [$name_of_ugroup, $registered_name]));
                        } elseif ($user_set_anonymous_to_update || $anonymous_is_already_set_to_update) {
                            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_anon_update', [$name_of_ugroup, $anonymous_name]));
                        } elseif ($user_set_registered_to_update || $registered_is_already_set_to_update) {
                            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'ignore_g_regis_update', [$name_of_ugroup, $registered_name]));
                        } else {
                            permission_add_ugroup($group_id, 'TRACKER_FIELD_READ', $fake_object_id, $ugroup_id);
                            $add_read_to_history = true;
                        }
                    } elseif (
                        isset($stored_ugroups_permissions[$field_id]['ugroups'][$ugroup_id]['permissions']['TRACKER_FIELD_READ'])
                              && isset($ugroup_permissions['others'])
                              && $ugroup_permissions['others'] !== "0"
                    ) {
                        //If we don't have already clear the permissions
                        if (! $user_set_anonymous_to_read && ! $user_set_registered_to_read) {
                            permission_clear_ugroup_object($group_id, 'TRACKER_FIELD_READ', $ugroup_id, $fake_object_id);
                            $add_read_to_history = true;
                        }
                    }
                }
            }

            //history
            if ($add_submit_to_history) {
                permission_add_history($group_id, 'TRACKER_FIELD_SUBMIT', $fake_object_id);
            }
            if ($add_read_to_history) {
                permission_add_history($group_id, 'TRACKER_FIELD_READ', $fake_object_id);
            }
            if ($add_update_to_history) {
                permission_add_history($group_id, 'TRACKER_FIELD_UPDATE', $fake_object_id);
            }
            if (! $permissions_updated && ($add_submit_to_history || $add_read_to_history || $add_update_to_history)) {
                $permissions_updated = true;
            }
        }
    }
    //feedback
    if ($permissions_updated) {
        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('project_admin_userperms', 'perm_upd'));
    }
}

function permission_process_update_tracker_permissions($group_id, $atid, $permissions_wanted_by_user)
{
    //The user want to update permissions for the tracker.
    //We look into the request for specials variable
    $prefixe_expected     = 'permissions_';
    $len_prefixe_expected = strlen($prefixe_expected);

    //some special ugroup names
    $anonymous_name  = NameTranslator::getUserGroupDisplayKey(ugroup_get_name_from_id($GLOBALS['UGROUP_ANONYMOUS']));
    $registered_name = NameTranslator::getUserGroupDisplayKey(ugroup_get_name_from_id($GLOBALS['UGROUP_REGISTERED']));

    //small variables for history
    $add_full_to_history      = false;
    $add_assignee_to_history  = false;
    $add_submitter_to_history = false;

    //The actual permissions
    $stored_ugroups_permissions = permission_get_tracker_ugroups_permissions($group_id, $atid);

    //We look for anonymous and registered users' permissions, both in the user's request and in the db
    $user_set_anonymous_to_fullaccess        = isset($_REQUEST[$prefixe_expected . $GLOBALS['UGROUP_ANONYMOUS']]) && $_REQUEST[$prefixe_expected . $GLOBALS['UGROUP_ANONYMOUS']] === "0";
    $user_set_registered_to_fullaccess       = isset($_REQUEST[$prefixe_expected . $GLOBALS['UGROUP_REGISTERED']]) && $_REQUEST[$prefixe_expected . $GLOBALS['UGROUP_ANONYMOUS']] === "0";
    $anonymous_is_already_set_to_fullaccess  = isset($stored_ugroups_permissions[$GLOBALS['UGROUP_ANONYMOUS']]['permissions']['TRACKER_ACCESS_FULL']);
    $registered_is_already_set_to_fullaccess = isset($stored_ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['permissions']['TRACKER_ACCESS_FULL']);
    $registered_is_already_set_to_assignee   = isset($stored_ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['permissions']['TRACKER_ACCESS_ASSIGNEE']);
    $registered_is_already_set_to_submitter  = isset($stored_ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['permissions']['TRACKER_ACCESS_SUBMITTER']);
    //ANONYMOUS
    ////////////////////////////////////////////////////////////////
    if (isset($_REQUEST[$prefixe_expected . $GLOBALS['UGROUP_ANONYMOUS']])) {
        switch ($_REQUEST[$prefixe_expected . $GLOBALS['UGROUP_ANONYMOUS']]) {
            case 0:
                //TRACKER_ACCESS_FULL
                //-------------------
                if (! $anonymous_is_already_set_to_fullaccess) {
                    foreach ($stored_ugroups_permissions as $stored_ugroup_id => $stored_ugroup_permissions) {
                        if ($stored_ugroup_id === $GLOBALS['UGROUP_ANONYMOUS']) {
                            permission_add_ugroup($group_id, 'TRACKER_ACCESS_FULL', $atid, $stored_ugroup_id);
                            $add_full_to_history                    = true;
                            $anonymous_is_already_set_to_fullaccess = true;
                        } else {
                            //We remove permissions for others ugroups
                            if (
                                count($stored_ugroup_permissions['permissions']) > 0
                                && (! isset($_REQUEST[$prefixe_expected . $stored_ugroup_id]) || $_REQUEST[$prefixe_expected . $stored_ugroup_id] != 100)
                            ) {
                                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_anon_full', [$stored_ugroup_permissions['ugroup']['name'], $anonymous_name]));
                                if (isset($stored_ugroup_permissions['permissions']['TRACKER_ACCESS_FULL'])) {
                                    permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_FULL', $stored_ugroup_id, $atid);
                                    $add_full_to_history = true;
                                    if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                                        $registered_is_already_set_to_fullaccess = false;
                                    }
                                }
                                if (isset($stored_ugroup_permissions['permissions']['TRACKER_ACCESS_ASSIGNEE'])) {
                                    permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_ASSIGNEE', $stored_ugroup_id, $atid);
                                    $add_assignee_to_history = true;
                                    if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                                        $registered_is_already_set_to_assignee = false;
                                    }
                                }
                                if (isset($stored_ugroup_permissions['permissions']['TRACKER_ACCESS_SUBMITTER'])) {
                                    permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_SUBMITTER', $stored_ugroup_id, $atid);
                                    $add_submitter_to_history = true;
                                    if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                                        $registered_is_already_set_to_submitter = false;
                                    }
                                }
                            }
                        }
                    }
                }
                break;
            case 1:
                //TRACKER_ACCESS_ASSIGNEE
                //-----------------------
                //forbidden, do nothing
                break;
            case 2:
                //TRACKER_ACCESS_SUBMITTER
                //------------------------
                //forbidden, do nothing
                break;
            case 3:
                //TRACKER_ACCESS_SUBMITTER && TRACKER_ACCESS_ASSIGNEE
                //---------------------------------------------------
                //forbidden, do nothing
                break;
            case 100:
                //NO ACCESS
                //---------
                if ($anonymous_is_already_set_to_fullaccess) {
                    permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_FULL', $GLOBALS['UGROUP_ANONYMOUS'], $atid);
                    $add_submitter_to_history               = true;
                    $anonymous_is_already_set_to_fullaccess = false;
                }
                break;
            default:
                //do nothing
                break;
        }
    }

    //REGISTERED
    ////////////////////////////////////////////////////////////////
    if (isset($_REQUEST[$prefixe_expected . $GLOBALS['UGROUP_REGISTERED']])) {
        switch ($_REQUEST[$prefixe_expected . $GLOBALS['UGROUP_REGISTERED']]) {
            case 0:
                //TRACKER_ACCESS_FULL
                //-------------------
                if (! $registered_is_already_set_to_fullaccess) {
                    //It is not necessary to process if the anonymous has full access
                    if ($anonymous_is_already_set_to_fullaccess) {
                        $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_anon_full', [$stored_ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['ugroup']['name'], $anonymous_name]));
                    } else {
                        foreach ($stored_ugroups_permissions as $stored_ugroup_id => $stored_ugroup_permissions) {
                            if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                                //We remove old permissions
                                if ($registered_is_already_set_to_assignee) {
                                    permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_ASSIGNEE', $stored_ugroup_id, $atid);
                                    $add_assignee_to_history               = true;
                                    $registered_is_already_set_to_assignee = false;
                                }
                                if ($registered_is_already_set_to_submitter) {
                                    permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_SUBMITTER', $stored_ugroup_id, $atid);
                                    $add_submitter_to_history               = true;
                                    $registered_is_already_set_to_submitter = false;
                                }
                                permission_add_ugroup($group_id, 'TRACKER_ACCESS_FULL', $atid, $stored_ugroup_id);
                                $add_full_to_history                     = true;
                                $registered_is_already_set_to_fullaccess = true;
                            } elseif ($stored_ugroup_id !== $GLOBALS['UGROUP_ANONYMOUS']) { //ugroups other than anonymous
                                //We remove permissions for others ugroups
                                if (
                                    count($stored_ugroup_permissions['permissions']) > 0
                                    && (! isset($_REQUEST[$prefixe_expected . $stored_ugroup_id]) || $_REQUEST[$prefixe_expected . $stored_ugroup_id] != 100)
                                ) {
                                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_full', [$stored_ugroup_permissions['ugroup']['name'], $registered_name]));
                                    if (isset($stored_ugroup_permissions['permissions']['TRACKER_ACCESS_FULL'])) {
                                        permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_FULL', $stored_ugroup_id, $atid);
                                        $add_full_to_history = true;
                                    }
                                    if (isset($stored_ugroup_permissions['permissions']['TRACKER_ACCESS_ASSIGNEE'])) {
                                        permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_ASSIGNEE', $stored_ugroup_id, $atid);
                                        $add_assignee_to_history = true;
                                    }
                                    if (isset($stored_ugroup_permissions['permissions']['TRACKER_ACCESS_SUBMITTER'])) {
                                        permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_SUBMITTER', $stored_ugroup_id, $atid);
                                        $add_submitter_to_history = true;
                                    }
                                }
                            }
                        }
                    }
                }
                break;
            case 1:
                //TRACKER_ACCESS_ASSIGNEE
                //-----------------------
                if (! $registered_is_already_set_to_assignee) {
                    //It is not necessary to process if the anonymous has full access (anon can't have assignee or submitter access)
                    if ($anonymous_is_already_set_to_fullaccess) {
                        $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_anon_full', [$stored_ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['ugroup']['name'], $anonymous_name]));
                    } else {
                        foreach ($stored_ugroups_permissions as $stored_ugroup_id => $stored_ugroup_permissions) {
                            if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                                //We remove old permissions
                                if ($registered_is_already_set_to_fullaccess) {
                                    permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_FULL', $stored_ugroup_id, $atid);
                                    $add_full_to_history                     = true;
                                    $registered_is_already_set_to_fullaccess = false;
                                }
                                if ($registered_is_already_set_to_submitter) {
                                    permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_SUBMITTER', $stored_ugroup_id, $atid);
                                    $add_submitter_to_history               = true;
                                    $registered_is_already_set_to_submitter = false;
                                }
                                permission_add_ugroup($group_id, 'TRACKER_ACCESS_ASSIGNEE', $atid, $stored_ugroup_id);
                                $registered_is_already_set_to_assignee = true;
                            } elseif ($stored_ugroup_id !== $GLOBALS['UGROUP_ANONYMOUS']) { //ugroups other than anonymous
                                //We remove permissions for others ugroups if they have assignee
                                if (
                                    isset($stored_ugroup_permissions['permissions']['TRACKER_ACCESS_ASSIGNEE']) && ! isset($stored_ugroup_permissions['permissions']['TRACKER_ACCESS_SUBMITTER'])
                                    && (! isset($_REQUEST[$prefixe_expected . $stored_ugroup_id]) || $_REQUEST[$prefixe_expected . $stored_ugroup_id] != 100)
                                ) {
                                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_assignee', [$stored_ugroup_permissions['ugroup']['name'], $registered_name]));
                                    permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_ASSIGNEE', $stored_ugroup_id, $atid);
                                    $add_assignee_to_history = true;
                                }
                            }
                        }
                    }
                }
                break;
            case 2:
                //TRACKER_ACCESS_SUBMITTER
                //------------------------
                if (! $registered_is_already_set_to_submitter) {
                    //It is not necessary to process if the anonymous has full access (anon can't have assignee or submitter access)
                    if ($anonymous_is_already_set_to_fullaccess) {
                        $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_anon_full', [$stored_ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['ugroup']['name'], $anonymous_name]));
                    } else {
                        foreach ($stored_ugroups_permissions as $stored_ugroup_id => $stored_ugroup_permissions) {
                            if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                                //We remove old permissions
                                if ($registered_is_already_set_to_fullaccess) {
                                    permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_FULL', $stored_ugroup_id, $atid);
                                    $add_full_to_history                     = true;
                                    $registered_is_already_set_to_fullaccess = false;
                                }
                                if ($registered_is_already_set_to_assignee) {
                                    permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_ASSIGNEE', $stored_ugroup_id, $atid);
                                    $add_assignee_to_history               = true;
                                    $registered_is_already_set_to_assignee = false;
                                }
                                permission_add_ugroup($group_id, 'TRACKER_ACCESS_SUBMITTER', $atid, $stored_ugroup_id);
                                $add_submitter_to_history               = true;
                                $registered_is_already_set_to_submitter = true;
                            } elseif ($stored_ugroup_id !== $GLOBALS['UGROUP_ANONYMOUS']) { //ugroups other than anonymous
                                //We remove permissions for others ugroups if they have submitter
                                if (
                                    isset($stored_ugroup_permissions['permissions']['TRACKER_ACCESS_SUBMITTER']) && ! isset($stored_ugroup_permissions['permissions']['TRACKER_ACCESS_ASSIGNEE'])
                                    && (! isset($_REQUEST[$prefixe_expected . $stored_ugroup_id]) || $_REQUEST[$prefixe_expected . $stored_ugroup_id] != 100)
                                ) {
                                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_submitter', [$stored_ugroup_permissions['ugroup']['name'], $registered_name]));
                                    permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_SUBMITTER', $stored_ugroup_id, $atid);
                                    $add_submitter_to_history = true;
                                }
                            }
                        }
                    }
                }
                break;
            case 3:
                //TRACKER_ACCESS_SUBMITTER && TRACKER_ACCESS_ASSIGNEE
                //---------------------------------------------------
                if (! ($registered_is_already_set_to_submitter && $registered_is_already_set_to_assignee)) {
                    //It is not necessary to process if the anonymous has full access (anon can't have assignee or submitter access)
                    if ($anonymous_is_already_set_to_fullaccess) {
                        $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_anon_full', [$stored_ugroups_permissions[$GLOBALS['UGROUP_REGISTERED']]['ugroup']['name'], $anonymous_name]));
                    } else {
                        foreach ($stored_ugroups_permissions as $stored_ugroup_id => $stored_ugroup_permissions) {
                            if ($stored_ugroup_id === $GLOBALS['UGROUP_REGISTERED']) {
                                //We remove old permissions
                                if ($registered_is_already_set_to_fullaccess) {
                                    permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_FULL', $stored_ugroup_id, $atid);
                                    $add_full_to_history                     = true;
                                    $registered_is_already_set_to_fullaccess = false;
                                }
                                if (! $registered_is_already_set_to_assignee) {
                                    permission_add_ugroup($group_id, 'TRACKER_ACCESS_ASSIGNEE', $atid, $stored_ugroup_id);
                                    $add_assignee_to_history               = true;
                                    $registered_is_already_set_to_assignee = true;
                                }
                                if (! $registered_is_already_set_to_submitter) {
                                    permission_add_ugroup($group_id, 'TRACKER_ACCESS_SUBMITTER', $atid, $stored_ugroup_id);
                                    $add_submitter_to_history               = true;
                                    $registered_is_already_set_to_submitter = true;
                                }
                            } elseif ($stored_ugroup_id !== $GLOBALS['UGROUP_ANONYMOUS']) { //ugroups other than anonymous
                                //We remove permissions for others ugroups if they have submitter or assignee
                                if (
                                    (isset($stored_ugroup_permissions['permissions']['TRACKER_ACCESS_SUBMITTER']) || isset($stored_ugroup_permissions['permissions']['TRACKER_ACCESS_ASSIGNEE']))
                                    && (! isset($_REQUEST[$prefixe_expected . $stored_ugroup_id]) || $_REQUEST[$prefixe_expected . $stored_ugroup_id] != 100)
                                ) {
                                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_submitter_assignee', [$stored_ugroup_permissions['ugroup']['name'], $registered_name]));
                                    if (isset($stored_ugroup_permissions['permissions']['TRACKER_ACCESS_SUBMITTER'])) {
                                        permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_SUBMITTER', $stored_ugroup_id, $atid);
                                        $add_submitter_to_history = true;
                                    }
                                    if (isset($stored_ugroup_permissions['permissions']['TRACKER_ACCESS_ASSIGNEE'])) {
                                        permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_ASSIGNEE', $stored_ugroup_id, $atid);
                                        $add_assignee_to_history = true;
                                    }
                                }
                            }
                        }
                    }
                }
                break;
            case 100:
                //NO SPECIFIC ACCESS
                //------------------
                if ($registered_is_already_set_to_assignee) {
                    permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_ASSIGNEE', $GLOBALS['UGROUP_REGISTERED'], $atid);
                    $add_assignee_to_history               = true;
                    $registered_is_already_set_to_assignee = false;
                }
                if ($registered_is_already_set_to_submitter) {
                    permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_SUBMITTER', $GLOBALS['UGROUP_REGISTERED'], $atid);
                    $add_submitter_to_history               = true;
                    $registered_is_already_set_to_submitter = false;
                }
                if ($registered_is_already_set_to_fullaccess) {
                    permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_FULL', $GLOBALS['UGROUP_REGISTERED'], $atid);
                    $add_full_to_history                     = true;
                    $registered_is_already_set_to_fullaccess = false;
                }
                break;
            default:
                //do nothing
                break;
        }
    }

    //OTHERS INSIGNIFIANT UGROUPS
    ////////////////////////////////////////////////////////////////
    foreach ($_REQUEST as $key => $value) {
        $pos = strpos($key, $prefixe_expected);
        if ($pos !== false) {
            //We've just found a variable
            //We check now if the suffixe (id of ugroup) and the value is numeric values
            $suffixe = substr($key, $len_prefixe_expected);
            if (is_numeric($suffixe)) {
                $ugroup_id = $suffixe;
                if ($ugroup_id != $GLOBALS['UGROUP_ANONYMOUS'] && $ugroup_id != $GLOBALS['UGROUP_REGISTERED']) { //already done.
                    $ugroup_name = $stored_ugroups_permissions[$ugroup_id]['ugroup']['name'];
                    switch ($value) {
                        case 0:
                            //TRACKER_FULL_ACCESS
                            //-------------------
                            if (! isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_FULL'])) {
                                if ($anonymous_is_already_set_to_fullaccess) { //It is not necessary to process if the anonymous has full access
                                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_anon_full', [$ugroup_name, $anonymous_name]));
                                } elseif ($registered_is_already_set_to_fullaccess) {//It is not necessary to process if the registered has full access
                                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_full', [$ugroup_name, $registered_name]));
                                } else {
                                    //We remove old permissions
                                    if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_ASSIGNEE'])) {
                                        permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_ASSIGNEE', $ugroup_id, $atid);
                                        $add_assignee_to_history = true;
                                    }
                                    if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_SUBMITTER'])) {
                                        permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_SUBMITTER', $ugroup_id, $atid);
                                        $add_submitter_to_history = true;
                                    }
                                    permission_add_ugroup($group_id, 'TRACKER_ACCESS_FULL', $atid, $ugroup_id);
                                    $add_full_to_history = true;
                                }
                            }
                            break;
                        case 1:
                            //TRACKER_ACCESS_ASSIGNEE
                            //-----------------------
                            if (! isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_ASSIGNEE'])) {
                                //It is not necessary to process if the anonymous has full access
                                if ($anonymous_is_already_set_to_fullaccess) {
                                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_anon_full', [$ugroup_name, $anonymous_name]));
                                } elseif ($registered_is_already_set_to_fullaccess) {//It is not necessary to process if the registered has full access
                                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_full', [$ugroup_name, $registered_name]));
                                } elseif ($registered_is_already_set_to_submitter && $registered_is_already_set_to_assignee) {//It is not necessary to process if the registered has submitter and assignee
                                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_submitter_assignee', [$ugroup_name, $registered_name]));
                                } elseif ($registered_is_already_set_to_assignee) {//It is not necessary to process if the registered has assignee
                                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_assignee', [$ugroup_name, $registered_name]));
                                } else {
                                    //We remove old permissions
                                    if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_FULL'])) {
                                        permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_FULL', $ugroup_id, $atid);
                                        $add_full_to_history = true;
                                    }
                                    if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_SUBMITTER'])) {
                                        permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_SUBMITTER', $ugroup_id, $atid);
                                        $add_submitter_to_history = true;
                                    }
                                    permission_add_ugroup($group_id, 'TRACKER_ACCESS_ASSIGNEE', $atid, $ugroup_id);
                                    $add_assignee_to_history = true;
                                }
                            }
                            break;
                        case 2:
                            //TRACKER_ACCESS_SUBMITTER
                            //------------------------
                            if (! isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_SUBMITTER'])) {
                                //It is not necessary to process if the anonymous has full access
                                if ($anonymous_is_already_set_to_fullaccess) {
                                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_anon_full', [$ugroup_name, $anonymous_name]));
                                } elseif ($registered_is_already_set_to_fullaccess) {//It is not necessary to process if the registered has full access
                                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_full', [$ugroup_name, $registered_name]));
                                } elseif ($registered_is_already_set_to_submitter && $registered_is_already_set_to_assignee) {//It is not necessary to process if the registered has submitter and assignee
                                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_submitter_assignee', [$ugroup_name, $registered_name]));
                                } elseif ($registered_is_already_set_to_submitter) {//It is not necessary to process if the registered has submitter
                                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_submitter', [$ugroup_name, $registered_name]));
                                } else {
                                    //We remove old permissions
                                    if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_FULL'])) {
                                        permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_FULL', $ugroup_id, $atid);
                                        $add_full_to_history = true;
                                    }
                                    if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_ASSIGNEE'])) {
                                        permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_ASSIGNEE', $ugroup_id, $atid);
                                        $add_assignee_to_history = true;
                                    }
                                    permission_add_ugroup($group_id, 'TRACKER_ACCESS_SUBMITTER', $atid, $ugroup_id);
                                    $add_submitter_to_history = true;
                                }
                            }
                            break;
                        case 3:
                            //TRACKER_ACCESS_SUBMITTER && TRACKER_ACCESS_ASSIGNEE
                            //---------------------------------------------------
                            if (! (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_ASSIGNEE']) && isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_SUBMITTER']))) {
                                //It is not necessary to process if the anonymous has full access
                                if ($anonymous_is_already_set_to_fullaccess) {
                                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_anon_full', [$ugroup_name, $anonymous_name]));
                                } elseif ($registered_is_already_set_to_fullaccess) {//It is not necessary to process if the registered has full access
                                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_full', [$ugroup_name, $registered_name]));
                                } elseif ($registered_is_already_set_to_submitter && $registered_is_already_set_to_assignee) {//It is not necessary to process if the registered has submitter and assignee
                                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_ignore_g_regis_submitter_assignee', [$ugroup_name, $registered_name]));
                                } else {
                                    //We remove old permissions
                                    if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_FULL'])) {
                                        permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_FULL', $ugroup_id, $atid);
                                        $add_full_to_history = true;
                                    }
                                    if (! isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_ASSIGNEE'])) {
                                        permission_add_ugroup($group_id, 'TRACKER_ACCESS_ASSIGNEE', $atid, $ugroup_id);
                                        $add_assignee_to_history = true;
                                    }
                                    if (! isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_SUBMITTER'])) {
                                        permission_add_ugroup($group_id, 'TRACKER_ACCESS_SUBMITTER', $atid, $ugroup_id);
                                        $add_submitter_to_history = true;
                                    }
                                }
                            }
                            break;
                        case 100:
                            //NO SPECIFIC ACCESS
                            //------------------
                            if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_FULL'])) {
                                permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_FULL', $ugroup_id, $atid);
                                $add_full_to_history = true;
                            }
                            if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_ASSIGNEE'])) {
                                permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_ASSIGNEE', $ugroup_id, $atid);
                                $add_assignee_to_history = true;
                            }
                            if (isset($stored_ugroups_permissions[$ugroup_id]['permissions']['TRACKER_ACCESS_SUBMITTER'])) {
                                permission_clear_ugroup_object($group_id, 'TRACKER_ACCESS_SUBMITTER', $ugroup_id, $atid);
                                $add_submitter_to_history = true;
                            }
                            break;
                        default:
                            //do nothing
                            break;
                    }
                }
            }
        }
    }
    //history
    if ($add_full_to_history) {
        permission_add_history($group_id, 'TRACKER_ACCESS_FULL', $atid);
    }
    if ($add_assignee_to_history) {
        permission_add_history($group_id, 'TRACKER_ACCESS_ASSIGNEE', $atid);
    }
    if ($add_submitter_to_history) {
        permission_add_history($group_id, 'TRACKER_ACCESS_SUBMITTER', $atid);
    }

    //feedback
    if ($add_full_to_history || $add_assignee_to_history || $add_submitter_to_history) {
        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('project_admin_userperms', 'perm_upd'));
    }
}

    /** returns true if the perms array contains
     * TRACKER_FIELD_READ or TRACKER_FIELD_UPDATE permission
     */
function permission_can_read_field($perms)
{
    if (! $perms) {
        return false;
    }
    return (in_array('TRACKER_FIELD_READ', $perms) || in_array('TRACKER_FIELD_UPDATE', $perms));
}

    /** returns true if the perms array contains
     * TRACKER_FIELD_UPDATE permission
     */
function permission_can_update_field($perms)
{
    if (! $perms) {
        return false;
    }
    return (in_array('TRACKER_FIELD_UPDATE', $perms));
}

    /** returns true if the perms array contains
     * TRACKER_FIELD_SUBMIT permission
     */
function permission_can_submit_field($perms)
{
    if (! $perms) {
        return false;
    }
    return (in_array('TRACKER_FIELD_SUBMIT', $perms));
}
