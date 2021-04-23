<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Progress;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Artifact\Artifact;

class MethodBasedOnEffortTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var MethodBasedOnEffort
     */
    private $method;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_FormElement_Field_Numeric
     */
    private $total_effort_field;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_FormElement_Field_Numeric
     */
    private $remaining_effort_field;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\PFUser
     */
    private $user;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Artifact
     */
    private $artifact;

    protected function setUp(): void
    {
        $this->total_effort_field     = \Mockery::mock(\Tracker_FormElement_Field_Numeric::class, ['getId' => 1001]);
        $this->remaining_effort_field = \Mockery::mock(\Tracker_FormElement_Field_Numeric::class, ['getId' => 1001]);
        $this->method                 = new MethodBasedOnEffort(
            $this->total_effort_field,
            $this->remaining_effort_field
        );

        $this->user     = \Mockery::mock(\PFUser::class);
        $this->artifact = \Mockery::mock(Artifact::class);
    }

    /**
     * @testWith [8, 5.25, 0.34375, ""]
     *           [8, 8, 0, ""]
     *           [8, 0, 1, ""]
     *           [0, 0, null, "There is no total effort."]
     *           [8, -2, null, "Remaining effort cannot be negative."]
     *           [-2, 3, null, "Total effort cannot be negative."]
     *           [8, 10, null, "Remaining effort cannot be greater than total effort."]
     */
    public function testItComputesTheProgressWithFloatAndIntFields(
        int $total_effort,
        float $remaining_effort,
        ?float $expected_progress_value,
        string $expected_error_message
    ): void {
        $this->total_effort_field->shouldReceive('userCanRead')->with($this->user)->once()->andReturn(true);
        $this->remaining_effort_field->shouldReceive('userCanRead')->with($this->user)->once()->andReturn(true);

        $total_effort_last_changeset     = \Mockery::mock(\Tracker_Artifact_ChangesetValue_Integer::class);
        $remaining_effort_last_changeset = \Mockery::mock(\Tracker_Artifact_ChangesetValue_Float::class);

        $total_effort_last_changeset->shouldReceive('getNumeric')->andReturn($total_effort);
        $remaining_effort_last_changeset->shouldReceive('getNumeric')->andReturn($remaining_effort);

        $this->total_effort_field
            ->shouldReceive('getLastChangesetValue')
            ->with($this->artifact)
            ->andReturn($total_effort_last_changeset);

        $this->remaining_effort_field
            ->shouldReceive('getLastChangesetValue')
            ->with($this->artifact)
            ->andReturn($remaining_effort_last_changeset);

        $progression_result = $this->method->computeProgression($this->artifact, $this->user);

        $this->assertEquals($expected_progress_value, $progression_result->getValue());
        $this->assertEquals($expected_error_message, $progression_result->getErrorMessage());
    }

    /**
     * @testWith [8, 5.25, 0.34375, ""]
     *           [8, 8, 0, ""]
     *           [8, 0, 1, ""]
     *           [0, 0, null, "There is no total effort."]
     *           [8, -2, null, "Remaining effort cannot be negative."]
     *           [-2, 3, null, "Total effort cannot be negative."]
     *           [8, 10, null, "Remaining effort cannot be greater than total effort."]
     */
    public function testItComputesProgressWithComputedFields(
        ?float $total_effort,
        ?float $remaining_effort,
        ?float $expected_progress_value,
        string $expected_error_message
    ): void {
        $computed_field_total_effort     = \Mockery::mock(\Tracker_FormElement_Field_Computed::class);
        $computed_field_remaining_effort = \Mockery::mock(\Tracker_FormElement_Field_Computed::class);

        $computed_field_total_effort->shouldReceive('userCanRead')->with($this->user)->once()->andReturn(true);
        $computed_field_remaining_effort->shouldReceive('userCanRead')->with($this->user)->once()->andReturn(true);

        $computed_field_total_effort->shouldReceive('getComputedValue')->with($this->user, $this->artifact)->andReturn($total_effort);
        $computed_field_remaining_effort->shouldReceive('getComputedValue')->with($this->user, $this->artifact)->andReturn($remaining_effort);

        $method = new MethodBasedOnEffort($computed_field_total_effort, $computed_field_remaining_effort);

        $progression_result = $method->computeProgression($this->artifact, $this->user);

        $this->assertEquals($expected_progress_value, $progression_result->getValue());
        $this->assertEquals($expected_error_message, $progression_result->getErrorMessage());
    }

    public function testItReturnsNullWhenUserHasNotPermissionToReadTotalEffortField(): void
    {
        $this->total_effort_field->shouldReceive('userCanRead')->with($this->user)->once()->andReturn(false);

        $progression_result = $this->method->computeProgression($this->artifact, $this->user);

        $this->assertEquals(null, $progression_result->getValue());
        $this->assertEquals('', $progression_result->getErrorMessage());
    }

    public function testItReturnsNullWhenUserHasNotPermissionToReadRemainingEffortField(): void
    {
        $this->total_effort_field->shouldReceive('userCanRead')->with($this->user)->once()->andReturn(true);
        $this->remaining_effort_field->shouldReceive('userCanRead')->with($this->user)->once()->andReturn(false);

        $progression_result = $this->method->computeProgression($this->artifact, $this->user);

        $this->assertEquals(null, $progression_result->getValue());
        $this->assertEquals('', $progression_result->getErrorMessage());
    }
}
