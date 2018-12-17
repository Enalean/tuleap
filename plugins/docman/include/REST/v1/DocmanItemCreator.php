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
 *
 */

namespace Tuleap\Docman\REST\v1;

use Docman_Item;
use PFUser;
use Project;

class DocmanItemCreator
{
    /**
     * @var \PermissionsManager
     */
    private $permission_manager;

    /**
     * @var \EventManager
     */
    private $event_manager;

    /**
     * @var \Docman_ItemFactory
     */
    private $item_factory;

    public function __construct(
        \PermissionsManager $permission_manager,
        \EventManager $event_manager,
        \Docman_ItemFactory $item_factory
    ) {
        $this->permission_manager       = $permission_manager;
        $this->event_manager            = $event_manager;
        $this->item_factory             = $item_factory;
    }

    public function create(
        Docman_Item $parent_item,
        PFUser $user,
        Project $project,
        $title,
        $description,
        $item_type_id
    ) {
        $status_none_id = 100;

        $item = $this->item_factory->createWithoutOrdering(
            $title,
            $description,
            $parent_item->getId(),
            $status_none_id,
            $user->getId(),
            $item_type_id
        );

        $this->inheritPermissionsFromParent($item);
        $this->triggerPostCreationEvents($item, $user, $parent_item, $project);
    }

    private function inheritPermissionsFromParent(Docman_Item $item)
    {
        $this->permission_manager->clonePermissions(
            $item->getParentId(),
            $item->getId(),
            ['PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE']
        );
    }

    private function triggerPostCreationEvents(Docman_Item $item, PFUser $user, Docman_Item $parent, Project $project)
    {
        $params = [
            'group_id' => $project->getID(),
            'parent'   => $parent,
            'item'     => $item,
            'user'     => $user
        ];

        $this->event_manager->processEvent(PLUGIN_DOCMAN_EVENT_NEW_EMPTY, $params);
        $this->event_manager->processEvent('plugin_docman_event_add', $params);
        $this->event_manager->processEvent('send_notifications', []);
    }
}
