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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Admin\Homepage;

class UsersStatisticsPresenter
{
    public $json_encoded_statistics;
    public $per_activities;
    public $lastday_users_label;
    public $lastweek_users_label;
    public $lastmonth_users_label;
    public $last3months_users_label;
    public $recent_logins_label;
    public $lastday_users;
    public $lastweek_users;
    public $lastmonth_users;
    public $last3months_users;
    public $homepage_all_users;
    public $additional_statistics;

    public function __construct(
        $statistics_users_graph,
        $lastday_users,
        $lastweek_users,
        $lastmonth_users,
        $last3months_users,
        array $additional_statistics
    ) {
        $this->json_encoded_statistics = json_encode($statistics_users_graph);
        $this->lastday_users           = number_format($lastday_users);
        $this->lastweek_users          = number_format($lastweek_users);
        $this->lastmonth_users         = number_format($lastmonth_users);
        $this->last3months_users       = number_format($last3months_users);
        $this->additional_statistics   = $additional_statistics;

        $this->lastday_users_label     = $GLOBALS['Language']->getText('admin_main', 'lastday_users');
        $this->lastweek_users_label    = $GLOBALS['Language']->getText('admin_main', 'lastweek_users');
        $this->lastmonth_users_label   = $GLOBALS['Language']->getText('admin_main', 'lastmonth_users');
        $this->last3months_users_label = $GLOBALS['Language']->getText('admin_main', 'last3months_users');
        $this->recent_logins_label     = $GLOBALS['Language']->getText('admin_main', 'stat_login');
        $this->per_activities          = $GLOBALS['Language']->getText('admin_main', 'active_users');
        $this->homepage_all_users      = $GLOBALS['Language']->getText('admin_main', 'homepage_all_users');
    }
}
