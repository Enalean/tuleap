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
final class Tracker_Hierarchy_HierarchicalTrackerTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Tracker_Hierarchy_HierarchicalTracker
     */
    private $hierarchical_tracker;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $child;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $tracker;

    protected function setUp(): void
    {
        $project_id = 110;
        $project          = Mockery::mock(Project::class);
        $project->shouldReceive('getId')->andReturns($project_id);
        $this->tracker = Mockery::mock(Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturn(1);
        $this->tracker->shouldReceive('getProject')->andReturn($project);
        $this->child = Mockery::mock(Tracker::class);
        $this->child->shouldReceive('getId')->andReturn(2);

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
        $not_child = Mockery::mock(Tracker::class);
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
