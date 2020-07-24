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
use Project;
use UGroupManager;

class NotifiedPeopleRetriever
{
    /**
     * @var UsersToNotifyDao
     */
    private $user_dao;

    /**
     * @var UgroupsToNotifyDao
     */
    private $ugroup_dao;

    /**
     * @var Docman_ItemFactory
     */
    private $item_factory;

    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    public function __construct(
        UsersToNotifyDao $user_dao,
        UgroupsToNotifyDao $ugroup_dao,
        Docman_ItemFactory $item_factory,
        UGroupManager $ugroup_manager
    ) {
        $this->user_dao       = $user_dao;
        $this->ugroup_dao     = $ugroup_dao;
        $this->item_factory   = $item_factory;
        $this->ugroup_manager = $ugroup_manager;
    }

    public function getNotifiedUsers(Project $project, $item_id)
    {
        //search for users who monitor the item or its parent
        $type  = PLUGIN_DOCMAN_NOTIFICATION;
        $users = [];
        $this->getNotifiedUsersForAscendantHierarchy(
            $project,
            $item_id,
            $users,
            $type
        );

        return new ArrayIterator($users);
    }

    private function getNotifiedUsersForAscendantHierarchy(
        Project $project,
        $item_id,
        array &$users,
        $type = null
    ) {
        if ($item_id === 0) {
            return;
        }

        if ($item = $this->item_factory->getItemFromDb($item_id)) {
            $ugroups = [];
            $this->aggregateUsers($item_id, $users, $type);
            $this->aggregateUgroups($item_id, $ugroups, $type);
            $this->addNotifedUgroupMembersToUsers(
                $project,
                $users,
                $ugroups,
                $item_id,
                $type
            );

            $this->getNotifiedUsersForAscendantHierarchy(
                $project,
                $item->getParentId(),
                $users,
                PLUGIN_DOCMAN_NOTIFICATION_CASCADE
            );
        }
    }

    private function addNotifedUgroupMembersToUsers(
        Project $project,
        array &$users,
        array $ugroups,
        $item_id,
        $type
    ) {
        foreach ($ugroups as $ugroup) {
            $ugroup_data = $this->ugroup_manager->getUGroup($project, $ugroup['ugroup_id']);
            if ($ugroup_data === null) {
                continue;
            }

            foreach ($ugroup_data->getMembers() as $user) {
                $users[$user->getId()] = [
                    'item_id' => (string) $item_id,
                    'user_id' => $user->getId(),
                    'type'    => $type
                ];
            }
        }
    }

    private function aggregateUgroups($item_id, array &$ugroups, $type)
    {
        $ugroups_iterator = $this->ugroup_dao->searchUgroupsByItemIdAndType($item_id, $type);
        if ($ugroups_iterator) {
            foreach ($ugroups_iterator as $ugroup) {
                $ugroups[] = $ugroup;
            }
        }
    }

    private function aggregateUsers($item_id, array &$users, $type)
    {
        $type_for_user_dao = $type ? $type : PLUGIN_DOCMAN_NOTIFICATION_CASCADE;
        $users_iterator    = $this->user_dao->searchUserIdByObjectIdAndType($item_id, $type_for_user_dao);
        foreach ($users_iterator as $user) {
            $user_id         = $user['user_id'];
            $users[$user_id] = $user;
        }
    }
}
