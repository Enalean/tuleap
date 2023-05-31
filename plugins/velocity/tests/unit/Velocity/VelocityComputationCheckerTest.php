<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue;
use Tracker_FormElement_Field_List;
use Tracker_Semantic_Status;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDone;
use Tuleap\Tracker\Workflow\BeforeEvent;
use Tuleap\Velocity\Semantic\SemanticVelocity;

require_once dirname(__FILE__) . '/../bootstrap.php';

final class VelocityComputationCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Artifact&\PHPUnit\Framework\MockObject\MockObject $artifact;
    private BeforeEvent&\PHPUnit\Framework\MockObject\MockObject $before_event;
    private \PHPUnit\Framework\MockObject\MockObject&Tracker_FormElement_Field_List $semantic_velocity_field;
    private Tracker_Semantic_Status&\PHPUnit\Framework\MockObject\MockObject $semantic_status;
    private int $semantic_status_field_id;
    private \PHPUnit\Framework\MockObject\MockObject&SemanticVelocity $semantic_velocity;
    private \PHPUnit\Framework\MockObject\MockObject&SemanticDone $semantic_done;
    private VelocityComputationChecker $computation_checker;
    private Tracker_Artifact_ChangesetValue&\PHPUnit\Framework\MockObject\MockObject $last_changeset_value;

    public function setUp(): void
    {
        parent::setUp();

        $this->artifact     = $this->createMock(Artifact::class);
        $this->before_event = $this->createMock(BeforeEvent::class);
        $this->before_event->method('getArtifact')->willReturn($this->artifact);

        $this->semantic_velocity_field  = $this->createMock(Tracker_FormElement_Field_List::class);
        $this->semantic_status          = $this->createMock(Tracker_Semantic_Status::class);
        $this->semantic_status_field_id = 100;
        $this->semantic_velocity_field->method('getId')->willReturn($this->semantic_status_field_id);

        $this->semantic_velocity = $this->createMock(SemanticVelocity::class);

        $this->semantic_done = $this->createMock(SemanticDone::class);

        $this->computation_checker = new VelocityComputationChecker();

        $this->last_changeset_value = $this->createMock(Tracker_Artifact_ChangesetValue::class);
        $last_changeset             = $this->createMock(Tracker_Artifact_Changeset::class);
        $last_changeset->method('getValue')->willReturn($this->last_changeset_value);
        $this->artifact->method('getLastChangeset')->willReturn($last_changeset);
    }

    public function testComputationIsNeverLaunchedWhenStatusSemanticIsNotDefined(): void
    {
        $this->semantic_status->method('getFieldId')->willReturn(0);

        self::assertFalse(
            $this->computation_checker->shouldComputeCapacity(
                $this->semantic_status,
                $this->semantic_done,
                $this->semantic_velocity,
                $this->before_event
            )
        );
    }

    public function testComputationIsNeverLaunchedWhenStatusCanHaveMultipleValues(): void
    {
        $this->semantic_velocity_field->method('isMultiple')->willReturn(true);
        $this->semantic_status->method('getFieldId')->willReturn(0);

        self::assertFalse(
            $this->computation_checker->shouldComputeCapacity(
                $this->semantic_status,
                $this->semantic_done,
                $this->semantic_velocity,
                $this->before_event
            )
        );
    }

    public function testComputationIsNeverLaunchedWhenDoneSemanticIsNotDefined(): void
    {
        $this->semantic_velocity_field->method('isMultiple')->willReturn(false);
        $this->semantic_status->method('getField')->willReturn($this->semantic_velocity_field);
        $this->semantic_status->method('getFieldId')->willReturn($this->semantic_status_field_id);
        $this->semantic_done->method('isSemanticDefined')->willReturn(false);
        $this->semantic_velocity->method('getFieldId')->willReturn($this->semantic_status_field_id);

        self::assertFalse(
            $this->computation_checker->shouldComputeCapacity(
                $this->semantic_status,
                $this->semantic_done,
                $this->semantic_velocity,
                $this->before_event
            )
        );
    }

    public function testComputationIsNeverLaunchedWhenVelocitySemanticIsNotDefined(): void
    {
        $this->semantic_velocity_field->method('isMultiple')->willReturn(false);
        $this->semantic_status->method('getField')->willReturn($this->semantic_velocity_field);
        $this->semantic_status->method('getFieldId')->willReturn($this->semantic_status_field_id);
        $this->semantic_done->method('isSemanticDefined')->willReturn(true);
        $this->semantic_velocity->method('getFieldId')->willReturn(false);

        self::assertFalse(
            $this->computation_checker->shouldComputeCapacity(
                $this->semantic_status,
                $this->semantic_done,
                $this->semantic_velocity,
                $this->before_event
            )
        );
    }

    private function setValidSemantics(): void
    {
        $this->semantic_velocity_field->method('isMultiple')->willReturn(false);
        $this->semantic_status->method('getField')->willReturn($this->semantic_velocity_field);
        $this->semantic_status->method('getFieldId')->willReturn($this->semantic_status_field_id);
        $this->semantic_done->method('isSemanticDefined')->willReturn(true);
        $this->semantic_velocity->method('getFieldId')->willReturn($this->semantic_status_field_id);
    }

    public function testVelocityShouldNotBeComputedIfArtifactDoesNotChangeItsStatus(): void
    {
        $this->setValidSemantics();

        $this->last_changeset_value->method('getValue')->willReturn([1000]);
        $new_changeset = [
            $this->semantic_status_field_id => [1000],
        ];
        $this->semantic_status->method('getOpenValues')->willReturn([1001]);
        $this->semantic_done->method('getDoneValuesIds')->willReturn([1002]);
        $this->before_event->method('getFieldsData')->willReturn($new_changeset);

        self::assertFalse(
            $this->computation_checker->shouldComputeCapacity(
                $this->semantic_status,
                $this->semantic_done,
                $this->semantic_velocity,
                $this->before_event
            )
        );
    }

    public function testVelocityShouldNotBeComputedIfNewArtifactStatusIsOpen(): void
    {
        $this->setValidSemantics();

        $this->last_changeset_value->method('getValue')->willReturn([1000]);
        $new_changeset = [
            $this->semantic_status_field_id => [1001],
        ];
        $this->semantic_status->method('getOpenValues')->willReturn([1001]);
        $this->before_event->method('getFieldsData')->willReturn($new_changeset);
        $this->semantic_status->method('getOpenValues')->willReturn([1000, 1001]);
        $this->semantic_done->method('getDoneValuesIds')->willReturn([2000]);

        self::assertFalse(
            $this->computation_checker->shouldComputeCapacity(
                $this->semantic_status,
                $this->semantic_done,
                $this->semantic_velocity,
                $this->before_event
            )
        );
    }

    public function testVelocityShouldNotBeComputedOldStatusIsClosedAndNewStatusIsClosed(): void
    {
        $this->setValidSemantics();

        $this->last_changeset_value->method('getValue')->willReturn([1001]);
        $new_changeset = [
            $this->semantic_status_field_id => [1002],
        ];
        $this->before_event->method('getFieldsData')->willReturn($new_changeset);
        $this->semantic_status->method('getOpenValues')->willReturn([1000]);
        $this->semantic_done->method('getDoneValuesIds')->willReturn([1001, 1002]);

        self::assertFalse(
            $this->computation_checker->shouldComputeCapacity(
                $this->semantic_status,
                $this->semantic_done,
                $this->semantic_velocity,
                $this->before_event
            )
        );
    }

    public function testVelocityShouldBeComputedIfArtifactMoveFromOpenToClose(): void
    {
        $this->setValidSemantics();

        $this->last_changeset_value->method('getValue')->willReturn([1001]);
        $new_changeset = [
            $this->semantic_status_field_id => [1002],
        ];
        $this->before_event->method('getFieldsData')->willReturn($new_changeset);
        $this->semantic_status->method('getOpenValues')->willReturn([1001]);
        $this->semantic_done->method('getDoneValuesIds')->willReturn([1002]);

        self::assertTrue(
            $this->computation_checker->shouldComputeCapacity(
                $this->semantic_status,
                $this->semantic_done,
                $this->semantic_velocity,
                $this->before_event
            )
        );
    }

    public function testVelocityShouldBeComputedIfOldValuesHasAtLeastOneOpenValue(): void
    {
        $this->setValidSemantics();

        $this->last_changeset_value->method('getValue')->willReturn([1000, 1001]);
        $new_changeset = [
            $this->semantic_status_field_id => [1002],
        ];
        $this->before_event->method('getFieldsData')->willReturn($new_changeset);
        $this->semantic_status->method('getOpenValues')->willReturn([1001]);
        $this->semantic_done->method('getDoneValuesIds')->willReturn([1000, 1002]);

        self::assertTrue(
            $this->computation_checker->shouldComputeCapacity(
                $this->semantic_status,
                $this->semantic_done,
                $this->semantic_velocity,
                $this->before_event
            )
        );
    }

    public function testVelocityShouldBeComputedIfNewValuesHasAtLeastOneClosedValue(): void
    {
        $this->setValidSemantics();

        $this->last_changeset_value->method('getValue')->willReturn([1001]);
        $new_changeset = [
            $this->semantic_status_field_id => [1000, 1002],
        ];
        $this->before_event->method('getFieldsData')->willReturn($new_changeset);
        $this->semantic_status->method('getOpenValues')->willReturn([1000, 1001]);
        $this->semantic_done->method('getDoneValuesIds')->willReturn([1002]);

        self::assertTrue(
            $this->computation_checker->shouldComputeCapacity(
                $this->semantic_status,
                $this->semantic_done,
                $this->semantic_velocity,
                $this->before_event
            )
        );
    }
}
