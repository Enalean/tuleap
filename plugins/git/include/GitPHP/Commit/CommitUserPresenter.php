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

namespace GitPHP\Commit;

class CommitUserPresenter
{
    /** @var bool */
    public $is_a_tuleap_user;
    /** @var bool */
    public $has_avatar;
    /** @var string */
    public $avatar_url;
    /** @var string */
    public $display_name;
    /** @var string */
    public $url;

    public function build($email)
    {
        $user_manager = \UserManager::instance();
        $user_helper  = \UserHelper::instance();

        $user = $user_manager->getUserByEmail($email);
        $this->is_a_tuleap_user = $user !== null;
        if ($this->is_a_tuleap_user) {
            $this->has_avatar   = $user->hasAvatar();
            $this->avatar_url   = $user->getAvatarUrl();
            $this->display_name = trim($user_helper->getDisplayNameFromUser($user));
            $this->url          = $user_helper->getUserUrl($user);
        }
    }
}
