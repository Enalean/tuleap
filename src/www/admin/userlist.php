<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
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

use Tuleap\Admin\AdminPageRenderer;

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../include/account.php';
require_once __DIR__ . '/../project/admin/ugroup_utils.php';

$request = HTTPRequest::instance();
$request->checkUserIsSuperUser();

/**
 * @psalm-return "user_name"|"realname"|"status"
 * @psalm-taint-escape sql
 */
function getSortHeaderVerifiedParameter(HTTPRequest $request, string $parameter_name): string
{
    $value           = 'user_name';
    $parameter_value = $request->get($parameter_name);
    if ($parameter_value === 'realname' || $parameter_value === 'status') {
        $value = $parameter_value;
    }

    return $value;
}

/**
*   select the fields sort order and the header arrow direction
*
* @param String $previous_sort_header
* @param String $current_sort_header
* @param String $sort_order
* @param int $offset
*
* @return Array
*/
function get_sort_values($previous_sort_header, $current_sort_header, $sort_order, $offset)
{
    $sort_order_hash                                 = [
        'sort_header'    => $current_sort_header,
        'user_name_icon' => '',
        'realname_icon'  => '',
        'status_icon'    => '',
        'order'          => 'DESC',
    ];
    $sort_order_hash[$current_sort_header . "_icon"] = "fa fa-caret-down";

    if ($offset === 0) {
        if ($previous_sort_header === $current_sort_header) {
            if ($sort_order === "ASC") {
                $sort_order_hash[$current_sort_header . "_icon"] = "fa fa-caret-down";
                $sort_order_hash["order"]                        = "DESC";
            } else {
                $sort_order_hash[$current_sort_header . "_icon"] = "fa fa-caret-up";
                $sort_order_hash["order"]                        = "ASC";
            }
        }
    } else {
        if ($sort_order === "ASC") {
            $sort_order_hash[$current_sort_header . "_icon"] = "fa fa-caret-down";
            $sort_order_hash["order"]                        = "DESC";
        } else {
            $sort_order_hash[$current_sort_header . "_icon"] = "fa fa-caret-up";
            $sort_order_hash["order"]                        = "ASC";
        }
    }
    return $sort_order_hash;
}

if ($request->exist('export')) {
    //Validate user_name_search
    $vUserNameSearch  = new Valid_String('user_name_search');
    $user_name_search = '';
    if ($request->valid($vUserNameSearch)) {
        if ($request->exist('user_name_search')) {
            $user_name_search = $request->get('user_name_search');
        }
    }
    //Get current sort header
    $current_sort_header = getSortHeaderVerifiedParameter($request, 'current_sort_header');
    //Get current sort order
    $sort_order = 'ASC';
    if ($request->get('sort_order') === 'DESC') {
        $sort_order = 'DESC';
    }
    //Get status values
    $status_values = [];
    if ($request->exist('status_values')) {
        $status_submitted = $request->get('status_values');
        foreach ($status_submitted as $status) {
            if ($status != "ANY") {
                $status_values[] = $status;
            }
        }
    }

    $vGroupId = new Valid_GroupId();
    $group_id = 0;
    if ($request->valid($vGroupId)) {
        if ($request->exist('group_id')) {
            $group_id = $request->get('group_id');
        }
    }

    //export user list in csv format
    $user_list_exporter = new Admin_UserListExporter();
    $user_list_exporter->exportUserList($group_id, $user_name_search, $current_sort_header, $sort_order, $status_values);
    exit;
}

$dao    = new UserDao();
$offset = $request->getValidated('offset', 'uint', 0);
if (! $offset || $offset < 0) {
    $offset = 0;
}
$limit = 25;

$vUserNameSearch  = new Valid_String('user_name_search');
$user_name_search = '';
if ($request->valid($vUserNameSearch)) {
    if ($request->exist('user_name_search')) {
        $user_name_search = $request->get('user_name_search');
    }
}

$header_whitelist = ['user_name', 'realname', 'status'];
if (in_array($request->get('previous_sort_header'), $header_whitelist)) {
    $previous_sort_header = $request->get('previous_sort_header');
} else {
    $previous_sort_header = '';
}
$current_sort_header = getSortHeaderVerifiedParameter($request, 'current_sort_header');

$sort_order = 'ASC';
if ($request->get('sort_order') === 'DESC') {
    $sort_order = 'DESC';
}

// Check if group_id is valid
$vGroupId = new Valid_GroupId();
$group_id = false;
if ($request->valid($vGroupId)) {
    if ($request->exist('group_id')) {
        $group_id = $request->get('group_id');
    }
}

$sort_params   = get_sort_values($previous_sort_header, $current_sort_header, $sort_order, $offset);
$status_values = [];
$anySelect     = "selected";
if ($request->exist('status_values')) {
    $status_values = $request->get('status_values');
    if (! is_array($status_values)) {
        $status_values = explode(",", $status_values);
    }
    if (in_array('ANY', $status_values)) {
        $status_values = [];
    } else {
        $anySelect = "";
    }
}

if (! $group_id) {
    $group_id = 0;
}
if (isset($user_name_search) && $user_name_search) {
    $result = $dao->listAllUsers(
        $group_id,
        $user_name_search,
        $offset,
        $limit,
        $current_sort_header,
        $sort_order,
        $status_values
    );
    if ($result['numrows'] == 1) {
        $row = array_shift($result['users']);
        $GLOBALS['Response']->redirect('/admin/usergroup.php?user_id=' . $row['user_id']);
    }
} else {
    $result = $dao->listAllUsers($group_id, '', $offset, $limit, $current_sort_header, $sort_order, $status_values);
}

$search_fields_presenter = new Tuleap\User\Admin\UserListSearchFieldsPresenter($user_name_search, $status_values);

$nb_active_sessions  = 0;
$display_nb_projects = false;
if (! $group_id) {
    $session_dao         = new SessionDao();
    $session_lifetime    = ForgeConfig::get('sys_session_lifetime');
    $nb_active_sessions  = $session_dao->count($request->getFromServer('REQUEST_TIME'), $session_lifetime);
    $display_nb_projects = true;
}
$results_presenter = new Tuleap\User\Admin\UserListResultsPresenter(
    UserManager::instance(),
    $group_id,
    $result['users'],
    $result['numrows'],
    $user_name_search,
    $sort_params,
    $sort_order,
    $status_values,
    $nb_active_sessions,
    $display_nb_projects,
    $limit,
    $offset
);

$admin_page     = new AdminPageRenderer();
$include_assets = new \Tuleap\Layout\IncludeAssets(__DIR__ . '/../../scripts/site-admin/frontend-assets', '/assets/core/site-admin');
$admin_page->addCssAsset(new \Tuleap\Layout\CssAssetWithoutVariantDeclinaisons($include_assets, 'site-admin-userlist-styles'));

if ($group_id) {
    $project = ProjectManager::instance()->getProject($group_id);

    $user_list_presenter = new Tuleap\Project\Admin\ProjectMembersPresenter(
        $project,
        $search_fields_presenter,
        $results_presenter
    );

    $admin_page->renderANoFramedPresenter(
        $Language->getText('admin_project', 'members_label'),
        ForgeConfig::get('codendi_dir') . '/src/templates/admin/users/',
        'project-members',
        $user_list_presenter
    );
} else {
    $title = $Language->getText('admin_userlist', 'user_list');

    $pending_users_count = UserManager::instance()->countUsersByStatus(PFUser::STATUS_PENDING);

    $user_list_presenter = new Tuleap\User\Admin\UserListPresenter(
        $group_id,
        $title,
        $search_fields_presenter,
        $results_presenter,
        $pending_users_count
    );

    $admin_page->renderAPresenter(
        $title,
        ForgeConfig::get('codendi_dir') . '/src/templates/admin/users/',
        'all-users',
        $user_list_presenter
    );
}
