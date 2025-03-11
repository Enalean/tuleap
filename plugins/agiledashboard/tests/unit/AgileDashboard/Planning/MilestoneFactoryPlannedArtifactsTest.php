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

namespace Tuleap\AgileDashboard\Planning;

use AgileDashboard_Milestone_MilestoneStatusCounter;
use Planning_MilestoneFactory;
use PlanningFactory;
use PlanningPermissionsManager;
use Psr\Log\NullLogger;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tuleap\AgileDashboard\Milestone\MilestoneDao;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Semantic\Timeframe\BuildSemanticTimeframeStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MilestoneFactoryPlannedArtifactsTest extends TestCase
{
    public function testItReturnsATreeOfPlanningItems(): void
    {
        $depth3_artifact = $this->createMock(Artifact::class);
        $depth3_artifact->method('getId')->willReturn(3);
        $depth3_artifact->method('getUniqueLinkedArtifacts')->willReturn([]);

        $depth2_artifact = $this->createMock(Artifact::class);
        $depth2_artifact->method('getId')->willReturn(2);
        $depth2_artifact->method('getUniqueLinkedArtifacts')->willReturn([$depth3_artifact]);

        $depth1_artifact = $this->createMock(Artifact::class);
        $depth1_artifact->method('getId')->willReturn(1);
        $depth1_artifact->method('getUniqueLinkedArtifacts')->willReturn([$depth2_artifact]);

        $root_artifact = $this->createMock(Artifact::class);
        $root_artifact->method('getId')->willReturn(100);
        $root_artifact->method('getUniqueLinkedArtifacts')->willReturn([$depth1_artifact]);

        $factory             = new Planning_MilestoneFactory(
            $this->createMock(PlanningFactory::class),
            $this->createMock(Tracker_ArtifactFactory::class),
            $this->createMock(Tracker_FormElementFactory::class),
            $this->createMock(AgileDashboard_Milestone_MilestoneStatusCounter::class),
            $this->createMock(PlanningPermissionsManager::class),
            $this->createMock(MilestoneDao::class),
            BuildSemanticTimeframeStub::withTimeframeSemanticNotConfigured(TrackerTestBuilder::aTracker()->build()),
            new NullLogger(),
        );
        $planning_items_tree = $factory->getPlannedArtifacts(UserTestBuilder::buildWithDefaults(), $root_artifact);

        $children = $planning_items_tree->flattenChildren();

        self::assertNotEmpty($children);
        foreach ($children as $tree_node) {
            self::assertInstanceOf(Artifact::class, $tree_node->getObject());
        }
    }
}
