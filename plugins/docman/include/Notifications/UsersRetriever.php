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

use Docman_Item;
use Docman_ItemFactory;

class UsersRetriever
{
    /**
     * @var UsersToNotifyDao
     */
    private $user_dao;

    /**
     * @var Docman_ItemFactory
     */
    private $item_factory;



    public function __construct(
        UsersToNotifyDao $user_dao,
        Docman_ItemFactory $item_factory
    ) {
        $this->user_dao       = $user_dao;
        $this->item_factory   = $item_factory;
    }

    public function getListeningUsers(Docman_Item $item, array $users, $type)
    {
        $dar = $this->user_dao->searchUserIdByObjectIdAndType(
            $item->getId(),
            $type ? $type : PLUGIN_DOCMAN_NOTIFICATION_CASCADE
        );
        if ($dar) {
            foreach ($dar as $user) {
                if (! array_key_exists($user['user_id'], $users)) {
                    $users[$user['user_id']] = $item;
                }
            }
        }

        if ($id = $item->getParentId()) {
            $item  = $this->item_factory->getItemFromDb($id);
            $users = $this->getListeningUsers($item, $users, PLUGIN_DOCMAN_NOTIFICATION_CASCADE);
        }

        return $users;
    }

    public function doesNotificationExistByUserAndItemId($user_id, $item_id, $type)
    {
        $dar = $this->user_dao->search($user_id, $item_id, $type);

        return $dar->count() > 0;
    }
}
