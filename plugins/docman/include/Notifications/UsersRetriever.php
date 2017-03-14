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

use ArrayIterator;
use Docman_ItemFactory;

class UsersRetriever
{
    /**
     * @var Dao
     */
    private $user_dao;
    /**
     * @var Docman_ItemFactory
     */
    private $item_factory;

    public function __construct(
        Dao $user_dao,
        Docman_ItemFactory $item_factory
    ) {
        $this->user_dao     = $user_dao;
        $this->item_factory = $item_factory;
    }

    public function getNotifiedUsers($item_id)
    {
        //search for users who monitor the item or its parent
        $type  = PLUGIN_DOCMAN_NOTIFICATION;
        $users = array();
        $this->getNotifiedUsersForAscendantHierarchy($item_id, $users, $type);
        return new ArrayIterator($users);
    }

    private function getNotifiedUsersForAscendantHierarchy($item_id, &$users, $type = null)
    {
        if ($item_id) {
            $u = $this->user_dao->searchUserIdByObjectIdAndType($item_id, $type ? $type : PLUGIN_DOCMAN_NOTIFICATION_CASCADE);
            if ($u) {
                while ($u->valid()) {
                    $users[] = $u->current();
                    $u->next();
                }
            }
            if ($item = $this->item_factory->getItemFromDb($item_id)) {
                $this->getNotifiedUsersForAscendantHierarchy($item->getParentId(), $users, $type);
            }
        }
    }
}
