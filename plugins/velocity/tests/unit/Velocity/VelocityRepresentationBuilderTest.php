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

namespace Tuleap\Velocity;

use PFUser;
use Planning_Milestone;
use Planning_MilestoneFactory;
use Tracker;
use Tracker_Artifact_Changeset;
use Tracker_FormElement_Field_Float;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDone;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneFactory;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Velocity\Semantic\SemanticVelocity;
use Tuleap\Velocity\Semantic\SemanticVelocityFactory;

final class VelocityRepresentationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private VelocityRepresentationBuilder $builder;
    private Planning_MilestoneFactory&\PHPUnit\Framework\MockObject\MockObject $milestone_factory;
    private \PHPUnit\Framework\MockObject\MockObject&SemanticVelocityFactory $semantic_velocity_factory;
    private SemanticDoneFactory&\PHPUnit\Framework\MockObject\MockObject $semantic_done_factory;
    private \PHPUnit\Framework\MockObject\MockObject&SemanticTimeframeBuilder $semantic_timeframe_builder;
    private \PHPUnit\Framework\MockObject\MockObject&Planning_Milestone $milestone;
    private \PHPUnit\Framework\MockObject\MockObject&PFUser $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->milestone_factory          = $this->createMock(Planning_MilestoneFactory::class);
        $this->semantic_velocity_factory  = $this->createMock(SemanticVelocityFactory::class);
        $this->semantic_done_factory      = $this->createMock(SemanticDoneFactory::class);
        $this->semantic_timeframe_builder = $this->createMock(SemanticTimeframeBuilder::class);

        $this->builder = new VelocityRepresentationBuilder(
            $this->semantic_velocity_factory,
            $this->semantic_done_factory,
            $this->semantic_timeframe_builder,
            $this->milestone_factory
        );

        $this->milestone = $this->createMock(Planning_Milestone::class);
        $this->user      = $this->createMock(PFUser::class);
    }

    public function testItReturnsACollectionOfVelocityRepresentation(): void
    {
        $tracker = $this->createMock(Tracker::class);

        $velocity_field = $this->createMock(Tracker_FormElement_Field_Float::class);

        $last_changeset_value = $this->createMock(\Tracker_Artifact_ChangesetValue_Numeric::class);
        $last_changeset_value->method('getNumeric')->willReturn(10);

        $last_changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $last_changeset->method('getValue')->with($velocity_field)->willReturn($last_changeset_value);

        $linked_artifact = $this->createMock(Artifact::class);
        $linked_artifact->method('getTracker')->willReturn($tracker);
        $linked_artifact->method('getLastChangeset')->willReturn($last_changeset);
        $linked_artifact->method('getId')->willReturn(102);
        $linked_artifact->method('getTitle')->willReturn('Sprint 01');

        $semantic_velocity  = $this->createMock(SemanticVelocity::class);
        $semantic_done      = $this->createMock(SemanticDone::class);
        $timeframe_semantic = $this->createMock(SemanticTimeframe::class);

        $this->semantic_velocity_factory->method('getInstanceByTracker')
            ->with($tracker)
            ->willReturn($semantic_velocity);

        $this->semantic_done_factory->method('getInstanceByTracker')
            ->with($tracker)
            ->willReturn($semantic_done);

        $this->semantic_timeframe_builder
            ->method("getSemantic")
            ->willReturn($timeframe_semantic);

        $semantic_velocity->method('getVelocityField')->willReturn($velocity_field);
        $semantic_done->method('isDone')->with($last_changeset)->willReturn(true);
        $timeframe_semantic
            ->method("isDefined")
            ->willReturn(true);

        $sub_milestone = $this->createMock(Planning_Milestone::class);
        $sub_milestone->method('getArtifact')
            ->willReturn($linked_artifact);

        $this->milestone_factory->expects(self::once())->method('updateMilestoneContextualInfo')->with($this->user, $sub_milestone);
        $this->milestone_factory
            ->method('getSubMilestones')
            ->with($this->user, $this->milestone)
            ->willReturn([$sub_milestone]);

        $sub_milestone->method('getStartDate')->willReturn(1);
        $sub_milestone->method('getDuration')->willReturn(1);

        $collection = $this->builder->buildCollectionOfRepresentations($this->milestone, $this->user);

        self::assertCount(1, $collection->getVelocityRepresentations());
        self::assertCount(0, $collection->getInvalidArtifacts());
    }

    public function testItDoesNotAddInCollectionIfNoSemanticDoneDefined(): void
    {
        $tracker = $this->createMock(Tracker::class);

        $velocity_field = $this->createMock(Tracker_FormElement_Field_Float::class);

        $last_changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $last_changeset->expects(self::never())->method('getValue')->with($velocity_field);

        $linked_artifact = $this->createMock(Artifact::class);
        $linked_artifact->method('getTracker')->willReturn($tracker);
        $linked_artifact->method('getLastChangeset')->willReturn($last_changeset);
        $linked_artifact->method('getId')->willReturn(102);
        $linked_artifact->method('getTitle')->willReturn('Sprint 01');

        $semantic_velocity  = $this->createMock(SemanticVelocity::class);
        $semantic_done      = $this->createMock(SemanticDone::class);
        $timeframe_semantic = $this->createMock(SemanticTimeframe::class);

        $this->semantic_velocity_factory->method('getInstanceByTracker')
            ->with($tracker)
            ->willReturn($semantic_velocity);

        $this->semantic_done_factory->method('getInstanceByTracker')
            ->with($tracker)
            ->willReturn($semantic_done);

        $this->semantic_timeframe_builder
            ->method("getSemantic")
            ->willReturn($timeframe_semantic);

        $semantic_velocity->method('getVelocityField')->willReturn($velocity_field);
        $semantic_done->method('isDone')->with($last_changeset)->willReturn(false);
        $timeframe_semantic
            ->method("isDefined")
            ->willReturn(true);

        $sub_milestone = $this->createMock(Planning_Milestone::class);
        $sub_milestone->method('getArtifact')
            ->willReturn($linked_artifact);

        $this->milestone_factory->expects(self::never())->method('updateMilestoneContextualInfo')->with($this->user, $sub_milestone);
        $this->milestone_factory
            ->method('getSubMilestones')
            ->with($this->user, $this->milestone)
            ->willReturn([$sub_milestone]);

        $collection = $this->builder->buildCollectionOfRepresentations($this->milestone, $this->user);

        self::assertCount(0, $collection->getVelocityRepresentations());
        self::assertCount(0, $collection->getInvalidArtifacts());
    }

    public function testItDoesNotAddInCollectionIfNoSemanticVelocityDefined(): void
    {
        $tracker = $this->createMock(Tracker::class);

        $linked_artifact = $this->createMock(Artifact::class);
        $linked_artifact->method('getTracker')->willReturn($tracker);
        $linked_artifact->method('getId')->willReturn(102);
        $linked_artifact->method('getTitle')->willReturn('Sprint 01');

        $semantic_velocity  = $this->createMock(SemanticVelocity::class);
        $semantic_done      = $this->createMock(SemanticDone::class);
        $timeframe_semantic = $this->createMock(SemanticTimeframe::class);

        $this->semantic_velocity_factory->method('getInstanceByTracker')
            ->with($tracker)
            ->willReturn($semantic_velocity);

        $this->semantic_done_factory->method('getInstanceByTracker')
            ->with($tracker)
            ->willReturn($semantic_done);

        $this->semantic_timeframe_builder
            ->method("getSemantic")
            ->willReturn($timeframe_semantic);

        $semantic_velocity->method('getVelocityField')->willReturn(null);
        $semantic_done->expects(self::never())->method('isDone');
        $timeframe_semantic
            ->method("isDefined")
            ->willReturn(true);

        $sub_milestone = $this->createMock(Planning_Milestone::class);
        $sub_milestone->method('getArtifact')
            ->willReturn($linked_artifact);

        $this->milestone_factory->expects(self::never())->method('updateMilestoneContextualInfo')->with($this->user, $sub_milestone);
        $this->milestone_factory
            ->method('getSubMilestones')
            ->with($this->user, $this->milestone)
            ->willReturn([$sub_milestone]);

        $collection = $this->builder->buildCollectionOfRepresentations($this->milestone, $this->user);

        self::assertCount(0, $collection->getVelocityRepresentations());
        self::assertCount(0, $collection->getInvalidArtifacts());
    }

    public function testItAddsInCollectionAsInvalidIfNoStartDateDefined(): void
    {
        $tracker = $this->createMock(Tracker::class);

        $velocity_field = $this->createMock(Tracker_FormElement_Field_Float::class);

        $last_changeset_value = $this->createMock(\Tracker_Artifact_ChangesetValue_Numeric::class);
        $last_changeset_value->method('getNumeric')->willReturn(10);

        $last_changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $last_changeset->method('getValue')->with($velocity_field)->willReturn($last_changeset_value);

        $linked_artifact = $this->createMock(Artifact::class);
        $linked_artifact->method('getTracker')->willReturn($tracker);
        $linked_artifact->method('getLastChangeset')->willReturn($last_changeset);
        $linked_artifact->method('getId')->willReturn(102);
        $linked_artifact->method('getTitle')->willReturn('Sprint 01');
        $linked_artifact->method('getXref')->willReturn('art #102 Sprint 01');
        $linked_artifact->method('getUri')->willReturn('artifacts/102');

        $semantic_velocity  = $this->createMock(SemanticVelocity::class);
        $semantic_done      = $this->createMock(SemanticDone::class);
        $timeframe_semantic = $this->createMock(SemanticTimeframe::class);

        $this->semantic_velocity_factory->method('getInstanceByTracker')
            ->with($tracker)
            ->willReturn($semantic_velocity);

        $this->semantic_done_factory->method('getInstanceByTracker')
            ->with($tracker)
            ->willReturn($semantic_done);

        $this->semantic_timeframe_builder
            ->method("getSemantic")
            ->willReturn($timeframe_semantic);

        $semantic_velocity->method('getVelocityField')->willReturn($velocity_field);
        $semantic_done->method('isDone')->with($last_changeset)->willReturn(true);
        $timeframe_semantic
            ->method("isDefined")
            ->willReturn(true);

        $sub_milestone = $this->createMock(Planning_Milestone::class);
        $sub_milestone->method('getArtifact')
            ->willReturn($linked_artifact);

        $this->milestone_factory->expects(self::once())->method('updateMilestoneContextualInfo')->with($this->user, $sub_milestone);
        $this->milestone_factory
            ->method('getSubMilestones')
            ->with($this->user, $this->milestone)
            ->willReturn([$sub_milestone]);

        $sub_milestone->method('getStartDate')->willReturn(null);
        $sub_milestone->method('getDuration')->willReturn(1);

        $collection = $this->builder->buildCollectionOfRepresentations($this->milestone, $this->user);

        self::assertCount(0, $collection->getVelocityRepresentations());
        self::assertCount(1, $collection->getInvalidArtifacts());
    }

    public function testItAddsTrackerNameToInvalidTrackersNamesWhenNoTimeframeSemanticIsDefined(): void
    {
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getName')->willReturn('Sprints');

        $velocity_field = $this->createMock(Tracker_FormElement_Field_Float::class);

        $last_changeset_value = $this->createMock(\Tracker_Artifact_ChangesetValue_Numeric::class);
        $last_changeset_value->method('getNumeric')->willReturn(10);

        $last_changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $last_changeset->method('getValue')->with($velocity_field)->willReturn($last_changeset_value);

        $linked_artifact = $this->createMock(Artifact::class);
        $linked_artifact->method('getTracker')->willReturn($tracker);
        $linked_artifact->method('getLastChangeset')->willReturn($last_changeset);
        $linked_artifact->method('getId')->willReturn(102);
        $linked_artifact->method('getTitle')->willReturn('Sprint 01');

        $semantic_velocity  = $this->createMock(SemanticVelocity::class);
        $semantic_done      = $this->createMock(SemanticDone::class);
        $semantic_timeframe = $this->createMock(SemanticTimeframe::class);

        $this->semantic_velocity_factory->method('getInstanceByTracker')
            ->with($tracker)
            ->willReturn($semantic_velocity);

        $this->semantic_done_factory->method('getInstanceByTracker')
            ->with($tracker)
            ->willReturn($semantic_done);

        $this->semantic_timeframe_builder->method('getSemantic')
            ->with($tracker)
            ->willReturn($semantic_timeframe);

        $semantic_velocity->method('getVelocityField')->willReturn($velocity_field);
        $semantic_done->method('isDone')->with($last_changeset)->willReturn(true);
        $semantic_timeframe->method('isDefined')->willReturn(false);

        $sub_milestone = $this->createMock(Planning_Milestone::class);
        $sub_milestone->method('getArtifact')
            ->willReturn($linked_artifact);

        $this->milestone_factory->expects(self::never())->method('updateMilestoneContextualInfo')->with($this->user, $sub_milestone);
        $this->milestone_factory
            ->method('getSubMilestones')
            ->with($this->user, $this->milestone)
            ->willReturn([$sub_milestone]);

        $sub_milestone->method('getStartDate')->willReturn(1);
        $sub_milestone->method('getDuration')->willReturn(1);

        $collection = $this->builder->buildCollectionOfRepresentations($this->milestone, $this->user);

        self::assertCount(0, $collection->getVelocityRepresentations());
        self::assertCount(0, $collection->getInvalidArtifacts());
        self::assertCount(1, $collection->getInvalidTrackersNames());
        self::assertEquals('Sprints', $collection->getInvalidTrackersNames()[0]);
    }

    public function testItDoesNotAddLinkedArtifactsWhichAreNotASubmilestone(): void
    {
        $tracker = $this->createMock(Tracker::class);

        $velocity_field = $this->createMock(Tracker_FormElement_Field_Float::class);

        $last_changeset_value = $this->createMock(\Tracker_Artifact_ChangesetValue_Numeric::class);
        $last_changeset_value->method('getNumeric')->willReturn(10);

        $last_changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $last_changeset->method('getValue')->with($velocity_field)->willReturn($last_changeset_value);

        $linked_artifact = $this->createMock(Artifact::class);
        $linked_artifact->method('getTracker')->willReturn($tracker);
        $linked_artifact->method('getLastChangeset')->willReturn($last_changeset);
        $linked_artifact->method('getId')->willReturn(102);
        $linked_artifact->method('getTitle')->willReturn('Sprint 01');

        $semantic_velocity  = $this->createMock(SemanticVelocity::class);
        $semantic_done      = $this->createMock(SemanticDone::class);
        $timeframe_semantic = $this->createMock(SemanticTimeframe::class);

        $this->semantic_velocity_factory->method('getInstanceByTracker')
            ->with($tracker)
            ->willReturn($semantic_velocity);

        $this->semantic_done_factory->method('getInstanceByTracker')
            ->with($tracker)
            ->willReturn($semantic_done);

        $this->semantic_timeframe_builder
            ->method("getSemantic")
            ->willReturn($timeframe_semantic);

        $semantic_velocity->method('getVelocityField')->willReturn($velocity_field);
        $semantic_done->method('isDone')->with($last_changeset)->willReturn(true);
        $timeframe_semantic
            ->method("isDefined")
            ->willReturn(true);

        $sub_milestone = $this->createMock(Planning_Milestone::class);
        $sub_milestone->method('getArtifact')
            ->willReturn($linked_artifact);

        $this->milestone_factory->expects(self::once())->method('updateMilestoneContextualInfo')->with($this->user, $sub_milestone);
        $this->milestone_factory
            ->method('getSubMilestones')
            ->with($this->user, $this->milestone)
            ->willReturn([$sub_milestone]);

        $sub_milestone->method('getStartDate')->willReturn(1);
        $sub_milestone->method('getDuration')->willReturn(1);

        $collection = $this->builder->buildCollectionOfRepresentations($this->milestone, $this->user);

        $this->milestone->expects(self::never())->method('getLinkedArtifacts');
        self::assertCount(1, $collection->getVelocityRepresentations());
        self::assertCount(0, $collection->getInvalidArtifacts());
    }
}
