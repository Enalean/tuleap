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
use Tuleap\Tracker\Workflow\PostAction\PostActionsRetriever;
use Tuleap\Tracker\Workflow\PostAction\ReadOnly\NoReadOnlyFieldsPostActionException;
use Tuleap\Tracker\Workflow\PostAction\ReadOnly\ReadOnlyFields;
use Tuleap\Tracker\Workflow\Transition\NoTransitionForStateException;

final class WorkflowUpdateCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var WorkflowUpdateChecker */
    private $workflow_update_checker;
    /** @var Mockery\MockInterface */
    private $post_actions_retriever;

    protected function setUp(): void
    {
        $this->post_actions_retriever  = Mockery::mock(PostActionsRetriever::class);
        $this->workflow_update_checker = new WorkflowUpdateChecker($this->post_actions_retriever);
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

    public function testCanFieldBeUpdatedReturnsTrueWhenNoWorkflow()
    {
        $artifact             = Mockery::mock(\Tracker_Artifact::class);
        $field                = $this->mockFieldWithChanges();
        $last_changeset_value = Mockery::mock(\Tracker_Artifact_ChangesetValue::class);
        $submitted_value      = 'Arguslike';
        $is_submission        = false;

        $artifact->shouldReceive('getWorkflow')->andReturnNull();

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

    public function testCanFieldBeUpdatedReturnsTrueWhenWorkflowIsNotUsed()
    {
        $artifact             = Mockery::mock(\Tracker_Artifact::class);
        $field                = $this->mockFieldWithChanges();
        $last_changeset_value = Mockery::mock(\Tracker_Artifact_ChangesetValue::class);
        $submitted_value      = 'Arguslike';
        $is_submission        = false;

        $workflow = Mockery::mock(\Workflow::class);
        $workflow->shouldReceive('isUsed')->andReturnFalse();
        $artifact->shouldReceive('getWorkflow')->andReturns($workflow);

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

    public function testCanFieldBeUpdatedReturnsTrueWhenWorkflowIsAdvanced()
    {
        $artifact             = Mockery::mock(\Tracker_Artifact::class);
        $field                = $this->mockFieldWithChanges();
        $last_changeset_value = Mockery::mock(\Tracker_Artifact_ChangesetValue::class);
        $submitted_value      = 'Arguslike';
        $is_submission        = false;

        $workflow = Mockery::mock(\Workflow::class);
        $workflow->shouldReceive('isUsed')->andReturnTrue();
        $workflow->shouldReceive('isAdvanced')->andReturnTrue();
        $artifact->shouldReceive('getWorkflow')->andReturns($workflow);

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

    public function testCanFieldBeUpdatedReturnsTrueWhenNoTransitionIsDefinedForCurrentState()
    {
        $field                = $this->mockFieldWithChanges();
        $last_changeset_value = Mockery::mock(\Tracker_Artifact_ChangesetValue::class);
        $submitted_value      = 'Arguslike';
        $is_submission        = false;

        $artifact = Mockery::mock(\Tracker_Artifact::class);
        $workflow = $this->mockSimpleModeWorkflow();
        $workflow->shouldReceive('getFirstTransitionForCurrentState')
            ->with($artifact)
            ->andThrows(new NoTransitionForStateException());
        $artifact->shouldReceive('getWorkflow')->andReturns($workflow);

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

    public function testCanFieldBeUpdatedReturnsTrueWhenNoReadOnlyFieldsPostAction()
    {
        $field                = $this->mockFieldWithChanges();
        $last_changeset_value = Mockery::mock(\Tracker_Artifact_ChangesetValue::class);
        $submitted_value      = 'Arguslike';
        $is_submission        = false;

        $transition = Mockery::mock(\Transition::class);
        $artifact   = Mockery::mock(\Tracker_Artifact::class);
        $workflow   = $this->mockSimpleModeWorkflow();
        $workflow->shouldReceive('getFirstTransitionForCurrentState')
            ->andReturns($transition);
        $artifact->shouldReceive('getWorkflow')->andReturns($workflow);
        $this->post_actions_retriever
            ->shouldReceive('getReadOnlyFields')
            ->with($transition)
            ->andThrows(new NoReadOnlyFieldsPostActionException());

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

    public function testCanFieldBeUpdatedReturnsTrueWhenGivenFieldIsNotAmongReadOnlyFields()
    {
        $field                = $this->mockFieldWithChanges();
        $last_changeset_value = Mockery::mock(\Tracker_Artifact_ChangesetValue::class);
        $submitted_value      = 'Arguslike';
        $is_submission        = false;
        $artifact             = $this->mockArtifactWithWorkflow();

        $this->mockReadOnlyFields(242, 566);
        $field->shouldReceive('getId')->andReturns('312');

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

    public function testCanFieldBeUpdatedReturnsFalseWhenGivenFieldIsReadOnly()
    {
        $field                = $this->mockFieldWithChanges();
        $last_changeset_value = Mockery::mock(\Tracker_Artifact_ChangesetValue::class);
        $submitted_value      = 'Arguslike';
        $is_submission        = false;
        $artifact             = $this->mockArtifactWithWorkflow();

        $this->mockReadOnlyFields(242, 312, 566);
        $field->shouldReceive('getId')->andReturns('312');

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

    public function testCanFieldBeUpdatedChecksFieldWhenLastChangesetWasNull()
    {
        $artifact             = Mockery::mock(\Tracker_Artifact::class);
        $field                = Mockery::mock(\Tracker_FormElement_Field::class);
        $last_changeset_value = null;
        $submitted_value      = 'Arguslike';
        $is_submission        = false;
        $artifact->shouldReceive('getWorkflow')->andReturnNull();

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
        $artifact->shouldReceive('getWorkflow')->andReturnNull();

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

    private function mockFieldWithChanges(): Mockery\MockInterface
    {
        return Mockery::mock(\Tracker_FormElement_Field::class)
            ->shouldReceive('hasChanges')
            ->andReturnTrue()
            ->getMock();
    }

    private function mockSimpleModeWorkflow(): Mockery\MockInterface
    {
        $workflow = Mockery::mock(\Workflow::class);
        $workflow->shouldReceive('isUsed')->andReturnTrue();
        $workflow->shouldReceive('isAdvanced')->andReturnFalse();

        return $workflow;
    }

    private function mockArtifactWithWorkflow(): Mockery\MockInterface
    {
        $transition = Mockery::mock(\Transition::class);
        $artifact   = Mockery::mock(\Tracker_Artifact::class);
        $workflow   = $this->mockSimpleModeWorkflow();
        $workflow->shouldReceive('getFirstTransitionForCurrentState')
            ->andReturns($transition);
        $artifact->shouldReceive('getWorkflow')->andReturns($workflow);

        return $artifact;
    }

    private function mockReadOnlyFields(int ...$field_ids): void
    {
        $read_only_fields_post_action = Mockery::mock(ReadOnlyFields::class);
        $this->post_actions_retriever
            ->shouldReceive('getReadOnlyFields')
            ->andReturns($read_only_fields_post_action);
        $read_only_fields_post_action
            ->shouldReceive('getFieldIds')
            ->andReturns($field_ids);
    }
}
