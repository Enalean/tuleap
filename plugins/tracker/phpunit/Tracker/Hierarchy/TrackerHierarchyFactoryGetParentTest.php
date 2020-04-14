<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

declare(strict_types=1);

use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureIsChildLinkRetriever;
use Tuleap\Tracker\Hierarchy\HierarchyDAO;

final class TrackerHierarchyFactoryGetParentTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var Tracker_HierarchyFactory
     */
    private $hierarchy_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|HierarchyDAO
     */
    private $dao;
    /**
     * @var Tracker
     */
    private $story_tracker;
    /**
     * @var Tracker
     */
    private $epic_tracker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->epic_tracker = Mockery::mock(Tracker::class);
        $this->epic_tracker->shouldReceive('getId')->andReturn(111);
        $this->story_tracker = Mockery::mock(Tracker::class);
        $this->story_tracker->shouldReceive('getId')->andReturn(112);

        $this->tracker_factory = Mockery::mock(TrackerFactory::class);

        $this->dao               = Mockery::mock(HierarchyDAO::class);
        $child_link_retriever    = Mockery::mock(NatureIsChildLinkRetriever::class);
        $this->hierarchy_factory = new Tracker_HierarchyFactory(
            $this->dao,
            $this->tracker_factory,
            Mockery::mock(Tracker_ArtifactFactory::class),
            $child_link_retriever
        );
    }

    public function testItReturnsTheParentTracker(): void
    {
        $this->tracker_factory->shouldReceive('getTrackerById')->with(111)->andReturn($this->epic_tracker)->once();
        $this->dao->shouldReceive('searchTrackerHierarchy')->andReturn(
            [
                ['parent_id' => 111, 'child_id' => 112]
            ]
        );
        $this->assertEquals($this->epic_tracker, $this->hierarchy_factory->getParent($this->story_tracker));
    }

    public function testItReturnsNullIfNoParentTracker(): void
    {
        $this->dao->shouldReceive('searchTrackerHierarchy')->andReturn([])->once();
        $this->assertNull($this->hierarchy_factory->getParent($this->epic_tracker));
    }
}
