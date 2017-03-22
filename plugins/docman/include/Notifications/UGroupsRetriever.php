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

class UGroupsRetriever
{
    /**
     * @var UgroupsToNotifyDao
     */
    private $dao;
    /**
     * @var \Docman_ItemFactory
     */
    private $item_factory;

    public function __construct(UgroupsToNotifyDao $dao, \Docman_ItemFactory $item_factory)
    {
        $this->dao          = $dao;
        $this->item_factory = $item_factory;
    }

    public function getListeningUGroups(\Docman_Item $item, array $ugroups, $type)
    {
        $dar = $this->dao->searchUgroupsByItemIdAndType(
            $item->getId(),
            $type ? $type : PLUGIN_DOCMAN_NOTIFICATION_CASCADE
        );
        if ($dar) {
            foreach ($dar as $group) {
                if (! array_key_exists($group['ugroup_id'], $ugroups)) {
                    $ugroups[$group['ugroup_id']] = $item;
                }
            }
        }

        if ($id = $item->getParentId()) {
            $item    = $this->item_factory->getItemFromDb($id);
            $ugroups = $this->getListeningUGroups($item, $ugroups, PLUGIN_DOCMAN_NOTIFICATION_CASCADE);
        }

        return $ugroups;
    }
}
