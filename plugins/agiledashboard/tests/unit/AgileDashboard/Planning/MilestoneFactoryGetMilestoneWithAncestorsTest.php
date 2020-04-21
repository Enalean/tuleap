<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
use PFUser;
use PHPUnit\Framework\TestCase;
use Planning;
use Planning_ArtifactMilestone;
use Planning_MilestoneFactory;
use Planning_NoMilestone;
use Project;
use Tracker_Artifact;

final class MilestoneFactoryGetMilestoneWithAncestorsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Planning_ArtifactMilestone
     */
    private $sprint_milestone;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Artifact
     */
    private $sprint_artifact;
    /**
     * @var Mockery\Mock|Planning_MilestoneFactory
     */
    private $milestone_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $current_user;

    protected function setUp(): void
    {
        $this->current_user      = Mockery::mock(PFUser::class);
        $this->milestone_factory = Mockery::mock(Planning_MilestoneFactory::class)
            ->makePartial()->shouldAllowMockingProtectedMethods();

        $this->sprint_artifact  = Mockery::spy(Tracker_Artifact::class);
        $this->sprint_milestone = Mockery::mock(Planning_ArtifactMilestone::class);
        $this->sprint_milestone->shouldReceive('getArtifact')->andReturn($this->sprint_artifact);
    }

    public function testItReturnsEmptyArrayIfThereIsNoArtifactInMilestone(): void
    {
        $empty_milestone = new Planning_NoMilestone(Mockery::spy(Project::class), Mockery::spy(Planning::class));

        $milestones = $this->milestone_factory->getMilestoneAncestors($this->current_user, $empty_milestone);
        $this->assertEquals([], $milestones);
    }

    public function testItBuildTheMilestonesWhenNoParents(): void
    {
        $this->sprint_artifact->shouldReceive('getAllAncestors')->with($this->current_user)->andReturn([]);

        $milestones = $this->milestone_factory->getMilestoneAncestors($this->current_user, $this->sprint_milestone);
        $this->assertEquals([], $milestones);
    }

    public function testItBuildTheMilestoneForOneParent(): void
    {
        $release_artifact = Mockery::mock(Tracker_Artifact::class);
        $this->sprint_artifact->shouldReceive('getAllAncestors')
            ->with($this->current_user)
            ->andReturn([$release_artifact]);

        $release_milestone = Mockery::spy(Planning_ArtifactMilestone::class);
        $this->milestone_factory->shouldReceive('getMilestoneFromArtifact')
            ->with($release_artifact)
            ->andReturn($release_milestone);

        $milestones = $this->milestone_factory->getMilestoneAncestors($this->current_user, $this->sprint_milestone);
        $this->assertEquals([$release_milestone], $milestones);
    }

    public function testItBuildTheMilestoneForSeveralParents(): void
    {
        $release_artifact = Mockery::mock(Tracker_Artifact::class);
        $product_artifact = Mockery::mock(Tracker_Artifact::class);
        $this->sprint_artifact->shouldReceive('getAllAncestors')
            ->with($this->current_user)
            ->andReturn([$release_artifact, $product_artifact]);

        $product_milestone = Mockery::mock(Planning_ArtifactMilestone::class);
        $product_milestone->shouldReceive('getArtifact')->andReturn($product_artifact);
        $release_milestone = Mockery::mock(Planning_ArtifactMilestone::class);
        $release_milestone->shouldReceive('getArtifact')->andReturn($release_artifact);
        $this->milestone_factory->shouldReceive('getMilestoneFromArtifact')
            ->with($product_artifact)
            ->andReturn($product_milestone);
        $this->milestone_factory->shouldReceive('getMilestoneFromArtifact')
            ->with($release_artifact)
            ->andReturn($release_milestone);

        $milestones = $this->milestone_factory->getMilestoneAncestors($this->current_user, $this->sprint_milestone);
        $this->assertEquals([$release_milestone, $product_milestone], $milestones);
    }

    public function testItFiltersOutTheEmptyMilestones(): void
    {
        $release_artifact = Mockery::mock(Tracker_Artifact::class);
        $this->sprint_artifact->shouldReceive('getAllAncestors')
            ->with($this->current_user)
            ->andReturn([$release_artifact]);

        $this->milestone_factory->shouldReceive('getMilestoneFromArtifact')->with($release_artifact)->andReturn(null);

        $milestones = $this->milestone_factory->getMilestoneAncestors($this->current_user, $this->sprint_milestone);
        $this->assertEquals([], $milestones);
    }
}
