<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Hierarchy_HierarchicalTrackerTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
{
    private Tracker_Hierarchy_HierarchicalTracker $hierarchical_tracker;
    private Tracker $child;
    private Tracker $tracker;

    #[\Override]
    protected function setUp(): void
    {
        $project_id = 110;
        $project    = ProjectTestBuilder::aProject()->withId($project_id)->build();

        $this->tracker = TrackerTestBuilder::aTracker()->withId(1)->withProject($project)->build();
        $this->child   = TrackerTestBuilder::aTracker()->withId(2)->build();

        $this->hierarchical_tracker = new Tracker_Hierarchy_HierarchicalTracker($this->tracker, [$this->child]);
    }

    public function testDelegatesGetGroupIdToTracker(): void
    {
        $this->assertEquals(110, $this->hierarchical_tracker->getProject()->getId());
    }

    public function testDelegatesGetIdToTracker(): void
    {
        $this->assertEquals(1, $this->hierarchical_tracker->getId());
    }

    public function testHasChild(): void
    {
        $this->assertTrue($this->hierarchical_tracker->hasChild($this->child));
    }

    public function testNotHasChild(): void
    {
        $not_child = TrackerTestBuilder::aTracker()->withId(3)->build();
        $this->assertFalse($this->hierarchical_tracker->hasChild($not_child));
    }

    public function testIsNotItsOwnChild(): void
    {
        $this->assertFalse($this->hierarchical_tracker->hasChild($this->tracker));
    }

    public function testGetChildren(): void
    {
        $children = $this->hierarchical_tracker->getChildren();
        $this->assertCount(1, $children);
        $this->assertEquals($children[0], $this->child);
    }
}
