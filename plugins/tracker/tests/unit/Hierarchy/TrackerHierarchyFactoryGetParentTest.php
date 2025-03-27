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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeIsChildLinkRetriever;
use Tuleap\Tracker\Hierarchy\HierarchyDAO;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerHierarchyFactoryGetParentTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private TrackerFactory&MockObject $tracker_factory;
    private Tracker_HierarchyFactory $hierarchy_factory;
    private HierarchyDAO&MockObject $dao;
    private Tracker $story_tracker;
    private Tracker $epic_tracker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->epic_tracker  = TrackerTestBuilder::aTracker()->withId(111)->build();
        $this->story_tracker = TrackerTestBuilder::aTracker()->withId(112)->build();

        $this->tracker_factory = $this->createMock(TrackerFactory::class);

        $this->dao               = $this->createMock(HierarchyDAO::class);
        $child_link_retriever    = $this->createMock(TypeIsChildLinkRetriever::class);
        $this->hierarchy_factory = new Tracker_HierarchyFactory(
            $this->dao,
            $this->tracker_factory,
            $this->createMock(Tracker_ArtifactFactory::class),
            $child_link_retriever
        );
    }

    public function testItReturnsTheParentTracker(): void
    {
        $this->tracker_factory->expects($this->once())->method('getTrackerById')->with(111)->willReturn($this->epic_tracker);
        $this->dao->method('searchTrackerHierarchy')->willReturn(
            [
                ['parent_id' => 111, 'child_id' => 112],
            ]
        );
        $this->assertEquals($this->epic_tracker, $this->hierarchy_factory->getParent($this->story_tracker));
    }

    public function testItReturnsNullIfNoParentTracker(): void
    {
        $this->dao->expects($this->once())->method('searchTrackerHierarchy')->willReturn([]);
        $this->assertNull($this->hierarchy_factory->getParent($this->epic_tracker));
    }
}
