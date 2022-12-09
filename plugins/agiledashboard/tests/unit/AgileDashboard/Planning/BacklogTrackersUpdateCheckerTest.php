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
use Tracker_Hierarchy;
use Tracker_HierarchyFactory;
use Tuleap\Test\PHPUnit\TestCase;

final class BacklogTrackersUpdateCheckerTest extends TestCase
{
    public function testItReturnsVoidIfNoHierarchicalLinksExistBetweenBacklogTrackers(): void
    {
        $this->expectNotToPerformAssertions();

        $hierarchy_factory = $this->createMock(Tracker_HierarchyFactory::class);
        $checker           = new BacklogTrackersUpdateChecker($hierarchy_factory);

        $hierarchy_factory->method('getHierarchy')->willReturn(
            new Tracker_Hierarchy()
        );

        $planning_parameters = PlanningParameters::fromArray([
            'backlog_tracker_ids' => [4, 101, 59],
        ]);
        $checker->checkProvidedBacklogTrackersAreValid($planning_parameters);
    }

    public function testItReturnsVoidIfNoHierarchicalLinksFoundBetweenBacklogTrackers(): void
    {
        $this->expectNotToPerformAssertions();

        $hierarchy_factory = $this->createMock(Tracker_HierarchyFactory::class);
        $checker           = new BacklogTrackersUpdateChecker($hierarchy_factory);

        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(4, 102);
        $hierarchy->addRelationship(61, 78);
        $hierarchy_factory->method('getHierarchy')->willReturn($hierarchy);

        $planning_parameters = PlanningParameters::fromArray([
            'backlog_tracker_ids' => [4, 101, 59],
        ]);
        $checker->checkProvidedBacklogTrackersAreValid($planning_parameters);
    }

    public function testItThrowsAnExceptionIfHierarchicalLinksFoundBetweenBacklogTrackers(): void
    {
        $hierarchy_factory = $this->createMock(Tracker_HierarchyFactory::class);
        $checker           = new BacklogTrackersUpdateChecker($hierarchy_factory);

        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(61, 78);
        $hierarchy->addRelationship(4, 101);
        $hierarchy_factory->method('getHierarchy')->willReturn($hierarchy);

        $planning_parameters = PlanningParameters::fromArray([
            'backlog_tracker_ids' => [4, 101, 59],
        ]);

        $this->expectException(TrackersHaveAtLeastOneHierarchicalLinkException::class);

        $checker->checkProvidedBacklogTrackersAreValid($planning_parameters);
    }
}
