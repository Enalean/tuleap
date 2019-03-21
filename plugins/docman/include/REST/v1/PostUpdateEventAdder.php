<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Docman\REST\v1;

use Docman_Item;
use Docman_VersionFactory;
use EventManager;
use ProjectManager;

class PostUpdateEventAdder
{
    /**
     * @var Docman_VersionFactory
     */
    private $version_factory;
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var DocmanItemsEventAdder
     */
    private $items_event_adder;
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(
        Docman_VersionFactory $version_factory,
        ProjectManager $project_manager,
        DocmanItemsEventAdder $items_event_adder,
        EventManager $event_manager
    ) {
        $this->version_factory   = $version_factory;
        $this->project_manager   = $project_manager;
        $this->items_event_adder = $items_event_adder;
        $this->event_manager     = $event_manager;
    }

    public function triggerPostUpdateEvents(Docman_Item $item, \PFUser $user): void
    {
        $params = [
            'item'     => $item,
            'user'     => $user,
            'group_id' => $item->getGroupId(),
            'version'  => $this->version_factory->getCurrentVersionForItem($item)
        ];

        $this->items_event_adder->addNotificationEvents($this->project_manager->getProject($item->getGroupId()));
        $this->items_event_adder->addLogEvents();

        $this->event_manager->processEvent('plugin_docman_event_new_version', $params);
        $this->event_manager->processEvent('send_notifications', []);
    }
}
