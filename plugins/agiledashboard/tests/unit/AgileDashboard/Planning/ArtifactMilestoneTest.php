<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Planning;

use ArtifactNode;
use PHPUnit\Framework\MockObject\MockObject;
use Planning;
use Planning_ArtifactMilestone;
use Project;
use Tuleap\Date\DatePeriodWithOpenDays;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactMilestoneTest extends TestCase
{
    private Project $project;
    private Planning $planning;
    private Artifact&MockObject $artifact;
    private Planning_ArtifactMilestone $milestone;

    #[\Override]
    protected function setUp(): void
    {
        $this->project  = ProjectTestBuilder::aProject()->withId(123)->build();
        $this->planning = PlanningBuilder::aPlanning(123)->withId(9999)->build();
        $this->artifact = $this->createMock(Artifact::class);
        $this->artifact->method('getId')->willReturn(201);
        $this->milestone = new Planning_ArtifactMilestone(
            $this->project,
            $this->planning,
            $this->artifact,
        );
    }

    public function testItHasAPlanning(): void
    {
        self::assertSame($this->planning, $this->milestone->getPlanning());
        self::assertSame($this->planning->getId(), $this->milestone->getPlanningId());
    }

    public function testItHasAProject(): void
    {
        self::assertSame($this->project, $this->milestone->getProject());
        self::assertSame($this->project->getID(), $this->milestone->getGroupId());
    }

    public function testItRepresentsAnArtifact(): void
    {
        self::assertSame($this->artifact, $this->milestone->getArtifact());
        self::assertSame($this->artifact->getId(), $this->milestone->getArtifactId());
    }

    public function testItDelegatesArtifactTitleRetrieval(): void
    {
        $this->artifact->method('getTitle')->willReturn('Foo');

        self::assertSame($this->artifact->getTitle(), $this->milestone->getArtifactTitle());
        self::assertSame('Foo', $this->milestone->getArtifactTitle());
    }

    public function testItMayHavePlannedArtifacts(): void
    {
        $node_artifact = ArtifactTestBuilder::anArtifact(202)->build();

        $planned_artifacts = new ArtifactNode($node_artifact);

        $milestone = new Planning_ArtifactMilestone(
            $this->project,
            $this->planning,
            $this->artifact,
            $planned_artifacts,
        );

        self::assertSame($planned_artifacts, $milestone->getPlannedArtifacts());
    }

    public function testItGetsLinkedArtifactsOfTheRootLevelArtifact(): void
    {
        $this->artifact->method('getUniqueLinkedArtifacts')->willReturn([ArtifactTestBuilder::anArtifact(301)->build()]);

        $all_artifacts = $this->milestone->getLinkedArtifacts(UserTestBuilder::buildWithDefaults());

        self::assertCount(1, $all_artifacts);
    }

    public function testItGetsTheArtifactsChildNodes(): void
    {
        $this->artifact->method('getUniqueLinkedArtifacts')->willReturn([]);

        $root_artifact = $this->createMock(Artifact::class);
        $root_artifact->method('getId')->willReturn(9999);
        $root_artifact->method('getTitle')->willReturn('root artifact');
        $root_artifact->method('getUniqueLinkedArtifacts')->willReturn([]);

        $child1_artifact = $this->createMock(Artifact::class);
        $child1_artifact->method('getId')->willReturn(1111);
        $child1_artifact->method('getTitle')->willReturn('child artifact 1');
        $child1_artifact->method('getUniqueLinkedArtifacts')->willReturn([]);

        $child2_artifact = $this->createMock(Artifact::class);
        $child2_artifact->method('getId')->willReturn(2222);
        $child2_artifact->method('getTitle')->willReturn('child artifact 2');
        $child2_artifact->method('getUniqueLinkedArtifacts')->willReturn([]);

        $planned_artifacts    = new ArtifactNode($root_artifact);
        $child1_artifact_node = new ArtifactNode($child1_artifact);
        $child2_artifact_node = new ArtifactNode($child2_artifact);

        $planned_artifacts->addChildren($child1_artifact_node, $child2_artifact_node);

        $milestone = new Planning_ArtifactMilestone(
            $this->project,
            $this->planning,
            $this->artifact,
            $planned_artifacts,
        );

        $all_artifacts = $milestone->getLinkedArtifacts(UserTestBuilder::buildWithDefaults());

        self::assertCount(2, $all_artifacts);
    }

    public function testItGetsTheArtifactsOfNestedChildNodes(): void
    {
        $this->artifact->method('getUniqueLinkedArtifacts')->willReturn([]);

        $root_artifact = $this->createMock(Artifact::class);
        $root_artifact->method('getId')->willReturn(9999);
        $root_artifact->method('getTitle')->willReturn('root artifact');
        $root_artifact->method('getUniqueLinkedArtifacts')->willReturn([]);

        $depth1_artifact = $this->createMock(Artifact::class);
        $depth1_artifact->method('getId')->willReturn(1111);
        $depth1_artifact->method('getTitle')->willReturn('depth artifact 1');
        $depth1_artifact->method('getUniqueLinkedArtifacts')->willReturn([]);

        $depth2_artifact = $this->createMock(Artifact::class);
        $depth2_artifact->method('getId')->willReturn(2222);
        $depth2_artifact->method('getTitle')->willReturn('depth artifact 2');
        $depth2_artifact->method('getUniqueLinkedArtifacts')->willReturn([]);

        $planned_artifacts    = new ArtifactNode($root_artifact);
        $depth1_artifact_node = new ArtifactNode($depth1_artifact);
        $depth2_artifact_node = new ArtifactNode($depth2_artifact);

        $depth1_artifact_node->addChild($depth2_artifact_node);
        $planned_artifacts->addChild($depth1_artifact_node);

        $milestone = new Planning_ArtifactMilestone(
            $this->project,
            $this->planning,
            $this->artifact,
            $planned_artifacts,
        );

        $all_artifacts = $milestone->getLinkedArtifacts(UserTestBuilder::buildWithDefaults());

        self::assertCount(2, $all_artifacts);
    }

    public function testItGetsTheLinkedArtifactsOfChildNodes(): void
    {
        $this->artifact->method('getUniqueLinkedArtifacts')->willReturn([]);

        $root_artifact = $this->createMock(Artifact::class);
        $root_artifact->method('getId')->willReturn(9999);
        $root_artifact->method('getTitle')->willReturn('root artifact');
        $root_artifact->method('getUniqueLinkedArtifacts')->willReturn([]);

        $linked_artifact_1 = $this->createMock(Artifact::class);
        $linked_artifact_1->method('getId')->willReturn(1111);
        $linked_artifact_1->method('getTitle')->willReturn('depth artifact 1');
        $linked_artifact_1->method('getUniqueLinkedArtifacts')->willReturn([]);

        $linked_artifact_2 = $this->createMock(Artifact::class);
        $linked_artifact_2->method('getId')->willReturn(2222);
        $linked_artifact_2->method('getTitle')->willReturn('depth artifact 2');
        $linked_artifact_2->method('getUniqueLinkedArtifacts')->willReturn([]);

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getId')->willReturn(4444);
        $artifact->method('getTitle')->willReturn('artifact');
        $artifact->method('getUniqueLinkedArtifacts')->willReturn([$linked_artifact_1, $linked_artifact_2]);

        $planned_artifacts = new ArtifactNode($root_artifact);
        $artifact_node     = new ArtifactNode($artifact);

        $planned_artifacts->addChild($artifact_node);

        $milestone = new Planning_ArtifactMilestone(
            $this->project,
            $this->planning,
            $this->artifact,
            $planned_artifacts,
        );

        $all_artifacts = $milestone->getLinkedArtifacts(UserTestBuilder::buildWithDefaults());

        self::assertCount(3, $all_artifacts);
    }

    public function testEndDateIsNullIfNoStartDate(): void
    {
        $date_period = DatePeriodWithOpenDays::buildFromDuration(0, 10);
        $this->milestone->setDatePeriod($date_period);

        self::assertNull($this->milestone->getEndDate());
    }

    public function testEndDateIsNullIfNoDuration(): void
    {
        $date_period = DatePeriodWithOpenDays::buildFromDuration(10, 0);
        $this->milestone->setDatePeriod($date_period);

        self::assertNull($this->milestone->getEndDate());
    }

    public function testEndDateIsNullIfNegativeDuration(): void
    {
        $date_period = DatePeriodWithOpenDays::buildFromDuration(10, -2);
        $this->milestone->setDatePeriod($date_period);

        $this->assertNull($this->milestone->getEndDate());
    }
}
