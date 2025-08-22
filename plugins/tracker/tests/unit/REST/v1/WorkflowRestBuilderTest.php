<?php
/**
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\REST\v1;

use Tracker_RulesManager;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\REST\WorkflowRestBuilder;
use Tuleap\Tracker\REST\WorkflowTransitionRepresentation;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Workflow_Transition_Condition_Permissions;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WorkflowRestBuilderTest extends TestCase
{
    private WorkflowRestBuilder $builder;
    private \PFUser $user;
    private \Tuleap\Tracker\Tracker $tracker;
    private \PHPUnit\Framework\MockObject\MockObject|\Workflow $workflow;
    private \PHPUnit\Framework\MockObject\MockObject|\Tuleap\Tracker\FormElement\Field\ListField $field;
    private Workflow_Transition_Condition_Permissions|\PHPUnit\Framework\MockObject\MockObject $condition_permissions;
    /**
     * @var Tracker_RulesManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $global_rules_manager;

    protected function setUp(): void
    {
        $this->condition_permissions = $this->createMock(Workflow_Transition_Condition_Permissions::class);
        $this->builder               = $this->createPartialMock(WorkflowRestBuilder::class, ['getConditionPermissions']);
        $this->builder->method('getConditionPermissions')->willReturn($this->condition_permissions);
        $this->workflow             = $this->createMock(\Workflow::class);
        $this->field                = $this->createMock(\Tuleap\Tracker\FormElement\Field\ListField::class);
        $this->user                 = UserTestBuilder::buildWithDefaults();
        $this->global_rules_manager = $this->createMock(Tracker_RulesManager::class);
        $this->global_rules_manager->method('getAllDateRulesByTrackerId')->willReturn([]);
        $this->global_rules_manager->method('getAllListRulesByTrackerWithOrder')->willReturn([]);
        $this->tracker = TrackerTestBuilder::aTracker()->withId(1)->build();
    }

    public function testItReturnNullWhenUserCanNotReadField(): void
    {
        $this->workflow->method('getField')->willReturn($this->field);
        $this->field->method('userCanRead')->willReturn(false);

        self::assertNull($this->builder->getWorkflowRepresentation($this->workflow, $this->user));
    }

    public function testItReturnsEmptyTransitions(): void
    {
        $this->mockWorkflow();
        $this->workflow->method('getTransitions')->willReturn([]);
        $this->field->method('userCanRead')->willReturn(true);

        $representation = $this->builder->getWorkflowRepresentation($this->workflow, $this->user);
        self::assertEmpty($representation->transitions);
    }

    public function testItReturnsTransitionsForNewArtifact(): void
    {
        $this->mockWorkflow();
        $this->field->method('userCanRead')->willReturn(true);

        $this->workflow->method('getTransitions')->willReturn(
            [
                new \Transition(
                    1,
                    2,
                    null,
                    ListStaticValueBuilder::aStaticValue('label')->withId(20)->build()
                ),
            ]
        );
        $this->condition_permissions->method('isUserAllowedToSeeTransition')->willReturn(true);
        $expected_transition = new WorkflowTransitionRepresentation();
        $expected_transition->build(1, null, 20);

        $representation = $this->builder->getWorkflowRepresentation($this->workflow, $this->user);
        self::assertEquals([$expected_transition], $representation->transitions);
    }

    public function testItReturnsEmptyTransitionsWhenUserCanNotSeeConditions(): void
    {
        $this->mockWorkflow();
        $this->field->method('userCanRead')->willReturn(true);

        $this->workflow->method('getTransitions')->willReturn(
            [
                new \Transition(
                    1,
                    2,
                    ListStaticValueBuilder::aStaticValue('label')->withId(10)->build(),
                    ListStaticValueBuilder::aStaticValue('label')->withId(20)->build()
                ),
            ]
        );
        $this->condition_permissions->method('isUserAllowedToSeeTransition')->willReturn(false);

        $representation = $this->builder->getWorkflowRepresentation($this->workflow, $this->user);
        self::assertEmpty($representation->transitions);
    }

    public function testItReturnsTransitions(): void
    {
        $this->mockWorkflow();
        $this->field->method('userCanRead')->willReturn(true);

        $this->workflow->method('getTransitions')->willReturn(
            [
                new \Transition(
                    1,
                    2,
                    ListStaticValueBuilder::aStaticValue('label')->withId(10)->build(),
                    ListStaticValueBuilder::aStaticValue('label')->withId(20)->build()
                ),
            ]
        );
        $this->condition_permissions->method('isUserAllowedToSeeTransition')->willReturn(true);
        $expected_transition = new WorkflowTransitionRepresentation();
        $expected_transition->build(1, 10, 20);

        $representation = $this->builder->getWorkflowRepresentation($this->workflow, $this->user);
        self::assertEquals([$expected_transition], $representation->transitions);
    }

    private function mockWorkflow(): void
    {
        $this->workflow->method('getField')->willReturn($this->field);
        $this->workflow->method('getGlobalRulesManager')->willReturn($this->global_rules_manager);
        $this->workflow->method('getTrackerId')->willReturn(1);
        $this->workflow->method('getTracker')->willReturn($this->tracker);
        $this->workflow->method('getFieldId')->willReturn(2);
        $this->workflow->method('isLegacy')->willReturn(false);
        $this->workflow->method('isAdvanced')->willReturn(false);
    }
}
