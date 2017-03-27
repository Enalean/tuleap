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

namespace Tuleap\Docman\Notifications;

class UsersUpdater
{
    /**
     * @var UsersToNotifyDao
     */
    private $user_dao;

    public function __construct(UsersToNotifyDao $user_dao)
    {
        $this->user_dao = $user_dao;
    }

    public function create($user_id, $item_id, $type)
    {
        return $this->user_dao->create($user_id, $item_id, $type);
    }

    public function delete($user_id, $item_id, $type)
    {
        return $this->user_dao->delete($user_id, $item_id, $type);
    }
}
