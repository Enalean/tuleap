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

namespace Tuleap\Docman\Metadata;

use Docman_Item;
use PFUser;

class MetadataEventProcessor
{
    /**
     * @var \EventManager
     */
    private $event_manager;

    public function __construct(\EventManager $event_manager)
    {
        $this->event_manager = $event_manager;
    }

    public function raiseUpdateEvent(
        Docman_Item $item,
        PFUser $current_user,
        ?string $old_value,
        string $new_value,
        string $field
    ): void {
        $params = [
            'group_id'  => $item->getGroupId(),
            'item'      => $item,
            'user'      => $current_user,
            'old_value' => $old_value,
            'new_value' => $new_value,
            'field'     => $field
        ];

        $this->event_manager->processEvent(
            'plugin_docman_event_metadata_update',
            $params
        );
    }
}
