<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Velocity;

use AgileDashBoard_Semantic_InitialEffort;
use AgileDashboard_Semantic_InitialEffortFactory;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_List;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_Integer;
use Tracker_FormElement_Field_Selectbox;
use Tracker_Semantic_Status;
use Tuleap\AgileDashboard\Semantic\SemanticDone;
use Tuleap\AgileDashboard\Semantic\SemanticDoneFactory;

final class VelocityCalculatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var VelocityCalculator
     */
    private $calculator;

    private $artifact_factory;
    private $initial_effort_factory;
    private $semantic_done_factory;
    private $velocity_dao;
    private $artifact;
    private $tracker;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artifact_factory       = Mockery::mock(Tracker_ArtifactFactory::class);
        $this->initial_effort_factory = Mockery::mock(AgileDashboard_Semantic_InitialEffortFactory::class);
        $this->semantic_done_factory  = Mockery::mock(SemanticDoneFactory::class);
        $this->velocity_dao           = Mockery::mock(VelocityDao::class);

        $this->calculator = new VelocityCalculator(
            $this->artifact_factory,
            $this->initial_effort_factory,
            $this->semantic_done_factory,
            $this->velocity_dao
        );

        $this->artifact = Mockery::mock(Tracker_Artifact::class);
        $this->tracker  = Mockery::mock(Tracker::class);
        $this->user     = Mockery::mock(PFUser::class);

        $this->artifact->shouldReceive('getId')->andReturn(200);
    }

    public function testItCalculatesVelocityBasedOnInitialEffort()
    {
        $linked_artifact = $this->mockLinkedArtifact();

        $last_changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $linked_artifact->shouldReceive('getLastChangeset')->andReturn($last_changeset);

        $initial_effort_field = Mockery::mock(Tracker_FormElement_Field_Integer::class);

        $this->mockSemanticInitialEffort($linked_artifact, $initial_effort_field);
        $status_field = $this->mockSemanticDone();

        $last_changeset_value_list = Mockery::mock(Tracker_Artifact_ChangesetValue_List::class);
        $last_changeset_value_list->shouldReceive('getValue')
            ->once()
            ->andReturn([0 => '431']);

        $last_changeset->shouldReceive('getValue')
            ->with($status_field)
            ->once()
            ->andReturn($last_changeset_value_list);

        $calculated_effort = $this->calculator->calculate($this->artifact, $this->user);

        $this->assertSame(15, $calculated_effort);
    }

    public function testItCalculatesVelocityBasedOnInitialEffortBindedToAListField()
    {
        $linked_artifact = $this->mockLinkedArtifact();

        $last_changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $linked_artifact->shouldReceive('getLastChangeset')->andReturn($last_changeset);

        $initial_effort_field = Mockery::mock(Tracker_FormElement_Field_Selectbox::class);
        $this->mockSemanticInitialEffort($linked_artifact, $initial_effort_field);

        $status_field = $this->mockSemanticDone();

        $last_changeset_value_list = Mockery::mock(Tracker_Artifact_ChangesetValue_List::class);
        $last_changeset_value_list->shouldReceive('getValue')
            ->once()
            ->andReturn([0 => '431']);

        $last_changeset->shouldReceive('getValue')
            ->with($status_field)
            ->once()
            ->andReturn($last_changeset_value_list);

        $calculated_effort = $this->calculator->calculate($this->artifact, $this->user);

        $this->assertSame(15, $calculated_effort);
    }

    public function testItReturnsZeroIfNoLinkedArtifacts()
    {
        $this->velocity_dao->shouldReceive('searchPlanningLinkedArtifact')
            ->with(200)
            ->once()
            ->andReturn([]);

        $calculated_effort = $this->calculator->calculate($this->artifact, $this->user);

        $this->assertSame(0, $calculated_effort);
    }

    public function testItReturnsZeroIfLinkedArtifactsHAveNoInitialEffortSemantic()
    {
        $this->mockLinkedArtifact();

        $this->initial_effort_factory->shouldReceive('getByTracker')
            ->with($this->tracker)
            ->andReturnNull();

        $calculated_effort = $this->calculator->calculate($this->artifact, $this->user);

        $this->assertSame(0, $calculated_effort);
    }

    public function testItReturnsZeroIfLinkedArtifactsHAveNoSemanticInitialEffortField()
    {
        $this->mockLinkedArtifact();

        $this->mockSemanticInitialEffortWithoutField();

        $calculated_effort = $this->calculator->calculate($this->artifact, $this->user);

        $this->assertSame(0, $calculated_effort);
    }

    public function testItReturnsZeroIfLinkedArtifactsHAveNoDoneSemantic()
    {
        $linked_artifact      = $this->mockLinkedArtifact();
        $initial_effort_field = Mockery::mock(Tracker_FormElement_Field_Integer::class);

        $this->mockSemanticInitialEffort($linked_artifact, $initial_effort_field);

        $this->semantic_done_factory->shouldReceive('getInstanceByTracker')
            ->with($this->tracker)
            ->andReturnNull();

        $calculated_effort = $this->calculator->calculate($this->artifact, $this->user);

        $this->assertSame(0, $calculated_effort);
    }

    public function testItReturnsZeroIfLinkedArtifactsHaveNoLastChangeset()
    {
        $linked_artifact = $this->mockLinkedArtifact();
        $linked_artifact->shouldReceive('getLastChangeset')->andReturnNull();

        $initial_effort_field = Mockery::mock(Tracker_FormElement_Field_Integer::class);

        $this->mockSemanticInitialEffort($linked_artifact, $initial_effort_field);
        $this->mockSemanticDone();

        $calculated_effort = $this->calculator->calculate($this->artifact, $this->user);

        $this->assertSame(0, $calculated_effort);
    }

    public function testItReturnsZeroIfLinkedArtifactsLastChangesetHaveNoValueForInitialEffortField()
    {
        $linked_artifact = $this->mockLinkedArtifact();

        $initial_effort_field = Mockery::mock(Tracker_FormElement_Field_Integer::class);

        $this->mockSemanticInitialEffort($linked_artifact, $initial_effort_field);
        $status_field = $this->mockSemanticDone();

        $last_changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $last_changeset->shouldReceive('getValue')
            ->with($status_field)
            ->once()
            ->andReturnNull();

        $linked_artifact->shouldReceive('getLastChangeset')->andReturn($last_changeset);

        $calculated_effort = $this->calculator->calculate($this->artifact, $this->user);

        $this->assertSame(0, $calculated_effort);
    }

    public function testItReturnsZeroIfLinkedArtifactsValueForInitialEffortFieldIsNotANumeric()
    {
        $linked_artifact = $this->mockLinkedArtifact();

        $last_changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $linked_artifact->shouldReceive('getLastChangeset')->andReturn($last_changeset);

        $initial_effort_field = Mockery::mock(Tracker_FormElement_Field_Selectbox::class);
        $this->mockSemanticInitialEffortWithListValueNotInteger($linked_artifact, $initial_effort_field);

        $status_field = $this->mockSemanticDone();

        $last_changeset_value_list = Mockery::mock(Tracker_Artifact_ChangesetValue_List::class);
        $last_changeset_value_list->shouldReceive('getValue')
            ->once()
            ->andReturn([0 => '431']);

        $last_changeset->shouldReceive('getValue')
            ->with($status_field)
            ->once()
            ->andReturn($last_changeset_value_list);

        $calculated_effort = $this->calculator->calculate($this->artifact, $this->user);

        $this->assertSame(0, $calculated_effort);
    }

    private function mockSemanticInitialEffort(
        Tracker_Artifact $linked_artifact,
        Tracker_FormElement_Field $initial_effort_field
    ) {
        $initial_effort_field->shouldReceive('getComputedValue')
            ->with($this->user, $linked_artifact)
            ->andReturn(15);

        $semantic_initial_effort = Mockery::mock(AgileDashBoard_Semantic_InitialEffort::class);
        $semantic_initial_effort->shouldReceive('getField')->andReturn($initial_effort_field);

        $this->initial_effort_factory->shouldReceive('getByTracker')
            ->with($this->tracker)
            ->andReturn($semantic_initial_effort);
    }

    private function mockSemanticInitialEffortWithListValueNotInteger(
        Tracker_Artifact $linked_artifact,
        Tracker_FormElement_Field $initial_effort_field
    ) {
        $initial_effort_field->shouldReceive('getComputedValue')
            ->with($this->user, $linked_artifact)
            ->andReturnNull();

        $semantic_initial_effort = Mockery::mock(AgileDashBoard_Semantic_InitialEffort::class);
        $semantic_initial_effort->shouldReceive('getField')->andReturn($initial_effort_field);

        $this->initial_effort_factory->shouldReceive('getByTracker')
            ->with($this->tracker)
            ->andReturn($semantic_initial_effort);
    }

    private function mockSemanticInitialEffortWithoutField()
    {
        $semantic_initial_effort = Mockery::mock(AgileDashBoard_Semantic_InitialEffort::class);
        $semantic_initial_effort->shouldReceive('getField')->andReturnNull();

        $this->initial_effort_factory->shouldReceive('getByTracker')
            ->with($this->tracker)
            ->andReturn($semantic_initial_effort);
    }

    private function mockSemanticDone()
    {
        $status_field    = Mockery::mock(Tracker_FormElement_Field_Selectbox::class);
        $semantic_status = Mockery::mock(Tracker_Semantic_Status::class);
        $semantic_status->shouldReceive('getField')->andReturn($status_field);

        $semantic_done = Mockery::mock(SemanticDone::class);
        $semantic_done->shouldReceive('getSemanticStatus')->andReturn($semantic_status);
        $semantic_done->shouldReceive('getDoneValuesIds')->andReturn([
            430,
            431
        ]);

        $this->semantic_done_factory->shouldReceive('getInstanceByTracker')
            ->with($this->tracker)
            ->andReturn($semantic_done);

        return $status_field;
    }

    /**
     * @return Mockery\MockInterface|Tracker_Artifact
     */
    private function mockLinkedArtifact()
    {
        $this->velocity_dao->shouldReceive('searchPlanningLinkedArtifact')
            ->with(200)
            ->once()
            ->andReturn([
                ['id' => 201]
            ]);

        $linked_artifact = Mockery::mock(Tracker_Artifact::class);
        $linked_artifact->shouldReceive('getTracker')->andReturn($this->tracker);

        $this->artifact_factory->shouldReceive('getArtifactById')
            ->with(201)
            ->once()
            ->andReturn($linked_artifact);

        return $linked_artifact;
    }
}
