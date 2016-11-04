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

class UserListPresenter
{

    public $title;
    public $context;
    public $search_fields;
    public $results;
    public $new_user;
    public $group_id;

    public function __construct(
        $group_id,
        $title,
        $context,
        UserListSearchFieldsPresenter $search_fields,
        UserListResultsPresenter $results
    ) {
        $this->group_id      = $group_id;
        $this->title         = $title;
        $this->context       = $context;
        $this->search_fields = $search_fields;
        $this->results       = $results;
        $this->new_user      = $GLOBALS['Language']->getText('admin_main', 'new_user');
    }
}
