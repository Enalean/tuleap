<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Hierarchy;

use Tuleap\Event\Dispatchable;
use Tuleap\Tracker\Tracker;

class TrackerHierarchyUpdateEvent implements Dispatchable
{
    public const NAME = 'trackerHierarchyUpdateEvent';

    private bool $hierarchy_can_be_updated = true;
    private string $error_message          = '';

    /**
     * @param int[] $children_trackers_ids
     */
    public function __construct(
        private Tracker $parent_tracker,
        private array $children_trackers_ids,
    ) {
    }

    public function getParentTracker(): Tracker
    {
        return $this->parent_tracker;
    }

    /**
     * @return int[]
     */
    public function getChildrenTrackersIds(): array
    {
        return $this->children_trackers_ids;
    }

    public function canHierarchyBeUpdated(): bool
    {
        return $this->hierarchy_can_be_updated;
    }

    public function setHierarchyCannotBeUpdated(): void
    {
        $this->hierarchy_can_be_updated = false;
    }

    public function getErrorMessage(): string
    {
        return $this->error_message;
    }

    public function setErrorMessage(string $error_message): void
    {
        $this->error_message = $error_message;
    }
}
