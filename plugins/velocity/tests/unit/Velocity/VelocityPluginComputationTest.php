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
use PFUser;
use Tracker;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_FormElement_Field;
use Tracker_Semantic_Status;
use Tuleap\AgileDashboard\Semantic\SemanticDone;
use Tuleap\Tracker\Workflow\BeforeEvent;
use Tuleap\Velocity\Semantic\SemanticVelocity;

require_once dirname(__FILE__) . '/../bootstrap.php';

class VelocityPluginComputationTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var PFUser
     */
    private $user;
    /**
     * @var VelocityCalculator
     */
    private $velocity_calculator;
    /**
     * @var VelocityComputationChecker
     */
    private $velocity_computation_checker;
    /**
     * @var Tracker_Semantic_Status
     */
    private $semantic_status;
    /**
     * @var SemanticDone
     */
    private $semantic_done;
    /**
     * @var SemanticVelocity
     */
    private $semantic_velocity;
    /**
     * @var Tracker_Artifact
     */
    private $artifact;
    /**
     * @var VelocityComputation
     */
    private $velocity_computation;

    public function setUp(): void
    {
        parent::setUp();

        $this->velocity_calculator          = Mockery::mock(VelocityCalculator::class);
        $this->velocity_computation_checker = Mockery::mock(VelocityComputationChecker::class);

        $this->velocity_computation = new VelocityComputation(
            $this->velocity_calculator,
            $this->velocity_computation_checker
        );

        $this->user = Mockery::mock(PFUser::class);

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(101);
        $this->artifact = Mockery::mock(Tracker_Artifact::class);

        $this->semantic_status   = Mockery::mock(Tracker_Semantic_Status::class);
        $this->semantic_done     = Mockery::mock(SemanticDone::class);
        $this->semantic_velocity = Mockery::mock(SemanticVelocity::class);

        $velocity_field = Mockery::mock(Tracker_FormElement_Field::class);
        $velocity_field->shouldReceive('getId')->andReturn(100);
        $this->semantic_velocity->shouldReceive('getFieldId')->andReturn($velocity_field);
        $velocity_field->shouldReceive('userCanRead')->andReturn(false);
        $velocity_field->shouldReceive('getId')->andReturn(1);
    }

    public function testItLaunchComputationVelocity()
    {
        $already_computed_velocity = [];

        $artifact_id = 101;
        $changeset_id = 1001;
        $before_event = Mockery::mock(BeforeEvent::class);
        $before_event->shouldReceive('getArtifact')->andReturn($this->artifact);
        $this->artifact->shouldReceive('getId')->andReturn($artifact_id);
        $artifact_changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->artifact->shouldReceive('getLastChangeset')->andReturn($artifact_changeset);
        $artifact_changeset->shouldReceive('getId')->andReturn($changeset_id);

        $velocity_field = Mockery::mock(Tracker_FormElement_Field::class);
        $velocity_field->shouldReceive('userCanRead')->andReturn(false);
        $this->semantic_velocity->shouldReceive('getVelocityField')->andReturn($velocity_field);
        $this->velocity_computation_checker->shouldReceive('shouldComputeCapacity')->andReturn(true);
        $this->velocity_calculator->shouldReceive('calculate')->andReturn(20);
        $before_event->shouldReceive('getUser')->andReturn($this->user);

        $before_event->shouldReceive('forceFieldData');

        $this->velocity_computation->compute(
            $before_event,
            $already_computed_velocity,
            $this->semantic_status,
            $this->semantic_done,
            $this->semantic_velocity
        );

        $expected_computed_velocity[$artifact_id][$changeset_id] = 20;

        $this->assertEquals($expected_computed_velocity, $already_computed_velocity);
    }

    public function testItRetrieveAlreadyComputedVelocity()
    {
        $artifact_id = 101;
        $changeset_id = 1001;

        $already_computed_velocity[$artifact_id][$changeset_id] = 30;

        $before_event = Mockery::mock(BeforeEvent::class);
        $before_event->shouldReceive('getArtifact')->andReturn($this->artifact);
        $this->artifact->shouldReceive('getId')->andReturn($artifact_id);
        $artifact_changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->artifact->shouldReceive('getLastChangeset')->andReturn($artifact_changeset);
        $artifact_changeset->shouldReceive('getId')->andReturn($changeset_id);

        $this->velocity_computation_checker->shouldReceive('shouldComputeCapacity')->andReturn(true);
        $this->velocity_calculator->shouldNotHaveReceived('calculate');

        $before_event->shouldReceive('forceFieldData');

        $this->velocity_computation->compute(
            $before_event,
            $already_computed_velocity,
            $this->semantic_status,
            $this->semantic_done,
            $this->semantic_velocity
        );

        $expected_computed_velocity[$artifact_id][$changeset_id] = 30;

        $this->assertEquals($expected_computed_velocity, $already_computed_velocity);
    }
}
