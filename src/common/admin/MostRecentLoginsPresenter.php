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

namespace Tuleap\Admin;

class MostRecentLoginsPresenter
{
    public $page_title;
    public $pane_title;
    public $logins;
    public $no_result;
    public $no_result_label;

    public function __construct(array $logins)
    {
        $this->page_title                = $GLOBALS['Language']->getText('admin_lastlogins', 'title');
        $this->pane_title                = $GLOBALS['Language']->getText('admin_lastlogins', 'pane_title');
        $this->logins                    = $logins;
        $this->no_result                 = count($logins) === 0;
        $this->no_result_label           = $GLOBALS['Language']->getText('admin_lastlogins', 'no_result');
        $this->username_column_header    = $GLOBALS['Language']->getText('admin_lastlogins', 'username_column_header');
        $this->ip_column_header          = $GLOBALS['Language']->getText('admin_lastlogins', 'ip_column_header');
        $this->date_column_header        = $GLOBALS['Language']->getText('admin_lastlogins', 'date_column_header');
        $this->filter_placeholder        = $GLOBALS['Language']->getText('admin_lastlogins', 'filter_placeholder');
        $this->filter_no_matching_result = $GLOBALS['Language']->getText('admin_lastlogins', 'filter_no_matching_result');
    }
}
