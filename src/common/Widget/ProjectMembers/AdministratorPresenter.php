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

use Tuleap\Widget\Event\UserWithStarBadge;

class AdministratorPresenter
{
    /** @var bool */
    public $has_avatar;
    public $user_name;
    public $username_display;
    /** @var BadgePresenter */
    public $star_badge_presenter;

    /** @var \UserHelper */
    private $user_helper;
    /**
     * @var string
     */
    public $avatar_url;

    public function __construct(\UserHelper $user_helper)
    {
        $this->user_helper = $user_helper;
    }

    public function build(\PFUser $user, ?UserWithStarBadge $badged_user = null)
    {
        $this->has_avatar       = $user->hasAvatar();
        $this->avatar_url       = $user->getAvatarUrl();
        $this->user_name        = $user->getUserName();
        $this->username_display = $this->user_helper->getDisplayNameFromUser($user);
        if ($this->userHasStarBadge($user, $badged_user)) {
            $this->star_badge_presenter = new BadgePresenter(
                $badged_user->getBadgeLabel(),
                $badged_user->getBadgeTooltipText()
            );
        }
    }

    private function userHasStarBadge(\PFUser $user, ?UserWithStarBadge $badged_user = null)
    {
        return $badged_user !== null && $badged_user->isUserBadged($user);
    }
}
