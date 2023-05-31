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

declare(strict_types=1);

namespace Tuleap\Velocity;

use AgileDashBoard_Semantic_InitialEffort;
use AgileDashboard_Semantic_InitialEffortFactory;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_List;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_Integer;
use Tracker_FormElement_Field_Selectbox;
use Tracker_Semantic_Status;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDone;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneFactory;

final class VelocityCalculatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private VelocityCalculator $calculator;
    private Tracker_ArtifactFactory&\PHPUnit\Framework\MockObject\MockObject $artifact_factory;
    private AgileDashboard_Semantic_InitialEffortFactory&\PHPUnit\Framework\MockObject\MockObject $initial_effort_factory;
    private SemanticDoneFactory&\PHPUnit\Framework\MockObject\MockObject $semantic_done_factory;
    private VelocityDao&\PHPUnit\Framework\MockObject\MockObject $velocity_dao;
    private Artifact&\PHPUnit\Framework\MockObject\MockObject $artifact;
    private Tracker&\PHPUnit\Framework\MockObject\MockObject $tracker;
    private \PHPUnit\Framework\MockObject\MockObject&PFUser $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artifact_factory       = $this->createMock(Tracker_ArtifactFactory::class);
        $this->initial_effort_factory = $this->createMock(AgileDashboard_Semantic_InitialEffortFactory::class);
        $this->semantic_done_factory  = $this->createMock(SemanticDoneFactory::class);
        $this->velocity_dao           = $this->createMock(VelocityDao::class);

        $this->calculator = new VelocityCalculator(
            $this->artifact_factory,
            $this->initial_effort_factory,
            $this->semantic_done_factory,
            $this->velocity_dao
        );

        $this->artifact = $this->createMock(Artifact::class);
        $this->tracker  = $this->createMock(Tracker::class);
        $this->user     = $this->createMock(PFUser::class);

        $this->artifact->method('getId')->willReturn(200);
    }

    public function testItCalculatesVelocityBasedOnInitialEffort(): void
    {
        $linked_artifact = $this->mockLinkedArtifact();

        $last_changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $linked_artifact->method('getLastChangeset')->willReturn($last_changeset);

        $initial_effort_field = $this->createMock(Tracker_FormElement_Field_Integer::class);

        $this->mockSemanticInitialEffort($linked_artifact, $initial_effort_field);
        $status_field = $this->mockSemanticDone();

        $last_changeset_value_list = $this->createMock(Tracker_Artifact_ChangesetValue_List::class);
        $last_changeset_value_list->expects(self::once())->method('getValue')
            ->willReturn([0 => '431']);

        $last_changeset->expects(self::once())->method('getValue')
            ->with($status_field)
            ->willReturn($last_changeset_value_list);

        $calculated_effort = $this->calculator->calculate($this->artifact, $this->user);

        $this->assertSame(15, $calculated_effort);
    }

    public function testItCalculatesVelocityBasedOnInitialEffortBoundToAListField(): void
    {
        $linked_artifact = $this->mockLinkedArtifact();

        $last_changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $linked_artifact->method('getLastChangeset')->willReturn($last_changeset);

        $initial_effort_field = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $this->mockSemanticInitialEffort($linked_artifact, $initial_effort_field);

        $status_field = $this->mockSemanticDone();

        $last_changeset_value_list = $this->createMock(Tracker_Artifact_ChangesetValue_List::class);
        $last_changeset_value_list->expects(self::once())->method('getValue')
            ->willReturn([0 => '431']);

        $last_changeset->expects(self::once())->method('getValue')
            ->with($status_field)
            ->willReturn($last_changeset_value_list);

        $calculated_effort = $this->calculator->calculate($this->artifact, $this->user);

        $this->assertSame(15, $calculated_effort);
    }

    public function testItReturnsZeroIfNoLinkedArtifacts(): void
    {
        $this->velocity_dao->expects(self::once())->method('searchPlanningLinkedArtifact')
            ->with(200)
            ->willReturn([]);

        $calculated_effort = $this->calculator->calculate($this->artifact, $this->user);

        $this->assertSame(0, $calculated_effort);
    }

    public function testItReturnsZeroIfLinkedArtifactsHAveNoInitialEffortSemantic(): void
    {
        $this->mockLinkedArtifact();

        $this->initial_effort_factory->method('getByTracker')
            ->with($this->tracker)
            ->willReturn(null);

        $calculated_effort = $this->calculator->calculate($this->artifact, $this->user);

        $this->assertSame(0, $calculated_effort);
    }

    public function testItReturnsZeroIfLinkedArtifactsHAveNoSemanticInitialEffortField(): void
    {
        $this->mockLinkedArtifact();

        $this->mockSemanticInitialEffortWithoutField();

        $calculated_effort = $this->calculator->calculate($this->artifact, $this->user);

        $this->assertSame(0, $calculated_effort);
    }

    public function testItReturnsZeroIfLinkedArtifactsHAveNoDoneSemantic(): void
    {
        $linked_artifact      = $this->mockLinkedArtifact();
        $initial_effort_field = $this->createMock(Tracker_FormElement_Field_Integer::class);

        $this->mockSemanticInitialEffort($linked_artifact, $initial_effort_field);

        $this->semantic_done_factory->method('getInstanceByTracker')
            ->with($this->tracker)
            ->willReturn(null);

        $calculated_effort = $this->calculator->calculate($this->artifact, $this->user);

        $this->assertSame(0, $calculated_effort);
    }

    public function testItReturnsZeroIfLinkedArtifactsHaveNoLastChangeset(): void
    {
        $linked_artifact = $this->mockLinkedArtifact();
        $linked_artifact->method('getLastChangeset')->willReturn(null);

        $initial_effort_field = $this->createMock(Tracker_FormElement_Field_Integer::class);

        $this->mockSemanticInitialEffort($linked_artifact, $initial_effort_field);
        $this->mockSemanticDone();

        $calculated_effort = $this->calculator->calculate($this->artifact, $this->user);

        $this->assertSame(0, $calculated_effort);
    }

    public function testItReturnsZeroIfLinkedArtifactsLastChangesetHaveNoValueForInitialEffortField(): void
    {
        $linked_artifact = $this->mockLinkedArtifact();

        $initial_effort_field = $this->createMock(Tracker_FormElement_Field_Integer::class);

        $this->mockSemanticInitialEffort($linked_artifact, $initial_effort_field);
        $status_field = $this->mockSemanticDone();

        $last_changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $last_changeset->expects(self::once())->method('getValue')
            ->with($status_field)
            ->willReturn(null);

        $linked_artifact->method('getLastChangeset')->willReturn($last_changeset);

        $calculated_effort = $this->calculator->calculate($this->artifact, $this->user);

        $this->assertSame(0, $calculated_effort);
    }

    public function testItReturnsZeroIfLinkedArtifactsValueForInitialEffortFieldIsNotANumeric(): void
    {
        $linked_artifact = $this->mockLinkedArtifact();

        $last_changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $linked_artifact->method('getLastChangeset')->willReturn($last_changeset);

        $initial_effort_field = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $this->mockSemanticInitialEffortWithListValueNotInteger($linked_artifact, $initial_effort_field);

        $status_field = $this->mockSemanticDone();

        $last_changeset_value_list = $this->createMock(Tracker_Artifact_ChangesetValue_List::class);
        $last_changeset_value_list->expects(self::once())->method('getValue')
            ->willReturn([0 => '431']);

        $last_changeset->expects(self::once())->method('getValue')
            ->with($status_field)
            ->willReturn($last_changeset_value_list);

        $calculated_effort = $this->calculator->calculate($this->artifact, $this->user);

        $this->assertSame(0, $calculated_effort);
    }

    private function mockSemanticInitialEffort(
        Artifact $linked_artifact,
        MockObject&Tracker_FormElement_Field $initial_effort_field,
    ): void {
        $initial_effort_field->method('getComputedValue')
            ->with($this->user, $linked_artifact)
            ->willReturn(15);

        $semantic_initial_effort = $this->createMock(AgileDashBoard_Semantic_InitialEffort::class);
        $semantic_initial_effort->method('getField')->willReturn($initial_effort_field);

        $this->initial_effort_factory->method('getByTracker')
            ->with($this->tracker)
            ->willReturn($semantic_initial_effort);
    }

    private function mockSemanticInitialEffortWithListValueNotInteger(
        Artifact $linked_artifact,
        MockObject&Tracker_FormElement_Field $initial_effort_field,
    ): void {
        $initial_effort_field->method('getComputedValue')
            ->with($this->user, $linked_artifact)
            ->willReturn(null);

        $semantic_initial_effort = $this->createMock(AgileDashBoard_Semantic_InitialEffort::class);
        $semantic_initial_effort->method('getField')->willReturn($initial_effort_field);

        $this->initial_effort_factory->method('getByTracker')
            ->with($this->tracker)
            ->willReturn($semantic_initial_effort);
    }

    private function mockSemanticInitialEffortWithoutField(): void
    {
        $semantic_initial_effort = $this->createMock(AgileDashBoard_Semantic_InitialEffort::class);
        $semantic_initial_effort->method('getField')->willReturn(null);

        $this->initial_effort_factory->method('getByTracker')
            ->with($this->tracker)
            ->willReturn($semantic_initial_effort);
    }

    private function mockSemanticDone(): MockObject&Tracker_FormElement_Field_Selectbox
    {
        $status_field    = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $semantic_status = $this->createMock(Tracker_Semantic_Status::class);
        $semantic_status->method('getField')->willReturn($status_field);

        $semantic_done = $this->createMock(SemanticDone::class);
        $semantic_done->method('getSemanticStatus')->willReturn($semantic_status);
        $semantic_done->method('getDoneValuesIds')->willReturn([
            430,
            431,
        ]);

        $this->semantic_done_factory->method('getInstanceByTracker')
            ->with($this->tracker)
            ->willReturn($semantic_done);

        return $status_field;
    }

    private function mockLinkedArtifact(): Artifact&MockObject
    {
        $this->velocity_dao->expects(self::once())->method('searchPlanningLinkedArtifact')
            ->with(200)
            ->willReturn([
                ['id' => 201],
            ]);

        $linked_artifact = $this->createMock(Artifact::class);
        $linked_artifact->method('getTracker')->willReturn($this->tracker);

        $this->artifact_factory->expects(self::once())->method('getArtifactById')
            ->with(201)
            ->willReturn($linked_artifact);

        return $linked_artifact;
    }
}
