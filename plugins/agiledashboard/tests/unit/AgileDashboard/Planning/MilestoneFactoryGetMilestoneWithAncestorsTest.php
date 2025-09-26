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

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Planning_ArtifactMilestone;
use Planning_MilestoneFactory;
use Planning_NoMilestone;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MilestoneFactoryGetMilestoneWithAncestorsTest extends TestCase
{
    private Planning_ArtifactMilestone&MockObject $sprint_milestone;
    private Artifact&MockObject $sprint_artifact;
    private Planning_MilestoneFactory&MockObject $milestone_factory;
    private PFUser $current_user;

    #[\Override]
    protected function setUp(): void
    {
        $this->current_user      = UserTestBuilder::buildWithDefaults();
        $this->milestone_factory = $this->createPartialMock(Planning_MilestoneFactory::class, [
            'getMilestoneFromArtifact',
        ]);

        $this->sprint_artifact  = $this->createMock(Artifact::class);
        $this->sprint_milestone = $this->createMock(Planning_ArtifactMilestone::class);
        $this->sprint_milestone->method('getArtifact')->willReturn($this->sprint_artifact);
    }

    public function testItReturnsEmptyArrayIfThereIsNoArtifactInMilestone(): void
    {
        $empty_milestone = new Planning_NoMilestone(ProjectTestBuilder::aProject()->build(), PlanningBuilder::aPlanning(101)->build());

        $milestones = $this->milestone_factory->getMilestoneAncestors($this->current_user, $empty_milestone);
        self::assertEquals([], $milestones);
    }

    public function testItBuildTheMilestonesWhenNoParents(): void
    {
        $this->sprint_artifact->method('getAllAncestors')->with($this->current_user)->willReturn([]);

        $milestones = $this->milestone_factory->getMilestoneAncestors($this->current_user, $this->sprint_milestone);
        self::assertEquals([], $milestones);
    }

    public function testItBuildTheMilestoneForOneParent(): void
    {
        $release_artifact = ArtifactTestBuilder::anArtifact(1)->build();
        $this->sprint_artifact->method('getAllAncestors')->with($this->current_user)->willReturn([$release_artifact]);

        $release_milestone = $this->createMock(Planning_ArtifactMilestone::class);
        $this->milestone_factory->method('getMilestoneFromArtifact')->with($this->current_user, $release_artifact)->willReturn($release_milestone);

        $milestones = $this->milestone_factory->getMilestoneAncestors($this->current_user, $this->sprint_milestone);
        self::assertEquals([$release_milestone], $milestones);
    }

    public function testItBuildTheMilestoneForSeveralParents(): void
    {
        $release_artifact = ArtifactTestBuilder::anArtifact(1)->build();
        $product_artifact = ArtifactTestBuilder::anArtifact(2)->build();
        $this->sprint_artifact->method('getAllAncestors')->with($this->current_user)->willReturn([$release_artifact, $product_artifact]);

        $product_milestone = $this->createMock(Planning_ArtifactMilestone::class);
        $product_milestone->method('getArtifact')->willReturn($product_artifact);
        $release_milestone = $this->createMock(Planning_ArtifactMilestone::class);
        $release_milestone->method('getArtifact')->willReturn($release_artifact);
        $matcher = $this->exactly(2);
        $this->milestone_factory->expects($matcher)->method('getMilestoneFromArtifact')->willReturnCallback(function (...$parameters) use ($matcher, $release_artifact, $product_artifact, $release_milestone, $product_milestone) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($this->current_user, $parameters[0]);
                self::assertSame($release_artifact, $parameters[1]);
                return $release_milestone;
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($this->current_user, $parameters[0]);
                self::assertSame($product_artifact, $parameters[1]);
                return $product_milestone;
            }
        });

        $milestones = $this->milestone_factory->getMilestoneAncestors($this->current_user, $this->sprint_milestone);
        self::assertEquals([$release_milestone, $product_milestone], $milestones);
    }

    public function testItFiltersOutTheEmptyMilestones(): void
    {
        $release_artifact = ArtifactTestBuilder::anArtifact(1)->build();
        $this->sprint_artifact->method('getAllAncestors')->with($this->current_user)->willReturn([$release_artifact]);

        $this->milestone_factory->method('getMilestoneFromArtifact')->with($this->current_user, $release_artifact)->willReturn(null);

        $milestones = $this->milestone_factory->getMilestoneAncestors($this->current_user, $this->sprint_milestone);
        self::assertEquals([], $milestones);
    }
}
