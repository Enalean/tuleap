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

namespace Tuleap\Timetracking\Time;

use PFUser;
use UserHelper;
use UserManager;

class TimePresenterBuilder
{
    /**
     * @var DateFormatter
     */
    private $date_formatter;

    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(DateFormatter $date_formatter, UserManager $user_manager)
    {
        $this->date_formatter = $date_formatter;
        $this->user_manager   = $user_manager;
    }

    /**
     * @return array
     */
    public function buildPresenter(Time $time, PFUser $current_user)
    {
        $user = $this->user_manager->getUserById($time->getUserId());

        return [
            'id'                   => $time->getId(),
            'day'                  => $time->getDay(),
            'user'                 => $this->getUserPresenter($user),
            'time'                 => $this->date_formatter->formatMinutes($time->getMinutes()),
            'step'                 => $time->getStep(),
            'time_belongs_to_user' => $time->getUserId() === (int) $current_user->getId()
        ];
    }

    private function getUserPresenter(PFUser $user)
    {
        return [
            'avatar_url' => $user->getAvatarUrl(),
            'has_avatar' => $user->hasAvatar(),
            'label'      => UserHelper::instance()->getDisplayName($user->getUserName(), $user->getRealName())
        ];
    }
}
