<?php
/**
 * Copyright (c) Enalean, 2017. All rights reserved
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

namespace Tuleap\Dashboard\User;

use PFUser;

class UserPresenter
{
    public $real_name;
    public $login;
    public $avatar_url;
    public $has_avatar;

    public function __construct(PFUser $user)
    {
        $this->real_name  = $user->getRealName();
        $this->login      = $user->getUnixName();
        $this->has_avatar = $user->hasAvatar();
        $this->avatar_url = $user->getAvatarUrl();
        $this->avatar_alt = _('User avatar');
    }
}
