<?php
/**
 * Copyright Enalean (c) 2017 - Present. All rights reserved.
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

use Docman_NotificationsManager;
use EventManager;
use ProjectUGroup;
use Tuleap\Notification\UgroupToBeNotifiedPresenter;
use UGroupDao;
use UGroupManager;

class CollectionOfUgroupMonitoredItemsBuilder
{
    /**
     * @var Docman_NotificationsManager
     */
    private $notifications_manager;

    public function __construct(Docman_NotificationsManager $notifications_manager)
    {
        $this->notifications_manager = $notifications_manager;
    }

    public function getCollectionOfUgroupMonitoredItems(\Docman_Item $item)
    {
        $ugroup_manager = new UGroupManager(new UGroupDao(), EventManager::instance());

        $monitored_items = [];
        foreach ($this->notifications_manager->getListeningUGroups($item) as $ugroup_id => $docman_item) {
            $ugroup = $ugroup_manager->getById($ugroup_id);

            $ugroup = [
                'ugroup_id'   => $ugroup->getId(),
                'name'        => $ugroup->getName(),
                'description' => $ugroup->getDescription(),
                'group_id'    => $docman_item->getGroupId(),
            ];

            $monitored_items[] = new UgroupMonitoredItem(
                new UgroupToBeNotifiedPresenter(new ProjectUGroup($ugroup)),
                $docman_item
            );
        }
        $this->sortUgroupAlphabetically($monitored_items);

        return $monitored_items;
    }

    private function sortUgroupAlphabetically(&$monitored_items)
    {
        usort(
            $monitored_items,
            function (UgroupMonitoredItem $a, UgroupMonitoredItem $b) {
                return strnatcasecmp($a->getUgroupPresenter()->label, $b->getUgroupPresenter()->label);
            }
        );
    }
}
