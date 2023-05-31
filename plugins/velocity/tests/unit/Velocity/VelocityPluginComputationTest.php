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

use PFUser;
use Tracker;
use Tracker_Artifact_Changeset;
use Tracker_FormElement_Field;
use Tracker_Semantic_Status;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDone;
use Tuleap\Tracker\Workflow\BeforeEvent;
use Tuleap\Velocity\Semantic\SemanticVelocity;

require_once dirname(__FILE__) . '/../bootstrap.php';

final class VelocityPluginComputationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private VelocityCalculator&\PHPUnit\Framework\MockObject\MockObject $velocity_calculator;
    private VelocityComputationChecker&\PHPUnit\Framework\MockObject\MockObject $velocity_computation_checker;
    private VelocityComputation $velocity_computation;
    private \PHPUnit\Framework\MockObject\MockObject&PFUser $user;
    private Artifact&\PHPUnit\Framework\MockObject\MockObject $artifact;
    private Tracker_Semantic_Status&\PHPUnit\Framework\MockObject\MockObject $semantic_status;
    private \PHPUnit\Framework\MockObject\MockObject&SemanticDone $semantic_done;
    private \PHPUnit\Framework\MockObject\MockObject&SemanticVelocity $semantic_velocity;

    public function setUp(): void
    {
        $this->velocity_calculator          = $this->createMock(VelocityCalculator::class);
        $this->velocity_computation_checker = $this->createMock(VelocityComputationChecker::class);

        $this->velocity_computation = new VelocityComputation(
            $this->velocity_calculator,
            $this->velocity_computation_checker
        );

        $this->user = $this->createMock(PFUser::class);

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(101);
        $this->artifact = $this->createMock(Artifact::class);

        $this->semantic_status   = $this->createMock(Tracker_Semantic_Status::class);
        $this->semantic_done     = $this->createMock(SemanticDone::class);
        $this->semantic_velocity = $this->createMock(SemanticVelocity::class);

        $velocity_field = $this->createMock(Tracker_FormElement_Field::class);
        $velocity_field->method('getId')->willReturn(100);
        $this->semantic_velocity->method('getFieldId')->willReturn($velocity_field);
        $velocity_field->method('userCanRead')->willReturn(false);
        $velocity_field->method('getId')->willReturn(1);
    }

    public function testItLaunchComputationVelocity(): void
    {
        $already_computed_velocity = [];

        $artifact_id  = 101;
        $changeset_id = 1001;
        $before_event = $this->createMock(BeforeEvent::class);
        $before_event->method('getArtifact')->willReturn($this->artifact);
        $this->artifact->method('getId')->willReturn($artifact_id);
        $artifact_changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $this->artifact->method('getLastChangeset')->willReturn($artifact_changeset);
        $artifact_changeset->method('getId')->willReturn($changeset_id);

        $velocity_field = $this->createMock(Tracker_FormElement_Field::class);
        $velocity_field->method('userCanRead')->willReturn(false);
        $this->semantic_velocity->method('getVelocityField')->willReturn($velocity_field);
        $this->velocity_computation_checker->method('shouldComputeCapacity')->willReturn(true);
        $this->velocity_calculator->method('calculate')->willReturn(20);
        $before_event->method('getUser')->willReturn($this->user);

        $before_event->method('forceFieldData');

        $this->velocity_computation->compute(
            $before_event,
            $already_computed_velocity,
            $this->semantic_status,
            $this->semantic_done,
            $this->semantic_velocity
        );

        $expected_computed_velocity = [
            $artifact_id => [
                $changeset_id => 20,
            ],
        ];

        self::assertEquals($expected_computed_velocity, $already_computed_velocity);
    }

    public function testItRetrieveAlreadyComputedVelocity(): void
    {
        $artifact_id  = 101;
        $changeset_id = 1001;

        $already_computed_velocity = [
            $artifact_id => [
                $changeset_id => 30,
            ],
        ];

        $before_event = $this->createMock(BeforeEvent::class);
        $before_event->method('getArtifact')->willReturn($this->artifact);
        $this->artifact->method('getId')->willReturn($artifact_id);
        $artifact_changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $this->artifact->method('getLastChangeset')->willReturn($artifact_changeset);
        $artifact_changeset->method('getId')->willReturn($changeset_id);

        $this->velocity_computation_checker->method('shouldComputeCapacity')->willReturn(true);
        $this->velocity_calculator->expects(self::never())->method('calculate');

        $before_event->method('forceFieldData');

        $this->velocity_computation->compute(
            $before_event,
            $already_computed_velocity,
            $this->semantic_status,
            $this->semantic_done,
            $this->semantic_velocity
        );

        $expected_computed_velocity = [
            $artifact_id => [
                $changeset_id => 30,
            ],
        ];

        self::assertEquals($expected_computed_velocity, $already_computed_velocity);
    }
}
