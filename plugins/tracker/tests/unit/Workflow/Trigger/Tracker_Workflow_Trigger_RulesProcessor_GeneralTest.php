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

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Workflow\Trigger\Siblings\SiblingsRetriever;
use Tuleap\Tracker\Workflow\WorkflowBackendLogger;

#[DisableReturnValueGenerationForTestDoubles]
final class Tracker_Workflow_Trigger_RulesProcessor_GeneralTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    private Artifact&MockObject $parent;
    private Artifact $artifact;
    private Tracker_Workflow_Trigger_RulesProcessor&MockObject $rules_processor;
    private int $target_field_id;
    private int $target_value_id;
    private Tracker_Workflow_Trigger_TriggerRule $rule;
    private Tracker_FormElement_Field_Selectbox&MockObject $target_field;
    private Tracker_FormElement_Field_List_BindValue&MockObject $target_value;

    protected function setUp(): void
    {
        $this->parent = $this->createMock(Artifact::class);
        $this->parent->method('getXRef')->willReturn(899);
        $this->parent->method('getTrackerId')->willReturn(899);
        $task_tracker   = TrackerTestBuilder::aTracker()->withId(899)->build();
        $this->artifact = new Artifact(1, 899, 0, 10, null);
        $this->artifact->setChangesets([ChangesetTestBuilder::aChangeset(2001)->ofArtifact($this->artifact)->build()]);
        $this->artifact->setParentWithoutPermissionChecking($this->parent);
        $this->artifact->setTracker($task_tracker);
        $this->rules_processor = $this->getMockBuilder(Tracker_Workflow_Trigger_RulesProcessor::class)
            ->onlyMethods(['getRuleStrategy'])
            ->setConstructorArgs([
                new Tracker_Workflow_WorkflowUser(),
                $this->createMock(SiblingsRetriever::class),
                new WorkflowBackendLogger(new NullLogger(), LogLevel::DEBUG),
            ])->getMock();

        $this->target_field_id = 569;
        $this->target_field    = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $this->target_field->method('getId')->willReturn($this->target_field_id);
        $this->target_field->method('getTracker')->willReturn($task_tracker);
        $this->target_value_id = 7;
        $this->target_value    = $this->createMock(Tracker_FormElement_Field_List_BindValue::class);
        $this->target_value->method('getId')->willReturn($this->target_value_id);

        $field_value = $this->createMock(Tracker_Workflow_Trigger_FieldValue::class);
        $field_value->method('getAsChangesetComment');

        $this->rule = new Tracker_Workflow_Trigger_TriggerRule(
            1,
            new Tracker_Workflow_Trigger_FieldValue(
                $this->target_field,
                $this->target_value
            ),
            Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE,
            [$field_value]
        );
    }

    public function testItDoesNothingWhenArtifactHasNoParents(): void
    {
        $this->artifact->setParentWithoutPermissionChecking(Artifact::NO_PARENT);

        $this->expectNotToPerformAssertions();

        // expect no errors
        $this->rules_processor->process($this->artifact, $this->rule);
    }

    public function testItAlwaysApplyRuleWhenAtLeastOneValueIsSet(): void
    {
        $fields_data       = [
            $this->target_field_id => $this->target_value_id,
        ];
        $send_notification = true;

        $this->parent->method('getValue')->willReturn(null);

        $this->parent->expects($this->once())->method('createNewChangeset')
            ->with($fields_data, $this->anything(), $this->isInstanceOf(Tracker_Workflow_WorkflowUser::class), $send_notification, CommentFormatIdentifier::HTML);

        $this->rules_processor->process($this->artifact, $this->rule);
    }

    public function testItDoesntSetTargetValueIfAlreadySet(): void
    {
            $changeset_value_list = new Tracker_Artifact_ChangesetValue_List(74, $this->createMock(Tracker_Artifact_Changeset::class), $this->target_field, null, [$this->target_value]);
        $this->parent->method('getValue')->with($this->target_field)->willReturn($changeset_value_list);
        $this->parent->expects($this->never())->method('createNewChangeset');
        $this->rules_processor->process($this->artifact, $this->rule);
    }
}
