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

namespace Tuleap\AgileDashboard\Planning;

use ArtifactNode;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Planning;
use Planning_ArtifactMilestone;
use Project;
use Tuleap\Date\DatePeriodWithoutWeekEnd;
use Tuleap\Tracker\Artifact\Artifact;

require_once __DIR__ . '/../../bootstrap.php';

final class ArtifactMilestoneTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private $project;
    private $planning;
    private $artifact;

    /**
     * @var Planning_ArtifactMilestone
     */
    private $milestone;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Mockery::mock(Project::class);
        $this->project->shouldReceive('getID')->andReturn(123);

        $this->planning = Mockery::mock(Planning::class);
        $this->planning->shouldReceive('getId')->andReturn(9999);

        $this->artifact = Mockery::mock(Artifact::class);
        $this->artifact->shouldReceive('getId')->andReturn(201);

        $this->milestone = new Planning_ArtifactMilestone(
            $this->project,
            $this->planning,
            $this->artifact,
        );
    }

    public function testItHasAPlanning()
    {
        $this->assertSame($this->planning, $this->milestone->getPlanning());
        $this->assertSame($this->planning->getId(), $this->milestone->getPlanningId());
    }

    public function testItHasAProject()
    {
        $this->assertSame($this->project, $this->milestone->getProject());
        $this->assertSame($this->project->getID(), $this->milestone->getGroupId());
    }

    public function testItRepresentsAnArtifact()
    {
        $this->assertSame($this->artifact, $this->milestone->getArtifact());
        $this->assertSame($this->artifact->getId(), $this->milestone->getArtifactId());
    }

    public function testItDelegatesArtifactTitleRetrieval()
    {
        $this->artifact->shouldReceive('getTitle')->andReturn('Foo');

        $this->assertSame($this->artifact->getTitle(), $this->milestone->getArtifactTitle());
        $this->assertSame('Foo', $this->milestone->getArtifactTitle());
    }

    public function testItMayHavePlannedArtifacts()
    {
        $node_artifact = Mockery::mock(Artifact::class);
        $node_artifact->shouldReceive('getId')->andReturn(202);

        $planned_artifacts = new ArtifactNode($node_artifact);

        $milestone = new Planning_ArtifactMilestone(
            $this->project,
            $this->planning,
            $this->artifact,
            $planned_artifacts,
        );


        $this->assertSame($planned_artifacts, $milestone->getPlannedArtifacts());
    }

    public function testItGetsLinkedArtifactsOfTheRootLevelArtifact()
    {
        $this->artifact->shouldReceive('getUniqueLinkedArtifacts')->andReturn([
            Mockery::mock(Artifact::class),
        ]);

        $all_artifacts = $this->milestone->getLinkedArtifacts(Mockery::mock(PFUser::class));

        $this->assertCount(1, $all_artifacts);
    }

    public function testItGetsTheArtifactsChildNodes()
    {
        $this->artifact->shouldReceive('getUniqueLinkedArtifacts')->andReturn([]);

        $root_artifact = Mockery::mock(Artifact::class);
        $root_artifact->shouldReceive('getId')->andReturn(9999);
        $root_artifact->shouldReceive('getTitle')->andReturn('root artifact');
        $root_artifact->shouldReceive('getUniqueLinkedArtifacts')->andReturn([]);

        $child1_artifact = Mockery::mock(Artifact::class);
        $child1_artifact->shouldReceive('getId')->andReturn(1111);
        $child1_artifact->shouldReceive('getTitle')->andReturn('child artifact 1');
        $child1_artifact->shouldReceive('getUniqueLinkedArtifacts')->andReturn([]);

        $child2_artifact = Mockery::mock(Artifact::class);
        $child2_artifact->shouldReceive('getId')->andReturn(2222);
        $child2_artifact->shouldReceive('getTitle')->andReturn('child artifact 2');
        $child2_artifact->shouldReceive('getUniqueLinkedArtifacts')->andReturn([]);

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

        $all_artifacts = $milestone->getLinkedArtifacts(Mockery::mock(PFUser::class));

        $this->assertCount(2, $all_artifacts);
    }

    public function testItGetsTheArtifactsOfNestedChildNodes()
    {
        $this->artifact->shouldReceive('getUniqueLinkedArtifacts')->andReturn([]);

        $root_artifact = Mockery::mock(Artifact::class);
        $root_artifact->shouldReceive('getId')->andReturn(9999);
        $root_artifact->shouldReceive('getTitle')->andReturn('root artifact');
        $root_artifact->shouldReceive('getUniqueLinkedArtifacts')->andReturn([]);

        $depth1_artifact = Mockery::mock(Artifact::class);
        $depth1_artifact->shouldReceive('getId')->andReturn(1111);
        $depth1_artifact->shouldReceive('getTitle')->andReturn('depth artifact 1');
        $depth1_artifact->shouldReceive('getUniqueLinkedArtifacts')->andReturn([]);

        $depth2_artifact = Mockery::mock(Artifact::class);
        $depth2_artifact->shouldReceive('getId')->andReturn(2222);
        $depth2_artifact->shouldReceive('getTitle')->andReturn('depth artifact 2');
        $depth2_artifact->shouldReceive('getUniqueLinkedArtifacts')->andReturn([]);

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

        $all_artifacts = $milestone->getLinkedArtifacts(Mockery::mock(PFUser::class));

        $this->assertCount(2, $all_artifacts);
    }

    public function testItGetsTheLinkedArtifactsOfChildNodes()
    {
        $this->artifact->shouldReceive('getUniqueLinkedArtifacts')->andReturn([]);

        $root_artifact = Mockery::mock(Artifact::class);
        $root_artifact->shouldReceive('getId')->andReturn(9999);
        $root_artifact->shouldReceive('getTitle')->andReturn('root artifact');
        $root_artifact->shouldReceive('getUniqueLinkedArtifacts')->andReturn([]);

        $linked_artifact_1 = Mockery::mock(Artifact::class);
        $linked_artifact_1->shouldReceive('getId')->andReturn(1111);
        $linked_artifact_1->shouldReceive('getTitle')->andReturn('depth artifact 1');
        $linked_artifact_1->shouldReceive('getUniqueLinkedArtifacts')->andReturn([]);

        $linked_artifact_2 = Mockery::mock(Artifact::class);
        $linked_artifact_2->shouldReceive('getId')->andReturn(2222);
        $linked_artifact_2->shouldReceive('getTitle')->andReturn('depth artifact 2');
        $linked_artifact_2->shouldReceive('getUniqueLinkedArtifacts')->andReturn([]);

        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(4444);
        $artifact->shouldReceive('getTitle')->andReturn('artifact');
        $artifact->shouldReceive('getUniqueLinkedArtifacts')->andReturn([$linked_artifact_1, $linked_artifact_2]);

        $planned_artifacts = new ArtifactNode($root_artifact);
        $artifact_node     = new ArtifactNode($artifact);

        $planned_artifacts->addChild($artifact_node);

        $milestone = new Planning_ArtifactMilestone(
            $this->project,
            $this->planning,
            $this->artifact,
            $planned_artifacts,
        );

        $all_artifacts = $milestone->getLinkedArtifacts(Mockery::mock(PFUser::class));

        $this->assertCount(3, $all_artifacts);
    }

    public function testEndDateIsNullIfNoStartDate()
    {
        $date_period = DatePeriodWithoutWeekEnd::buildFromDuration(0, 10);
        $this->milestone->setDatePeriod($date_period);

        $this->assertNull($this->milestone->getEndDate());
    }

    public function testEndDateIsNullIfNoDuration()
    {
        $date_period = DatePeriodWithoutWeekEnd::buildFromDuration(10, 0);
        $this->milestone->setDatePeriod($date_period);

        $this->assertNull($this->milestone->getEndDate());
    }

    public function testEndDateIsNullIfNegativeDuration()
    {
        $date_period = DatePeriodWithoutWeekEnd::buildFromDuration(10, -2);
        $this->milestone->setDatePeriod($date_period);

        $this->assertNull($this->milestone->getEndDate());
    }
}
