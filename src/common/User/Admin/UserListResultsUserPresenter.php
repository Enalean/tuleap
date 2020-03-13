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

use Tuleap\User\StatusPresenter;

class UserListResultsUserPresenter
{

    public $name;
    public $id;
    public $realname;
    public $has_avatar;
    public $nb_member_of;
    public $nb_admin_of;
    public $nb_member_of_title;
    public $nb_admin_of_title;
    public $status;
    public $avatar_url;

    public function __construct(
        $id,
        $name,
        $realname,
        $has_avatar,
        $avatar_url,
        $status,
        $nb_member_of,
        $nb_admin_of
    ) {
        $this->id           = $id;
        $this->name         = $name;
        $this->realname     = $realname;
        $this->has_avatar   = $has_avatar;
        $this->avatar_url   = $avatar_url;
        $this->nb_member_of = (int) $nb_member_of;
        $this->nb_admin_of  = (int) $nb_admin_of;

        $this->member_of_title     = $GLOBALS['Language']->getText('admin_userlist', 'member_of', $nb_member_of);
        $this->admin_of_title      = $GLOBALS['Language']->getText('admin_userlist', 'admin_of', $nb_admin_of);
        $this->not_member_of_title = $GLOBALS['Language']->getText('admin_userlist', 'not_member_of');

        $this->status = new StatusPresenter($status);
    }
}
