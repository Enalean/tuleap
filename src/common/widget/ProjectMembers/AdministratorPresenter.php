<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Widget\ProjectMembers;

class AdministratorPresenter
{
    /** @var bool */
    public $has_avatar;
    public $user_name;
    public $username_display;

    /** @var \UserHelper */
    private $user_helper;

    public function __construct(\UserHelper $user_helper)
    {
        $this->user_helper = $user_helper;
    }

    public function build(\PFUser $user)
    {
        $this->has_avatar       = $user->hasAvatar();
        $this->user_name        = $user->getUserName();
        $this->username_display = $this->user_helper->getDisplayNameFromUser($user);
    }
}
