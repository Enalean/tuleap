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

use Tuleap\Tracker\Workflow\WorkflowBackendLogger;

require_once __DIR__ . '/../../bootstrap.php';

class Tracker_Workflow_Trigger_RulesProcessor_GeneralTest extends TuleapTestCase
{
    /**
     * @var Mockery\MockInterface|Tracker_Artifact
     */
    private $parent;
    private $artifact;
    private $rules_processor;
    private $target_field_id;
    private $target_value_id;
    private $rule;
    private $target_field;
    private $target_value;
    private $task_tracker;

    public function setUp()
    {
        parent::setUp();
        $this->parent = Mockery::spy(Tracker_Artifact::class);
        $this->parent->shouldReceive('getTrackerId')->andReturn(899);
        $this->task_tracker = aTracker()->withId(899)->build();
        $this->artifact = anArtifact()->withChangesets(array(mock('Tracker_Artifact_Changeset')))->withParentWithoutPermissionChecking($this->parent)->withTracker($this->task_tracker)->build();
        $this->rules_processor = partial_mock(
            'Tracker_Workflow_Trigger_RulesProcessor',
            array('getRuleStrategy'),
            array(
                new Tracker_Workflow_WorkflowUser(),
                new WorkflowBackendLogger(Mockery::spy(\Psr\Log\LoggerInterface::class), \Psr\Log\LogLevel::DEBUG)
            )
        );

        $this->target_field_id = 569;
        $this->target_field    = aSelectBoxField()->withId($this->target_field_id)->withTracker($this->task_tracker)->build();
        $this->target_value_id = 7;
        $this->target_value    = aBindStaticValue()->withId($this->target_value_id)->build();

        $this->rule = new Tracker_Workflow_Trigger_TriggerRule(
            1,
            new Tracker_Workflow_Trigger_FieldValue(
                $this->target_field,
                $this->target_value
            ),
            Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE,
            [mock('Tracker_Workflow_Trigger_FieldValue')]
        );
    }

    public function itDoesNothingWhenArtifactHasNoParents()
    {
        $artifact = anArtifact()->withTracker($this->task_tracker)->withoutParentWithoutPermissionChecking()->build();

        // expect no errors
        $this->rules_processor->process($artifact, $this->rule);
    }

    public function itAlwaysApplyRuleWhenAtLeastOneValueIsSet()
    {
        $fields_data = array(
            $this->target_field_id => $this->target_value_id
        );
        $send_notification = true;

        $this->parent->shouldReceive('createNewChangeset')
            ->with($fields_data, Mockery::any(), Mockery::type(Tracker_Workflow_WorkflowUser::class), $send_notification, Tracker_Artifact_Changeset_Comment::HTML_COMMENT)
            ->once();

        $this->rules_processor->process($this->artifact, $this->rule);
    }

    public function itDoesntSetTargetValueIfAlreadySet()
    {
        stub($this->parent)->getValue($this->target_field)->returns(aChangesetValueList()->withValues(array($this->target_value))->build());
        expect($this->parent)->createNewChangeset()->never();
        $this->rules_processor->process($this->artifact, $this->rule);
    }
}
