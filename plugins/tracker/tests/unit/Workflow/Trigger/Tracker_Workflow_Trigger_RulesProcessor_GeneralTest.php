<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Workflow\WorkflowBackendLogger;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_Workflow_Trigger_RulesProcessor_GeneralTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalLanguageMock;

    /**
     * @var Mockery\MockInterface|Artifact
     */
    private $parent;
    private $artifact;
    private $rules_processor;
    private $target_field_id;
    private $target_value_id;
    private $rule;
    private $target_field;
    private $target_value;

    protected function setUp(): void
    {
        $this->parent = Mockery::spy(Artifact::class);
        $this->parent->shouldReceive('getTrackerId')->andReturn(899);
        $task_tracker = Mockery::spy(Tracker::class);
        $task_tracker->shouldReceive('getId')->andReturn(899);
        $this->artifact = new Artifact(1, 899, null, 10, null);
        $this->artifact->setChangesets([\Mockery::spy(\Tracker_Artifact_Changeset::class)]);
        $this->artifact->setParentWithoutPermissionChecking($this->parent);
        $this->artifact->setTracker($task_tracker);
        $this->rules_processor = \Mockery::mock(
            \Tracker_Workflow_Trigger_RulesProcessor::class . '[getRuleStrategy]',
            [
                new Tracker_Workflow_WorkflowUser(),
                new WorkflowBackendLogger(new \Psr\Log\NullLogger(), \Psr\Log\LogLevel::DEBUG)
            ]
        )->makePartial()->shouldAllowMockingProtectedMethods();

        $this->target_field_id = 569;
        $this->target_field = Mockery::spy(Tracker_FormElement_Field_Selectbox::class);
        $this->target_field->shouldReceive('getId')->andReturn($this->target_field_id);
        $this->target_field->shouldReceive('getTracker')->andReturn($task_tracker);
        $this->target_value_id = 7;
        $this->target_value    = Mockery::spy(Tracker_FormElement_Field_List_BindValue::class);
        $this->target_value->shouldReceive('getId')->andReturn($this->target_value_id);

        $this->rule = new Tracker_Workflow_Trigger_TriggerRule(
            1,
            new Tracker_Workflow_Trigger_FieldValue(
                $this->target_field,
                $this->target_value
            ),
            Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE,
            [\Mockery::spy(\Tracker_Workflow_Trigger_FieldValue::class)]
        );
    }

    public function testItDoesNothingWhenArtifactHasNoParents(): void
    {
        $this->artifact->setParentWithoutPermissionChecking(Artifact::NO_PARENT);

        // expect no errors
        $this->rules_processor->process($this->artifact, $this->rule);
    }

    public function testItAlwaysApplyRuleWhenAtLeastOneValueIsSet(): void
    {
        $fields_data = [
            $this->target_field_id => $this->target_value_id
        ];
        $send_notification = true;

        $this->parent->shouldReceive('createNewChangeset')
            ->with($fields_data, Mockery::any(), Mockery::type(Tracker_Workflow_WorkflowUser::class), $send_notification, Tracker_Artifact_Changeset_Comment::HTML_COMMENT)
            ->once();

        $this->rules_processor->process($this->artifact, $this->rule);
    }

    public function testItDoesntSetTargetValueIfAlreadySet(): void
    {
        $changeset_value_list = new Tracker_Artifact_ChangesetValue_List(74, Mockery::mock(Tracker_Artifact_Changeset::class), $this->target_field, null, [$this->target_value]);
        $this->parent->shouldReceive('getValue')->with($this->target_field)->andReturns($changeset_value_list);
        $this->parent->shouldReceive('createNewChangeset')->never();
        $this->rules_processor->process($this->artifact, $this->rule);
    }
}
