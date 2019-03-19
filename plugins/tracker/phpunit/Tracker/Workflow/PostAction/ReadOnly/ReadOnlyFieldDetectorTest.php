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

namespace Tuleap\Tracker\Workflow\PostAction\ReadOnly;

require_once __DIR__ . '/../../../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Workflow\PostAction\PostActionsRetriever;
use Tuleap\Tracker\Workflow\Transition\NoTransitionForStateException;

final class ReadOnlyFieldDetectorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var ReadOnlyFieldDetector */
    private $read_only_field_detector;
    /** @var Mockery\MockInterface */
    private $post_actions_retriever;

    protected function setUp(): void
    {
        $this->post_actions_retriever   = Mockery::mock(PostActionsRetriever::class);
        $this->read_only_field_detector = new ReadOnlyFieldDetector($this->post_actions_retriever);
    }

    public function testIsFieldReadOnlyReturnsFalseWhenNoWorkflow()
    {
        $artifact = Mockery::mock(\Tracker_Artifact::class);
        $field    = Mockery::mock(\Tracker_FormElement_Field::class);

        $artifact->shouldReceive('getWorkflow')->andReturnNull();

        $this->assertFalse(
            $this->read_only_field_detector->isFieldReadOnly($artifact, $field)
        );
    }

    public function testIsFieldReadOnlyReturnsFalseWhenWorkflowIsNotUsed()
    {
        $artifact = Mockery::mock(\Tracker_Artifact::class);
        $field    = Mockery::mock(\Tracker_FormElement_Field::class);

        $workflow = Mockery::mock(\Workflow::class);
        $workflow->shouldReceive('isUsed')->andReturnFalse();
        $artifact->shouldReceive('getWorkflow')->andReturns($workflow);

        $this->assertFalse(
            $this->read_only_field_detector->isFieldReadOnly($artifact, $field)
        );
    }

    public function testIsFieldReadOnlyReturnsFalseWhenWorkflowIsAdvanced()
    {
        $artifact = Mockery::mock(\Tracker_Artifact::class);
        $field    = Mockery::mock(\Tracker_FormElement_Field::class);

        $workflow = Mockery::mock(\Workflow::class);
        $workflow->shouldReceive('isUsed')->andReturnTrue();
        $workflow->shouldReceive('isAdvanced')->andReturnTrue();
        $artifact->shouldReceive('getWorkflow')->andReturns($workflow);

        $this->assertFalse(
            $this->read_only_field_detector->isFieldReadOnly($artifact, $field)
        );
    }

    public function testIsFieldReadOnlyReturnsFalseWhenNoTransitionIsDefinedForCurrentState()
    {
        $artifact = Mockery::mock(\Tracker_Artifact::class);
        $field    = Mockery::mock(\Tracker_FormElement_Field::class);

        $workflow = $this->mockSimpleModeWorkflow();
        $workflow->shouldReceive('getFirstTransitionForCurrentState')
            ->with($artifact)
            ->andThrows(new NoTransitionForStateException());
        $artifact->shouldReceive('getWorkflow')->andReturns($workflow);

        $this->assertFalse(
            $this->read_only_field_detector->isFieldReadOnly($artifact, $field)
        );
    }

    public function testIsFieldReadOnlyReturnsFalseWhenNoReadOnlyFieldsPostAction()
    {
        $artifact = Mockery::mock(\Tracker_Artifact::class);
        $field    = Mockery::mock(\Tracker_FormElement_Field::class);

        $transition = Mockery::mock(\Transition::class);
        $workflow   = $this->mockSimpleModeWorkflow();
        $workflow->shouldReceive('getFirstTransitionForCurrentState')
            ->andReturns($transition);
        $artifact->shouldReceive('getWorkflow')->andReturns($workflow);
        $this->post_actions_retriever
            ->shouldReceive('getReadOnlyFields')
            ->with($transition)
            ->andThrows(new NoReadOnlyFieldsPostActionException());

        $this->assertFalse(
            $this->read_only_field_detector->isFieldReadOnly($artifact, $field)
        );
    }

    public function testIsFieldReadOnlyReturnsFalseWhenGivenFieldIsNotAmongReadOnlyFields()
    {
        $field    = Mockery::mock(\Tracker_FormElement_Field::class);
        $artifact = $this->mockArtifactWithWorkflow();

        $this->mockReadOnlyFields(242, 566);
        $field->shouldReceive('getId')->andReturns('312');

        $this->assertFalse(
            $this->read_only_field_detector->isFieldReadOnly($artifact, $field)
        );
    }

    public function testIsFieldReadOnlyReturnsTrueWhenGivenFieldIsReadOnly()
    {
        $field    = Mockery::mock(\Tracker_FormElement_Field::class);
        $artifact = $this->mockArtifactWithWorkflow();

        $this->mockReadOnlyFields(242, 312, 566);
        $field->shouldReceive('getId')->andReturns('312');

        $this->assertTrue(
            $this->read_only_field_detector->isFieldReadOnly($artifact, $field)
        );
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
