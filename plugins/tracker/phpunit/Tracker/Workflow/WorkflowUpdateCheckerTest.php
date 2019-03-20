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

namespace Tuleap\Tracker\Workflow;

require_once __DIR__ . '/../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Workflow\PostAction\ReadOnly\ReadOnlyFieldDetector;

final class WorkflowUpdateCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var WorkflowUpdateChecker */
    private $workflow_update_checker;
    /** @var Mockery\MockInterface */
    private $read_only_field_detector;

    protected function setUp(): void
    {
        $this->read_only_field_detector = Mockery::mock(ReadOnlyFieldDetector::class);
        $this->workflow_update_checker  = new WorkflowUpdateChecker($this->read_only_field_detector);
    }

    public function testCanFieldBeUpdatedReturnsTrueWhenInitialSubmission()
    {
        $artifact             = Mockery::mock(\Tracker_Artifact::class);
        $field                = Mockery::mock(\Tracker_FormElement_Field::class);
        $last_changeset_value = null;
        $submitted_value      = null;

        $is_submission = true;

        $this->assertTrue(
            $this->workflow_update_checker->canFieldBeUpdated(
                $artifact,
                $field,
                $last_changeset_value,
                $submitted_value,
                $is_submission
            )
        );
    }

    public function testCanFieldBeUpdatedReturnsTrueWhenBothLastChangesetValueAndSubmittedValueAreNull()
    {
        $artifact             = Mockery::mock(\Tracker_Artifact::class);
        $field                = Mockery::mock(\Tracker_FormElement_Field::class);
        $last_changeset_value = null;
        $submitted_value      = null;
        $is_submission        = false;

        $this->assertTrue(
            $this->workflow_update_checker->canFieldBeUpdated(
                $artifact,
                $field,
                $last_changeset_value,
                $submitted_value,
                $is_submission
            )
        );
    }

    public function testCanFieldBeUpdatedReturnsTrueWhenFieldHasNoChanges()
    {
        $artifact             = Mockery::mock(\Tracker_Artifact::class);
        $field                = Mockery::mock(\Tracker_FormElement_Field::class);
        $last_changeset_value = Mockery::mock(\Tracker_Artifact_ChangesetValue::class);
        $submitted_value      = 'Arguslike';
        $is_submission        = false;

        $field->shouldReceive('hasChanges')
            ->with($artifact, $last_changeset_value, $submitted_value)
            ->andReturnFalse();

        $this->assertTrue(
            $this->workflow_update_checker->canFieldBeUpdated(
                $artifact,
                $field,
                $last_changeset_value,
                $submitted_value,
                $is_submission
            )
        );
    }

    public function testCanFieldBeUpdatedReturnsTrueWhenGivenFieldIsReadOnly()
    {
        $artifact             = Mockery::mock(\Tracker_Artifact::class);
        $field                = Mockery::mock(\Tracker_FormElement_Field::class);
        $last_changeset_value = Mockery::mock(\Tracker_Artifact_ChangesetValue::class);
        $submitted_value      = 'Arguslike';
        $is_submission        = false;

        $field->shouldReceive('hasChanges')->andReturnTrue();
        $this->read_only_field_detector
            ->shouldReceive('isFieldReadOnly')
            ->with($artifact, $field)
            ->andReturnTrue();

        $this->assertFalse(
            $this->workflow_update_checker->canFieldBeUpdated(
                $artifact,
                $field,
                $last_changeset_value,
                $submitted_value,
                $is_submission
            )
        );
    }

    public function testCanFieldBeUpdatedReturnsFalseWhenFieldIsNotReadOnly()
    {
        $artifact             = Mockery::mock(\Tracker_Artifact::class);
        $field                = Mockery::mock(\Tracker_FormElement_Field::class);
        $last_changeset_value = Mockery::mock(\Tracker_Artifact_ChangesetValue::class);
        $submitted_value      = 'Arguslike';
        $is_submission        = false;

        $field->shouldReceive('hasChanges')->andReturnTrue();
        $this->read_only_field_detector
            ->shouldReceive('isFieldReadOnly')
            ->andReturnFalse();

        $this->assertTrue(
            $this->workflow_update_checker->canFieldBeUpdated(
                $artifact,
                $field,
                $last_changeset_value,
                $submitted_value,
                $is_submission
            )
        );
    }

    public function testCanFieldBeUpdatedChecksFieldWhenLastChangesetWasNull()
    {
        $artifact             = Mockery::mock(\Tracker_Artifact::class);
        $field                = Mockery::mock(\Tracker_FormElement_Field::class);
        $last_changeset_value = null;
        $submitted_value      = 'Arguslike';
        $is_submission        = false;

        $this->read_only_field_detector
            ->shouldReceive('isFieldReadOnly')
            ->andReturnFalse();

        $this->assertTrue(
            $this->workflow_update_checker->canFieldBeUpdated(
                $artifact,
                $field,
                $last_changeset_value,
                $submitted_value,
                $is_submission
            )
        );
    }

    public function testCanFieldBeUpdatedChecksFieldWhenSubmittedValueIsNull()
    {
        $artifact             = Mockery::mock(\Tracker_Artifact::class);
        $field                = Mockery::mock(\Tracker_FormElement_Field::class);
        $last_changeset_value = Mockery::mock(\Tracker_Artifact_ChangesetValue::class);
        $submitted_value      = null;
        $is_submission        = false;

        $this->read_only_field_detector
            ->shouldReceive('isFieldReadOnly')
            ->andReturnFalse();

        $this->assertTrue(
            $this->workflow_update_checker->canFieldBeUpdated(
                $artifact,
                $field,
                $last_changeset_value,
                $submitted_value,
                $is_submission
            )
        );
    }
}
