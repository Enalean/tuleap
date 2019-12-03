<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

    private function __construct()
    {
    }

    /**
     * @return CommitUserPresenter
     */
    public static function buildFromTuleapUser(?\PFUser $user = null)
    {
        $user_presenter                   = new self();
        $user_presenter->is_a_tuleap_user = $user !== null;
        if ($user_presenter->is_a_tuleap_user) {
            $user_helper                  = \UserHelper::instance();
            $user_presenter->has_avatar   = $user->hasAvatar();
            $user_presenter->avatar_url   = $user->getAvatarUrl();
            $user_presenter->display_name = trim($user_helper->getDisplayNameFromUser($user) ?? '');
            $user_presenter->url          = $user_helper->getUserUrl($user);
        }

        return $user_presenter;
    }
}
