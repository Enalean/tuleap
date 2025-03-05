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

namespace Tuleap\AgileDashboard\Planning;

use PlanningParameters;
use Psr\Log\NullLogger;
use TrackerFactory;
use Tracker_Hierarchy;
use Tracker_HierarchyFactory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BacklogTrackersUpdateCheckerTest extends TestCase
{
    private BacklogTrackersUpdateChecker $checker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Tracker_HierarchyFactory
     */
    private $hierarchy_factory;
    /**
     * @var TrackerFactory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $tracker_factory;

    protected function setUp(): void
    {
        $this->hierarchy_factory = $this->createMock(Tracker_HierarchyFactory::class);
        $this->tracker_factory   = $this->createMock(TrackerFactory::class);
        $this->checker           = new BacklogTrackersUpdateChecker(
            $this->hierarchy_factory,
            $this->tracker_factory,
            new NullLogger(),
        );
    }

    public function testItReturnsVoidIfNoHierarchicalLinksExistBetweenBacklogTrackers(): void
    {
        $this->expectNotToPerformAssertions();

        $this->hierarchy_factory->method('getHierarchy')->willReturn(
            new Tracker_Hierarchy()
        );

        $planning_parameters = PlanningParameters::fromArray([
            'backlog_tracker_ids' => [4, 101, 59],
        ]);
        $this->checker->checkProvidedBacklogTrackersAreValid($planning_parameters);
    }

    public function testItReturnsVoidIfNoHierarchicalLinksFoundBetweenBacklogTrackers(): void
    {
        $this->expectNotToPerformAssertions();

        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(4, 102);
        $hierarchy->addRelationship(61, 78);
        $this->hierarchy_factory->method('getHierarchy')->willReturn($hierarchy);

        $planning_parameters = PlanningParameters::fromArray([
            'backlog_tracker_ids' => [4, 101, 59],
        ]);
        $this->checker->checkProvidedBacklogTrackersAreValid($planning_parameters);
    }

    public function testItThrowsAnExceptionIfHierarchicalLinksFoundBetweenBacklogTrackers(): void
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(61, 78);
        $hierarchy->addRelationship(4, 101);
        $this->hierarchy_factory->method('getHierarchy')->willReturn($hierarchy);

        $planning_parameters = PlanningParameters::fromArray([
            'backlog_tracker_ids' => [4, 101, 59],
        ]);

        $this->tracker_factory->method('getTrackerById')->willReturnMap([
            [4, TrackerTestBuilder::aTracker()->withName('tracker01')->build()],
            [101, TrackerTestBuilder::aTracker()->withName('tracker02')->build()],
        ]);

        $this->expectException(TrackersHaveAtLeastOneHierarchicalLinkException::class);

        $this->checker->checkProvidedBacklogTrackersAreValid($planning_parameters);
    }

    public function testItThrowsAnExceptionIfHierarchicalLinksFoundBetweenBacklogTrackersAndParentTrackerNotFound(): void
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(61, 78);
        $hierarchy->addRelationship(4, 101);
        $this->hierarchy_factory->method('getHierarchy')->willReturn($hierarchy);

        $planning_parameters = PlanningParameters::fromArray([
            'backlog_tracker_ids' => [4, 101, 59],
        ]);

        $this->tracker_factory->method('getTrackerById')->willReturnMap([
            [4, null],
            [101, TrackerTestBuilder::aTracker()->withName('tracker02')->build()],
        ]);

        $this->expectException(TrackersWithHierarchicalLinkDefinedNotFoundException::class);

        $this->checker->checkProvidedBacklogTrackersAreValid($planning_parameters);
    }

    public function testItThrowsAnExceptionIfHierarchicalLinksFoundBetweenBacklogTrackersAndChildTrackerNotFound(): void
    {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(61, 78);
        $hierarchy->addRelationship(4, 101);
        $this->hierarchy_factory->method('getHierarchy')->willReturn($hierarchy);

        $planning_parameters = PlanningParameters::fromArray([
            'backlog_tracker_ids' => [4, 101, 59],
        ]);

        $this->tracker_factory->method('getTrackerById')->willReturnMap([
            [4, TrackerTestBuilder::aTracker()->withName('tracker01')->build()],
            [101, null],
        ]);

        $this->expectException(TrackersWithHierarchicalLinkDefinedNotFoundException::class);

        $this->checker->checkProvidedBacklogTrackersAreValid($planning_parameters);
    }
}
