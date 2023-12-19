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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Planning_MilestoneFactory;
use Psr\Log\NullLogger;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

final class MilestoneFactoryPlannedArtifactsTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItReturnsATreeOfPlanningItems(): void
    {
        $depth3_artifact = Mockery::mock(Artifact::class);
        $depth3_artifact->shouldReceive('getId')->andReturn(3);
        $depth3_artifact->shouldReceive('getUniqueLinkedArtifacts')->andReturn([]);

        $depth2_artifact = Mockery::mock(Artifact::class);
        $depth2_artifact->shouldReceive('getId')->andReturn(2);
        $depth2_artifact->shouldReceive('getUniqueLinkedArtifacts')->andReturn([$depth3_artifact]);

        $depth1_artifact = Mockery::mock(Artifact::class);
        $depth1_artifact->shouldReceive('getId')->andReturn(1);
        $depth1_artifact->shouldReceive('getUniqueLinkedArtifacts')->andReturn([$depth2_artifact]);

        $root_artifact = Mockery::mock(Artifact::class);
        $root_artifact->shouldReceive('getId')->andReturn(100);
        $root_artifact->shouldReceive('getUniqueLinkedArtifacts')->andReturn([$depth1_artifact]);

        $factory             = new Planning_MilestoneFactory(
            Mockery::spy(\PlanningFactory::class),
            Mockery::spy(\Tracker_ArtifactFactory::class),
            Mockery::spy(\Tracker_FormElementFactory::class),
            Mockery::spy(\AgileDashboard_Milestone_MilestoneStatusCounter::class),
            Mockery::spy(\PlanningPermissionsManager::class),
            Mockery::spy(\AgileDashboard_Milestone_MilestoneDao::class),
            Mockery::mock(SemanticTimeframeBuilder::class),
            new NullLogger(),
            Mockery::spy(MilestoneBurndownFieldChecker::class)
        );
        $planning_items_tree = $factory->getPlannedArtifacts(Mockery::spy(\PFUser::class), $root_artifact);

        $children = $planning_items_tree->flattenChildren();

        $this->assertFalse(empty($children));
        foreach ($children as $tree_node) {
            $this->assertInstanceOf(Artifact::class, $tree_node->getObject());
        }
    }
}
