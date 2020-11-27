<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

final class TrackerHierarchyDelegation implements Dispatchable
{
    public const NAME = 'trackerHierarchyDelegation';

    /**
     * @var \Tracker
     * @psalm-readonly
     */
    private $tracker;
    /**
     * @var string|null
     */
    private $tracker_hierarchy_delegated_to;

    public function __construct(\Tracker $tracker)
    {
        $this->tracker = $tracker;
    }

    public function getTracker(): \Tracker
    {
        return $this->tracker;
    }

    public function enableTrackerHierarchyDelegation(string $resource_name): void
    {
        $this->tracker_hierarchy_delegated_to = $resource_name;
    }

    public function getResourceNameTrackerHierarchyHasBeenDelegatedTo(): ?string
    {
        return $this->tracker_hierarchy_delegated_to;
    }
}
