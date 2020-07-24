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

use Codendi_HTMLPurifier;

class UserListPresenter
{

    public $title;
    public $search_fields;
    public $results;
    public $new_user;
    public $detail_button_label;
    public $group_id;
    public $are_there_pending_users;
    public $purified_pending_users_text;

    public function __construct(
        $group_id,
        $title,
        UserListSearchFieldsPresenter $search_fields,
        UserListResultsPresenter $results,
        $pending_users_count
    ) {
        $this->group_id            = $group_id;
        $this->title               = $title;
        $this->search_fields       = $search_fields;
        $this->results             = $results;
        $this->new_user            = $GLOBALS['Language']->getText('admin_main', 'new_user');
        $this->detail_button_label = $GLOBALS['Language']->getText('admin_main', 'detail_button_label');

        $this->are_there_pending_users     = $pending_users_count > 0;
        $this->purified_pending_users_text = Codendi_HTMLPurifier::instance()->purify(
            $GLOBALS['Language']->getText(
                'admin_userlist',
                'pending_users_text',
                [
                    '/admin/approve_pending_users.php?page=pending',
                    $pending_users_count
                ]
            ),
            CODENDI_PURIFIER_LIGHT
        );
    }
}
