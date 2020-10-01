<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\InviteBuddy\Admin;

use PFUser;

class InvitedByUserPresenter
{
    /**
     * @var string
     * @psalm-readonly
     */
    public $display_name;
    /**
     * @var string
     * @psalm-readonly
     */
    public $url;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $has_avatar;
    /**
     * @var string
     * @psalm-readonly
     */
    public $avatar_url;

    public function __construct(PFUser $user)
    {
        $this->display_name = (string) \UserHelper::instance()->getDisplayNameFromUser($user);
        $this->url          = '/admin/usergroup.php?' . http_build_query(['user_id' => $user->getId()]);
        $this->has_avatar   = $user->hasAvatar();
        $this->avatar_url   = $user->getAvatarUrl();
    }
}
