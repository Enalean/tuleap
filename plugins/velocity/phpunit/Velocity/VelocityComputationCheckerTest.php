<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue;
use Tracker_FormElement_Field_List;
use Tracker_Semantic_Status;
use Tuleap\AgileDashboard\Semantic\SemanticDone;
use Tuleap\Tracker\Workflow\BeforeEvent;
use Tuleap\Velocity\Semantic\SemanticVelocity;

require_once dirname(__FILE__) . '/../bootstrap.php';

class VelocityComputationCheckerTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Tracker_FormElement_Field_List
     */
    private $semantic_velocity_field;
    /**
     * @var SemanticVelocity
     */
    private $semantic_velocity;
    /**
     * @var Int
     */
    private $semantic_status_field_id;
    /**
     * @var Tracker_Artifact_ChangesetValue
     */
    private $last_changeset_value;
    /**
     * @var Tracker_Semantic_Status
     */
    private $semantic_status;
    /**
     * @var SemanticDone
     */
    private $semantic_done;
    /**
     * @var VelocityCalculator
     */
    private $computation_checker;
    /**
     * @var BeforeEvent
     */
    private $before_event;
    /**
     * @var Tracker_Artifact
     */
    private $artifact;

    public function setUp() : void
    {
        parent::setUp();

        $this->artifact     = Mockery::mock(Tracker_Artifact::class);
        $this->before_event = Mockery::mock(BeforeEvent::class);
        $this->before_event->shouldReceive('getArtifact')->andReturn($this->artifact);

        $this->semantic_velocity_field  = Mockery::mock(Tracker_FormElement_Field_List::class);
        $this->semantic_status          = Mockery::mock(Tracker_Semantic_Status::class);
        $this->semantic_status_field_id = 100;
        $this->semantic_velocity_field->shouldReceive('getId')->andReturn($this->semantic_status_field_id);

        $this->semantic_velocity = Mockery::mock(SemanticVelocity::class);

        $this->semantic_done   = Mockery::mock(SemanticDone::class);

        $this->computation_checker = new VelocityComputationChecker();

        $this->last_changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue::class);
        $last_changeset             = Mockery::mock(Tracker_Artifact_Changeset::class);
        $last_changeset->shouldReceive('getValue')->andReturn($this->last_changeset_value);
        $this->artifact->shouldReceive('getLastChangeset')->andReturn($last_changeset);
    }

    public function testComputationIsNeverLaunchedWhenStatusSemanticIsNotDefined()
    {
        $this->semantic_status->shouldReceive('getFieldId')->andReturn(false);

        $this->assertFalse(
            $this->computation_checker->shouldComputeCapacity(
                $this->semantic_status,
                $this->semantic_done,
                $this->semantic_velocity,
                $this->before_event
            )
        );
    }

    public function testComputationIsNeverLaunchedWhenStatusCanHaveMultipleValues()
    {
        $this->semantic_velocity_field->shouldReceive('isMultiple')->andReturn(true);
        $this->semantic_status->shouldReceive('getFieldId')->andReturn(false);

        $this->assertFalse(
            $this->computation_checker->shouldComputeCapacity(
                $this->semantic_status,
                $this->semantic_done,
                $this->semantic_velocity,
                $this->before_event
            )
        );
    }

    public function testComputationIsNeverLaunchedWhenDoneSemanticIsNotDefined()
    {
        $this->semantic_velocity_field->shouldReceive('isMultiple')->andReturn(false);
        $this->semantic_status->shouldReceive('getField')->andReturn($this->semantic_velocity_field);
        $this->semantic_status->shouldReceive('getFieldId')->andReturn($this->semantic_status_field_id);
        $this->semantic_done->shouldReceive('isSemanticDefined')->andReturn(false);
        $this->semantic_velocity->shouldReceive('getFieldId')->andReturn($this->semantic_status_field_id);

        $this->assertFalse(
            $this->computation_checker->shouldComputeCapacity(
                $this->semantic_status,
                $this->semantic_done,
                $this->semantic_velocity,
                $this->before_event
            )
        );
    }

    public function testComputationIsNeverLaunchedWhenVelocitySemanticIsNotDefined()
    {
        $this->semantic_velocity_field->shouldReceive('isMultiple')->andReturn(false);
        $this->semantic_status->shouldReceive('getField')->andReturn($this->semantic_velocity_field);
        $this->semantic_status->shouldReceive('getFieldId')->andReturn($this->semantic_status_field_id);
        $this->semantic_done->shouldReceive('isSemanticDefined')->andReturn(true);
        $this->semantic_velocity->shouldReceive('getFieldId')->andReturn(false);

        $this->assertFalse(
            $this->computation_checker->shouldComputeCapacity(
                $this->semantic_status,
                $this->semantic_done,
                $this->semantic_velocity,
                $this->before_event
            )
        );
    }

    private function setValidSemantics()
    {
        $this->semantic_velocity_field->shouldReceive('isMultiple')->andReturn(false);
        $this->semantic_status->shouldReceive('getField')->andReturn($this->semantic_velocity_field);
        $this->semantic_status->shouldReceive('getFieldId')->andReturn($this->semantic_status_field_id);
        $this->semantic_done->shouldReceive('isSemanticDefined')->andReturn(true);
        $this->semantic_velocity->shouldReceive('getFieldId')->andReturn($this->semantic_status_field_id);
    }

    public function testVelocityShouldNotBeComputedIfArtifactDoesNotChangeItsStatus()
    {
        $this->setValidSemantics();

        $this->last_changeset_value->shouldReceive('getValue')->andReturn([1000]);
        $new_changeset[$this->semantic_status_field_id] = [1000];
        $this->semantic_status->shouldReceive('getOpenValues')->andReturn([1001]);
        $this->semantic_done->shouldReceive('getDoneValuesIds')->andReturn([1002]);
        $this->before_event->shouldReceive('getFieldsData')->andReturn($new_changeset);

        $this->assertFalse(
            $this->computation_checker->shouldComputeCapacity(
                $this->semantic_status,
                $this->semantic_done,
                $this->semantic_velocity,
                $this->before_event
            )
        );
    }

    public function testVelocityShouldNotBeComputedIfNewArtifactStatusIsOpen()
    {
        $this->setValidSemantics();

        $this->last_changeset_value->shouldReceive('getValue')->andReturn([1000]);
        $new_changeset[$this->semantic_status_field_id] = [1001];
        $this->semantic_status->shouldReceive('getOpenValues')->andReturn([1001]);
        $this->before_event->shouldReceive('getFieldsData')->andReturn($new_changeset);
        $this->semantic_status->shouldReceive('getOpenValues')->andReturn([1000, 1001]);
        $this->semantic_done->shouldReceive('getDoneValuesIds')->andReturn([2000]);

        $this->assertFalse(
            $this->computation_checker->shouldComputeCapacity(
                $this->semantic_status,
                $this->semantic_done,
                $this->semantic_velocity,
                $this->before_event
            )
        );
    }

    public function testVelocityShouldNotBeComputedOldStatusIsClosedAndNewStatusIsClosed()
    {
        $this->setValidSemantics();

        $this->last_changeset_value->shouldReceive('getValue')->andReturn([1001]);
        $new_changeset[$this->semantic_status_field_id] = [1002];
        $this->before_event->shouldReceive('getFieldsData')->andReturn($new_changeset);
        $this->semantic_status->shouldReceive('getOpenValues')->andReturn([1000]);
        $this->semantic_done->shouldReceive('getDoneValuesIds')->andReturn([1001, 1002]);

        $this->assertFalse(
            $this->computation_checker->shouldComputeCapacity(
                $this->semantic_status,
                $this->semantic_done,
                $this->semantic_velocity,
                $this->before_event
            )
        );
    }

    public function testVelocityShouldBeComputedIfArtifactMoveFromOpenToClose()
    {
        $this->setValidSemantics();

        $this->last_changeset_value->shouldReceive('getValue')->andReturn([1001]);
        $new_changeset[$this->semantic_status_field_id] = [1002];
        $this->before_event->shouldReceive('getFieldsData')->andReturn($new_changeset);
        $this->semantic_status->shouldReceive('getOpenValues')->andReturn([1001]);
        $this->semantic_done->shouldReceive('getDoneValuesIds')->andReturn([1002]);

        $this->assertTrue(
            $this->computation_checker->shouldComputeCapacity(
                $this->semantic_status,
                $this->semantic_done,
                $this->semantic_velocity,
                $this->before_event
            )
        );
    }

    public function testVelocityShouldBeComputedIfOldValuesHasAtLeastOneOpenValue()
    {
        $this->setValidSemantics();

        $this->last_changeset_value->shouldReceive('getValue')->andReturn([1000, 1001]);
        $new_changeset[$this->semantic_status_field_id] = [1002];
        $this->before_event->shouldReceive('getFieldsData')->andReturn($new_changeset);
        $this->semantic_status->shouldReceive('getOpenValues')->andReturn([1001]);
        $this->semantic_done->shouldReceive('getDoneValuesIds')->andReturn([1000, 1002]);

        $this->assertTrue(
            $this->computation_checker->shouldComputeCapacity(
                $this->semantic_status,
                $this->semantic_done,
                $this->semantic_velocity,
                $this->before_event
            )
        );
    }

    public function testVelocityShouldBeComputedIfNewValuesHasAtLeastOneClosedValue()
    {
        $this->setValidSemantics();

        $this->last_changeset_value->shouldReceive('getValue')->andReturn([1001]);
        $new_changeset[$this->semantic_status_field_id] = [1000, 1002];
        $this->before_event->shouldReceive('getFieldsData')->andReturn($new_changeset);
        $this->semantic_status->shouldReceive('getOpenValues')->andReturn([1000, 1001]);
        $this->semantic_done->shouldReceive('getDoneValuesIds')->andReturn([1002]);

        $this->assertTrue(
            $this->computation_checker->shouldComputeCapacity(
                $this->semantic_status,
                $this->semantic_done,
                $this->semantic_velocity,
                $this->before_event
            )
        );
    }
}
