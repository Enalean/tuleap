<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\User\Admin;

use CSRFSynchronizerToken;
use Tuleap\Layout\PaginationPresenter;
use UserManager;

class UserListResultsPresenter
{

    public $title;
    public $export_url;
    public $nb_matching_users;
    public $nb_active_sessions;
    public $display_purge_session;
    public $display_nb_projects;
    public $matching_users;

    public $sortby_name_icon;
    public $sortby_realname_icon;
    public $sortby_status_icon;
    public $sortby_name_url;
    public $sortby_realname_url;
    public $sortby_status_url;

    public $login_name_header;
    public $real_nam_header;
    public $nb_projects_header;
    public $status_header;
    public $profile_header;
    private $user_manager;

    public function __construct(
        UserManager $user_manager,
        $group_id,
        $result,
        $nb_matching_users,
        $user_name_search,
        $sort_params,
        $sort_order,
        $user_status,
        $nb_active_sessions,
        $display_nb_projects,
        $limit,
        $offset
    ) {
        $this->user_manager          = $user_manager;
        $this->nb_matching_users     = $nb_matching_users;
        $this->nb_active_sessions    = $nb_active_sessions;
        $this->display_purge_session = $nb_active_sessions > 0;
        $this->display_nb_projects   = $display_nb_projects;
        $this->matching_users        = $this->getMatchingUsers($result);

        $base_url       = '/admin/userlist.php';
        $default_params = array(
            'user_name_search'     => $user_name_search,
            'previous_sort_header' => $sort_params["sort_header"],
            'current_sort_header'  => $sort_params["sort_header"],
            'sort_order'           => $sort_params['order'],
            'status_values'        => $user_status
        );

        if (! $default_params['status_values']) {
            $default_params['status_values'] = array('ANY');
        }

        if ($group_id) {
            $default_params['group_id'] = $group_id;
        }

        $this->sortby_name_icon     = $sort_params["user_name_icon"];
        $this->sortby_realname_icon = $sort_params["realname_icon"];
        $this->sortby_status_icon   = $sort_params["status_icon"];
        $this->sortby_name_url      = $base_url . '?' . http_build_query($this->getSortUrlParams('user_name', $default_params));
        $this->sortby_realname_url  = $base_url . '?' . http_build_query($this->getSortUrlParams('realname', $default_params));
        $this->sortby_status_url    = $base_url . '?' . http_build_query($this->getSortUrlParams('status', $default_params));
        $this->export_url           = $base_url . '?' . http_build_query(array('export'   => 1) + $default_params);

        $this->login_name_header  = $GLOBALS['Language']->getText('admin_userlist', 'login');
        $this->real_nam_header    = $GLOBALS['Language']->getText('admin_userlist', 'name');
        $this->nb_projects_header = $GLOBALS['Language']->getText('admin_userlist', 'nb_projects');
        $this->status_header      = $GLOBALS['Language']->getText('admin_userlist', 'status');

        $this->title      = $GLOBALS['Language']->getText('admin_userlist', 'matching_users');
        $this->export_csv = $GLOBALS['Language']->getText('admin_userlist', 'export_csv');

        $this->active_sessions_csrf    = new CSRFSynchronizerToken('/admin/sessions.php');
        $this->active_sessions_label   = $GLOBALS['Language']->getText('admin_userlist', 'active_sessions_label');
        $this->active_sessions_confirm = $GLOBALS['Language']->getText('admin_userlist', 'active_sessions_confirm', $nb_active_sessions);
        $this->no_matching_users       = $GLOBALS['Language']->getText('admin_userlist', 'no_matching_users');

        $default_params['sort_order'] = $sort_order;
        $this->pagination = new PaginationPresenter(
            $limit,
            $offset,
            count($this->matching_users),
            $nb_matching_users,
            $base_url,
            $default_params
        );
    }

    private function getSortUrlParams($sort, $default_params)
    {
        if ($sort !== $default_params['current_sort_header']) {
            $default_params['sort_order'] = 'ASC';
        }
        return array('current_sort_header' => $sort) + $default_params;
    }

    private function getMatchingUsers($result)
    {
        $matching_users = array();
        foreach ($result as $row) {
            $nb_member_of = 0;
            if (isset($row['member_of'])) {
                $nb_member_of = $row['member_of'];
            }
            $nb_admin_of = 0;
            if (isset($row['admin_of'])) {
                $nb_admin_of = $row['admin_of'];
            }

            $user = $this->user_manager->getUserById($row['user_id']);
            if (! $user) {
                continue;
            }

            $matching_users[] = new UserListResultsUserPresenter(
                $row['user_id'],
                $row['user_name'],
                $row['realname'],
                $row['has_avatar'],
                $user->getAvatarUrl(),
                $row['status'],
                $nb_member_of,
                $nb_admin_of
            );
        }

        return $matching_users;
    }
}
