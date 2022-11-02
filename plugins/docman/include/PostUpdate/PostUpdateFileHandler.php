<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Docman\PostUpdate;

use Tuleap\Docman\REST\v1\DocmanItemsEventAdder;
use Tuleap\Project\ProjectByIDFactory;

class PostUpdateFileHandler
{
    public function __construct(
        private \Docman_VersionFactory $version_factory,
        private DocmanItemsEventAdder $items_event_adder,
        private ProjectByIDFactory $project_factory,
        private \EventManager $event_manager,
    ) {
    }

    public function triggerPostUpdateEvents(\Docman_File|\Docman_Empty $item, \PFUser $user): void
    {
        $params = [
            'item'     => $item,
            'user'     => $user,
            'group_id' => $item->getGroupId(),
            'version'  => $this->version_factory->getCurrentVersionForItem($item),
        ];

        $this->items_event_adder->addNotificationEvents($this->project_factory->getProjectById($item->getGroupId()));
        $this->items_event_adder->addLogEvents();

        $this->event_manager->processEvent('plugin_docman_event_new_version', $params);
        $this->event_manager->processEvent('send_notifications', []);
    }
}
