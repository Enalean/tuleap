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

use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TrackerHierarchyDelegationTest extends TestCase
{
    public function testTrackerHierarchyIsNotDelegatedByDefault(): void
    {
        $tracker                      = TrackerTestBuilder::aTracker()->build();
        $tracker_hierarchy_delegation = new TrackerHierarchyDelegation($tracker);

        self::assertSame($tracker, $tracker_hierarchy_delegation->getTracker());
        self::assertNull($tracker_hierarchy_delegation->getResourceNameTrackerHierarchyHasBeenDelegatedTo());
    }

    public function testTrackerHierarchyCanBeDelegated(): void
    {
        $tracker_hierarchy_delegation = new TrackerHierarchyDelegation(TrackerTestBuilder::aTracker()->build());

        $tracker_hierarchy_delegation->enableTrackerHierarchyDelegation('test');

        self::assertEquals('test', $tracker_hierarchy_delegation->getResourceNameTrackerHierarchyHasBeenDelegatedTo());
    }
}
