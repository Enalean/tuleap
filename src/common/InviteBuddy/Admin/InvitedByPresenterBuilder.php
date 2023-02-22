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

use Tuleap\InviteBuddy\InvitationDao;
use UserManager;

class InvitedByPresenterBuilder
{
    /**
     * @var InvitationDao
     */
    private $dao;
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(InvitationDao $dao, UserManager $user_manager)
    {
        $this->dao          = $dao;
        $this->user_manager = $user_manager;
    }

    public function getInvitedByPresenter(\PFUser $user): InvitedByPresenter
    {
        $users = [];
        foreach ($this->dao->searchUserIdThatInvitedUser((int) $user->getId()) as $row) {
            $from_user = $this->user_manager->getUserById($row['from_user_id']);
            if ($from_user) {
                $users[] = new InvitedByUserPresenter($from_user);
            }
        }

        return new InvitedByPresenter($users);
    }
}
