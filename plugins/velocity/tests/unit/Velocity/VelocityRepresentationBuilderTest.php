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

namespace Tuleap\Velocity;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Planning_Milestone;
use Planning_MilestoneFactory;
use Tracker;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue;
use Tracker_FormElement_Field_Float;
use Tuleap\AgileDashboard\Semantic\SemanticDone;
use Tuleap\AgileDashboard\Semantic\SemanticDoneFactory;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Velocity\Semantic\SemanticVelocity;
use Tuleap\Velocity\Semantic\SemanticVelocityFactory;

class VelocityRepresentationBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var VelocityRepresentationBuilder
     */
    private $builder;

    private $milestone_factory;
    private $semantic_velocity_factory;
    private $semantic_done_factory;
    private $semantic_timeframe_builder;
    private $milestone;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->milestone_factory          = Mockery::mock(Planning_MilestoneFactory::class);
        $this->semantic_velocity_factory  = Mockery::mock(SemanticVelocityFactory::class);
        $this->semantic_done_factory      = Mockery::mock(SemanticDoneFactory::class);
        $this->semantic_timeframe_builder = Mockery::mock(SemanticTimeframeBuilder::class);

        $this->builder = new VelocityRepresentationBuilder(
            $this->semantic_velocity_factory,
            $this->semantic_done_factory,
            $this->semantic_timeframe_builder,
            $this->milestone_factory
        );

        $this->milestone = Mockery::mock(Planning_Milestone::class);
        $this->user      = Mockery::mock(PFUser::class);
    }

    public function testItReturnsACollectionOfVelocityRepresentation()
    {
        $tracker = Mockery::mock(Tracker::class);

        $velocity_field = Mockery::mock(Tracker_FormElement_Field_Float::class);

        $last_changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue::class);
        $last_changeset_value->shouldReceive('getNumeric')->andReturn(10);

        $last_changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $last_changeset->shouldReceive('getValue')->with($velocity_field)->andReturn($last_changeset_value);

        $linked_artifact = Mockery::mock(Artifact::class);
        $linked_artifact->shouldReceive('getTracker')->andReturn($tracker);
        $linked_artifact->shouldReceive('getLastChangeset')->andReturn($last_changeset);
        $linked_artifact->shouldReceive('getId')->andReturn(102);
        $linked_artifact->shouldReceive('getTitle')->andReturn('Sprint 01');

        $semantic_velocity  = Mockery::mock(SemanticVelocity::class);
        $semantic_done      = Mockery::mock(SemanticDone::class);
        $timeframe_semantic = Mockery::mock(SemanticTimeframe::class);

        $this->semantic_velocity_factory->shouldReceive('getInstanceByTracker')
            ->with($tracker)
            ->andReturn($semantic_velocity);

        $this->semantic_done_factory->shouldReceive('getInstanceByTracker')
            ->with($tracker)
            ->andReturn($semantic_done);

        $this->semantic_timeframe_builder
            ->shouldReceive("getSemantic")
            ->andReturn($timeframe_semantic);

        $semantic_velocity->shouldReceive('getVelocityField')->andReturn($velocity_field);
        $semantic_done->shouldReceive('isDone')->with($last_changeset)->andReturnTrue();
        $timeframe_semantic
            ->shouldReceive("isDefined")
            ->andReturnTrue();

        $sub_milestone = Mockery::mock(Planning_Milestone::class);
        $sub_milestone->shouldReceive('getArtifact')
            ->andReturn($linked_artifact);

        $this->milestone_factory->shouldReceive('updateMilestoneContextualInfo')->with($this->user, $sub_milestone)->once();
        $this->milestone_factory
            ->shouldReceive('getSubMilestones')
            ->with($this->user, $this->milestone)
            ->andReturn([$sub_milestone]);

        $sub_milestone->shouldReceive('getStartDate')->andReturn(1);
        $sub_milestone->shouldReceive('getDuration')->andReturn(1);

        $collection = $this->builder->buildCollectionOfRepresentations($this->milestone, $this->user);

        $this->assertCount(1, $collection->getVelocityRepresentations());
        $this->assertCount(0, $collection->getInvalidArtifacts());
    }

    public function testItDoesNotAddInCollectionIfNoSemanticDoneDefined()
    {
        $tracker = Mockery::mock(Tracker::class);

        $velocity_field = Mockery::mock(Tracker_FormElement_Field_Float::class);

        $last_changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $last_changeset->shouldReceive('getValue')->with($velocity_field)->never();

        $linked_artifact = Mockery::mock(Artifact::class);
        $linked_artifact->shouldReceive('getTracker')->andReturn($tracker);
        $linked_artifact->shouldReceive('getLastChangeset')->andReturn($last_changeset);
        $linked_artifact->shouldReceive('getId')->andReturn(102);
        $linked_artifact->shouldReceive('getTitle')->andReturn('Sprint 01');

        $semantic_velocity  = Mockery::mock(SemanticVelocity::class);
        $semantic_done      = Mockery::mock(SemanticDone::class);
        $timeframe_semantic = Mockery::mock(SemanticTimeframe::class);

        $this->semantic_velocity_factory->shouldReceive('getInstanceByTracker')
            ->with($tracker)
            ->andReturn($semantic_velocity);

        $this->semantic_done_factory->shouldReceive('getInstanceByTracker')
            ->with($tracker)
            ->andReturn($semantic_done);

        $this->semantic_timeframe_builder
            ->shouldReceive("getSemantic")
            ->andReturn($timeframe_semantic);

        $semantic_velocity->shouldReceive('getVelocityField')->andReturn($velocity_field);
        $semantic_done->shouldReceive('isDone')->with($last_changeset)->andReturnFalse();
        $timeframe_semantic
            ->shouldReceive("isDefined")
            ->andReturnTrue();

        $sub_milestone = Mockery::mock(Planning_Milestone::class);
        $sub_milestone->shouldReceive('getArtifact')
            ->andReturn($linked_artifact);

        $this->milestone_factory->shouldReceive('updateMilestoneContextualInfo')->with($this->user, $sub_milestone)->never();
        $this->milestone_factory
            ->shouldReceive('getSubMilestones')
            ->with($this->user, $this->milestone)
            ->andReturn([$sub_milestone]);

        $collection = $this->builder->buildCollectionOfRepresentations($this->milestone, $this->user);

        $this->assertCount(0, $collection->getVelocityRepresentations());
        $this->assertCount(0, $collection->getInvalidArtifacts());
    }

    public function testItDoesNotAddInCollectionIfNoSemanticVelocityDefined()
    {
        $tracker = Mockery::mock(Tracker::class);

        $linked_artifact = Mockery::mock(Artifact::class);
        $linked_artifact->shouldReceive('getTracker')->andReturn($tracker);
        $linked_artifact->shouldReceive('getId')->andReturn(102);
        $linked_artifact->shouldReceive('getTitle')->andReturn('Sprint 01');

        $semantic_velocity  = Mockery::mock(SemanticVelocity::class);
        $semantic_done      = Mockery::mock(SemanticDone::class);
        $timeframe_semantic = Mockery::mock(SemanticTimeframe::class);

        $this->semantic_velocity_factory->shouldReceive('getInstanceByTracker')
            ->with($tracker)
            ->andReturn($semantic_velocity);

        $this->semantic_done_factory->shouldReceive('getInstanceByTracker')
            ->with($tracker)
            ->andReturn($semantic_done);

        $this->semantic_timeframe_builder
            ->shouldReceive("getSemantic")
            ->andReturn($timeframe_semantic);

        $semantic_velocity->shouldReceive('getVelocityField')->andReturnNull();
        $semantic_done->shouldReceive('isDone')->never();
        $timeframe_semantic
            ->shouldReceive("isDefined")
            ->andReturnTrue();

        $sub_milestone = Mockery::mock(Planning_Milestone::class);
        $sub_milestone->shouldReceive('getArtifact')
            ->andReturn($linked_artifact);

        $this->milestone_factory->shouldReceive('updateMilestoneContextualInfo')->with($this->user, $sub_milestone)->never();
        $this->milestone_factory
            ->shouldReceive('getSubMilestones')
            ->with($this->user, $this->milestone)
            ->andReturn([$sub_milestone]);

        $collection = $this->builder->buildCollectionOfRepresentations($this->milestone, $this->user);

        $this->assertCount(0, $collection->getVelocityRepresentations());
        $this->assertCount(0, $collection->getInvalidArtifacts());
    }

    public function testItAddsInCollectionAsInvalidIfNoStartDateDefined()
    {
        $tracker = Mockery::mock(Tracker::class);

        $velocity_field = Mockery::mock(Tracker_FormElement_Field_Float::class);

        $last_changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue::class);
        $last_changeset_value->shouldReceive('getNumeric')->andReturn(10);

        $last_changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $last_changeset->shouldReceive('getValue')->with($velocity_field)->andReturn($last_changeset_value);

        $linked_artifact = Mockery::mock(Artifact::class);
        $linked_artifact->shouldReceive('getTracker')->andReturn($tracker);
        $linked_artifact->shouldReceive('getLastChangeset')->andReturn($last_changeset);
        $linked_artifact->shouldReceive('getId')->andReturn(102);
        $linked_artifact->shouldReceive('getTitle')->andReturn('Sprint 01');
        $linked_artifact->shouldReceive('getXref')->andReturn('art #102 Sprint 01');
        $linked_artifact->shouldReceive('getUri')->andReturn('artifacts/102');

        $semantic_velocity  = Mockery::mock(SemanticVelocity::class);
        $semantic_done      = Mockery::mock(SemanticDone::class);
        $timeframe_semantic = Mockery::mock(SemanticTimeframe::class);

        $this->semantic_velocity_factory->shouldReceive('getInstanceByTracker')
            ->with($tracker)
            ->andReturn($semantic_velocity);

        $this->semantic_done_factory->shouldReceive('getInstanceByTracker')
            ->with($tracker)
            ->andReturn($semantic_done);

        $this->semantic_timeframe_builder
            ->shouldReceive("getSemantic")
            ->andReturn($timeframe_semantic);

        $semantic_velocity->shouldReceive('getVelocityField')->andReturn($velocity_field);
        $semantic_done->shouldReceive('isDone')->with($last_changeset)->andReturnTrue();
        $timeframe_semantic
            ->shouldReceive("isDefined")
            ->andReturnTrue();

        $sub_milestone = Mockery::mock(Planning_Milestone::class);
        $sub_milestone->shouldReceive('getArtifact')
            ->andReturn($linked_artifact);

        $this->milestone_factory->shouldReceive('updateMilestoneContextualInfo')->with($this->user, $sub_milestone)->once();
        $this->milestone_factory
            ->shouldReceive('getSubMilestones')
            ->with($this->user, $this->milestone)
            ->andReturn([$sub_milestone]);

        $sub_milestone->shouldReceive('getStartDate')->andReturnNull();
        $sub_milestone->shouldReceive('getDuration')->andReturn(1);

        $collection = $this->builder->buildCollectionOfRepresentations($this->milestone, $this->user);

        $this->assertCount(0, $collection->getVelocityRepresentations());
        $this->assertCount(1, $collection->getInvalidArtifacts());
    }

    public function testItAddsTrackerNameToInvalidTrackersNamesWhenNoTimeframeSemanticIsDefined()
    {
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getName')->andReturn('Sprints');

        $velocity_field = Mockery::mock(Tracker_FormElement_Field_Float::class);

        $last_changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue::class);
        $last_changeset_value->shouldReceive('getNumeric')->andReturn(10);

        $last_changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $last_changeset->shouldReceive('getValue')->with($velocity_field)->andReturn($last_changeset_value);

        $linked_artifact = Mockery::mock(Artifact::class);
        $linked_artifact->shouldReceive('getTracker')->andReturn($tracker);
        $linked_artifact->shouldReceive('getLastChangeset')->andReturn($last_changeset);
        $linked_artifact->shouldReceive('getId')->andReturn(102);
        $linked_artifact->shouldReceive('getTitle')->andReturn('Sprint 01');

        $semantic_velocity  = Mockery::mock(SemanticVelocity::class);
        $semantic_done      = Mockery::mock(SemanticDone::class);
        $semantic_timeframe = Mockery::mock(SemanticTimeframe::class);

        $this->semantic_velocity_factory->shouldReceive('getInstanceByTracker')
            ->with($tracker)
            ->andReturn($semantic_velocity);

        $this->semantic_done_factory->shouldReceive('getInstanceByTracker')
            ->with($tracker)
            ->andReturn($semantic_done);

        $this->semantic_timeframe_builder->shouldReceive('getSemantic')
            ->with($tracker)
            ->andReturn($semantic_timeframe);

        $semantic_velocity->shouldReceive('getVelocityField')->andReturn($velocity_field);
        $semantic_done->shouldReceive('isDone')->with($last_changeset)->andReturnTrue();
        $semantic_timeframe->shouldReceive('isDefined')->andReturnFalse();

        $sub_milestone = Mockery::mock(Planning_Milestone::class);
        $sub_milestone->shouldReceive('getArtifact')
            ->andReturn($linked_artifact);

        $this->milestone_factory->shouldReceive('updateMilestoneContextualInfo')->with($this->user, $sub_milestone)->never();
        $this->milestone_factory
            ->shouldReceive('getSubMilestones')
            ->with($this->user, $this->milestone)
            ->andReturn([$sub_milestone]);

        $sub_milestone->shouldReceive('getStartDate')->andReturn(1);
        $sub_milestone->shouldReceive('getDuration')->andReturn(1);

        $collection = $this->builder->buildCollectionOfRepresentations($this->milestone, $this->user);

        $this->assertCount(0, $collection->getVelocityRepresentations());
        $this->assertCount(0, $collection->getInvalidArtifacts());
        $this->assertCount(1, $collection->getInvalidTrackersNames());
        $this->assertEquals('Sprints', $collection->getInvalidTrackersNames()[0]);
    }

    public function testItDoesNotAddLinkedArtifactsWhichAreNotASubmilestone()
    {
        $tracker = Mockery::mock(Tracker::class);

        $velocity_field = Mockery::mock(Tracker_FormElement_Field_Float::class);

        $last_changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue::class);
        $last_changeset_value->shouldReceive('getNumeric')->andReturn(10);

        $last_changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $last_changeset->shouldReceive('getValue')->with($velocity_field)->andReturn($last_changeset_value);

        $linked_artifact = Mockery::mock(Artifact::class);
        $linked_artifact->shouldReceive('getTracker')->andReturn($tracker);
        $linked_artifact->shouldReceive('getLastChangeset')->andReturn($last_changeset);
        $linked_artifact->shouldReceive('getId')->andReturn(102);
        $linked_artifact->shouldReceive('getTitle')->andReturn('Sprint 01');

        $semantic_velocity  = Mockery::mock(SemanticVelocity::class);
        $semantic_done      = Mockery::mock(SemanticDone::class);
        $timeframe_semantic = Mockery::mock(SemanticTimeframe::class);

        $this->semantic_velocity_factory->shouldReceive('getInstanceByTracker')
            ->with($tracker)
            ->andReturn($semantic_velocity);

        $this->semantic_done_factory->shouldReceive('getInstanceByTracker')
            ->with($tracker)
            ->andReturn($semantic_done);

        $this->semantic_timeframe_builder
            ->shouldReceive("getSemantic")
            ->andReturn($timeframe_semantic);

        $semantic_velocity->shouldReceive('getVelocityField')->andReturn($velocity_field);
        $semantic_done->shouldReceive('isDone')->with($last_changeset)->andReturnTrue();
        $timeframe_semantic
            ->shouldReceive("isDefined")
            ->andReturnTrue();

        $sub_milestone = Mockery::mock(Planning_Milestone::class);
        $sub_milestone->shouldReceive('getArtifact')
            ->andReturn($linked_artifact);

        $this->milestone_factory->shouldReceive('updateMilestoneContextualInfo')->with($this->user, $sub_milestone)->once();
        $this->milestone_factory
            ->shouldReceive('getSubMilestones')
            ->with($this->user, $this->milestone)
            ->andReturn([$sub_milestone]);

        $sub_milestone->shouldReceive('getStartDate')->andReturn(1);
        $sub_milestone->shouldReceive('getDuration')->andReturn(1);

        $collection = $this->builder->buildCollectionOfRepresentations($this->milestone, $this->user);

        $this->milestone->shouldReceive('getLinkedArtifacts')->never();
        $this->assertCount(1, $collection->getVelocityRepresentations());
        $this->assertCount(0, $collection->getInvalidArtifacts());
    }
}
