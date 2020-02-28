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

declare(strict_types = 1);

namespace Tuleap\AgileDashboard\Planning;

use ArtifactNode;
use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use Planning_MilestoneFactory;
use Tracker_Artifact;

final class MilestoneFactoryGetMilestoneFromArtifactWithPlannedArtifactsTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItCreateMilestoneFromArtifactAndLoadsItsPlannedArtifacts(): void
    {
        $milestone_factory = Mockery::mock(Planning_MilestoneFactory::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $user     = Mockery::mock(PFUser::class);
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(101);
        $artifact2 = Mockery::mock(Tracker_Artifact::class);
        $artifact2->shouldReceive('getId')->andReturn(102);
        $artifact3 = Mockery::mock(Tracker_Artifact::class);
        $artifact3->shouldReceive('getId')->andReturn(103);

        $node = new ArtifactNode($artifact);
        $node->addChild(new ArtifactNode($artifact2));
        $node->addChild(new ArtifactNode($artifact3));

        $milestone_factory->shouldReceive('getPlannedArtifacts')->with($user, $artifact)->once()->andReturn($node);
        $milestone_factory->shouldReceive('getMilestoneFromArtifact')->with($artifact, $node)->once();

        $milestone_factory->getMilestoneFromArtifactWithPlannedArtifacts($artifact, $user);
    }
}
