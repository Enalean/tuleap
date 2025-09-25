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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WorkflowUpdateCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private WorkflowUpdateChecker $workflow_update_checker;
    private FrozenFieldDetector&MockObject $frozen_field_detector;

    #[\Override]
    protected function setUp(): void
    {
        $this->frozen_field_detector   = $this->createMock(FrozenFieldDetector::class);
        $this->workflow_update_checker = new WorkflowUpdateChecker($this->frozen_field_detector);
    }

    public function testCanFieldBeUpdatedReturnsTrueWhenInitialSubmission()
    {
        $artifact             = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $field                = $this->createMock(\Tuleap\Tracker\FormElement\Field\TrackerField::class);
        $last_changeset_value = null;
        $submitted_value      = null;
        $user                 = $this->createMock(\PFUser::class);

        $is_submission = true;

        $this->assertTrue(
            $this->workflow_update_checker->canFieldBeUpdated(
                $artifact,
                $field,
                $last_changeset_value,
                $submitted_value,
                $is_submission,
                $user
            )
        );
    }

    public function testCanFieldBeUpdatedReturnsTrueWhenUserIsWorkflowUser()
    {
        $artifact             = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $field                = $this->createMock(\Tuleap\Tracker\FormElement\Field\TrackerField::class);
        $last_changeset_value = null;
        $submitted_value      = null;
        $is_submission        = false;
        $user                 = $this->createMock(\Tracker_Workflow_WorkflowUser::class);

        $this->assertTrue(
            $this->workflow_update_checker->canFieldBeUpdated(
                $artifact,
                $field,
                $last_changeset_value,
                $submitted_value,
                $is_submission,
                $user
            )
        );
    }

    public function testCanFieldBeUpdatedReturnsTrueWhenBothLastChangesetValueAndSubmittedValueAreNull()
    {
        $artifact             = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $field                = $this->createMock(\Tuleap\Tracker\FormElement\Field\TrackerField::class);
        $last_changeset_value = null;
        $submitted_value      = null;
        $is_submission        = false;
        $user                 = $this->createMock(\PFUser::class);

        $this->assertTrue(
            $this->workflow_update_checker->canFieldBeUpdated(
                $artifact,
                $field,
                $last_changeset_value,
                $submitted_value,
                $is_submission,
                $user
            )
        );
    }

    public function testCanFieldBeUpdatedReturnsTrueWhenFieldHasNoChanges()
    {
        $artifact             = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $field                = $this->createMock(\Tuleap\Tracker\FormElement\Field\TrackerField::class);
        $last_changeset_value = $this->createMock(\Tracker_Artifact_ChangesetValue::class);
        $submitted_value      = 'Arguslike';
        $is_submission        = false;
        $user                 = $this->createMock(\PFUser::class);

        $field->method('hasChanges')
            ->with($artifact, $last_changeset_value, $submitted_value)
            ->willReturn(false);

        $this->assertTrue(
            $this->workflow_update_checker->canFieldBeUpdated(
                $artifact,
                $field,
                $last_changeset_value,
                $submitted_value,
                $is_submission,
                $user
            )
        );
    }

    public function testCanFieldBeUpdatedReturnsTrueWhenGivenFieldIsReadOnly()
    {
        $artifact             = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $field                = $this->createMock(\Tuleap\Tracker\FormElement\Field\TrackerField::class);
        $last_changeset_value = $this->createMock(\Tracker_Artifact_ChangesetValue::class);
        $submitted_value      = 'Arguslike';
        $is_submission        = false;
        $user                 = $this->createMock(\PFUser::class);

        $field->method('hasChanges')->willReturn(true);
        $this->frozen_field_detector
            ->method('isFieldFrozen')
            ->with($artifact, $field)
            ->willReturn(true);

        $this->assertFalse(
            $this->workflow_update_checker->canFieldBeUpdated(
                $artifact,
                $field,
                $last_changeset_value,
                $submitted_value,
                $is_submission,
                $user
            )
        );
    }

    public function testCanFieldBeUpdatedReturnsFalseWhenFieldIsNotReadOnly()
    {
        $artifact             = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $field                = $this->createMock(\Tuleap\Tracker\FormElement\Field\TrackerField::class);
        $last_changeset_value = $this->createMock(\Tracker_Artifact_ChangesetValue::class);
        $submitted_value      = 'Arguslike';
        $is_submission        = false;
        $user                 = $this->createMock(\PFUser::class);

        $field->method('hasChanges')->willReturn(true);
        $this->frozen_field_detector
            ->method('isFieldFrozen')
            ->willReturn(false);

        $this->assertTrue(
            $this->workflow_update_checker->canFieldBeUpdated(
                $artifact,
                $field,
                $last_changeset_value,
                $submitted_value,
                $is_submission,
                $user
            )
        );
    }

    public function testCanFieldBeUpdatedChecksFieldWhenLastChangesetWasNull()
    {
        $artifact             = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $field                = $this->createMock(\Tuleap\Tracker\FormElement\Field\TrackerField::class);
        $last_changeset_value = null;
        $submitted_value      = 'Arguslike';
        $is_submission        = false;
        $user                 = $this->createMock(\PFUser::class);

        $this->frozen_field_detector
            ->method('isFieldFrozen')
            ->willReturn(false);

        $this->assertTrue(
            $this->workflow_update_checker->canFieldBeUpdated(
                $artifact,
                $field,
                $last_changeset_value,
                $submitted_value,
                $is_submission,
                $user
            )
        );
    }

    public function testCanFieldBeUpdatedChecksFieldWhenSubmittedValueIsNull()
    {
        $artifact             = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $field                = $this->createMock(\Tuleap\Tracker\FormElement\Field\TrackerField::class);
        $last_changeset_value = $this->createMock(\Tracker_Artifact_ChangesetValue::class);
        $submitted_value      = null;
        $is_submission        = false;
        $user                 = $this->createMock(\PFUser::class);

        $this->frozen_field_detector
            ->method('isFieldFrozen')
            ->willReturn(false);

        $this->assertTrue(
            $this->workflow_update_checker->canFieldBeUpdated(
                $artifact,
                $field,
                $last_changeset_value,
                $submitted_value,
                $is_submission,
                $user
            )
        );
    }
}
