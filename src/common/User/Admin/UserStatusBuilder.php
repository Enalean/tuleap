<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use PFUser;

class UserStatusBuilder
{
    /**
     * @var UserStatusChecker
     */
    private $user_status_checker;

    public function __construct(UserStatusChecker $user_status_checker)
    {
        $this->user_status_checker = $user_status_checker;
    }

    public function getStatus(PFUser $user)
    {
        $labels[PFUser::STATUS_ACTIVE] = $GLOBALS['Language']->getText('admin_usergroup', 'active');

        if ($this->user_status_checker->isRestrictedStatusAllowedForUser($user)) {
            $labels[PFUser::STATUS_RESTRICTED] = $GLOBALS['Language']->getText('admin_usergroup', 'restricted');
        }
        $labels[PFUser::STATUS_SUSPENDED] = $GLOBALS['Language']->getText('admin_usergroup', 'suspended');
        $labels[PFUser::STATUS_DELETED]   = $GLOBALS['Language']->getText('admin_usergroup', 'deleted');

        $all_status = [];
        foreach ($labels as $key => $status) {
            $all_status[] = [
                'key'        => $key,
                'status'     => $status,
                'is_current' => $user->getStatus() === $key
            ];
        }

        return $all_status;
    }
}
