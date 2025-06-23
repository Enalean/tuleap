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

namespace Tuleap\Tracker\REST\v1\Workflow;

use PHPUnit\Framework\MockObject\MockObject;
use TrackerFactory;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\REST\WorkflowTransitionPOSTRepresentation;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionCreator;
use Tuleap\Tracker\Workflow\Transition\TransitionCreationParameters;
use UserManager;
use Workflow;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TransitionPOSTHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TransitionPOSTHandler $handler;
    private UserManager&MockObject $user_manager;
    private TrackerFactory&MockObject $tracker_factory;
    private ProjectStatusVerificator&MockObject $project_status_verificator;
    private TransitionsPermissionsChecker&MockObject $permissions_checker;
    private \WorkflowFactory&MockObject $workflow_factory;
    private \TransitionFactory&MockObject $transition_factory;
    private TransitionValidator&MockObject $validator;
    private StateFactory&MockObject $state_factory;
    private TransitionCreator&MockObject $transition_creator;

    private const TRACKER_ID = 196;
    private const FROM_ID    = 134;
    private const TO_ID      = 279;

    protected function setUp(): void
    {
        $this->user_manager               = $this->createMock(UserManager::class);
        $this->tracker_factory            = $this->createMock(TrackerFactory::class);
        $this->project_status_verificator = $this->createMock(ProjectStatusVerificator::class);
        $this->permissions_checker        = $this->createMock(TransitionsPermissionsChecker::class);
        $this->workflow_factory           = $this->createMock(\WorkflowFactory::class);
        $this->transition_factory         = $this->createMock(\TransitionFactory::class);
        $this->validator                  = $this->createMock(TransitionValidator::class);
        $this->state_factory              = $this->createMock(StateFactory::class);
        $this->transition_creator         = $this->createMock(TransitionCreator::class);

        $this->handler = new TransitionPOSTHandler(
            $this->user_manager,
            $this->tracker_factory,
            $this->project_status_verificator,
            $this->permissions_checker,
            $this->workflow_factory,
            $this->transition_factory,
            $this->validator,
            new DBTransactionExecutorPassthrough(),
            $this->state_factory,
            $this->transition_creator
        );
    }

    public function testHandleCreatesATransitionAndReturnsItsRepresentation(): void
    {
        $current_user = $this->createMock(\PFUser::class);
        $this->user_manager
            ->method('getCurrentUser')
            ->willReturn($current_user);
        $tracker = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $project = $this->createMock(\Project::class);
        $tracker->method('getProject')->willReturn($project);
        $this->tracker_factory
            ->method('getTrackerById')
            ->with(self::TRACKER_ID)
            ->willReturn($tracker);
        $this->project_status_verificator
            ->method('checkProjectStatusAllowsAllUsersToAccessIt')
            ->with($project);
        $this->permissions_checker
            ->method('checkCreate')
            ->with($current_user, $tracker);
        $workflow = $this->buildAdvancedWorkflow();
        $this->workflow_factory
            ->method('getWorkflowByTrackerId')
            ->with(self::TRACKER_ID)
            ->willReturn($workflow);
        $validated_parameters = new TransitionCreationParameters(self::FROM_ID, self::TO_ID);
        $this->validator
            ->method('validateForCreation')
            ->with($workflow, self::FROM_ID, self::TO_ID)
            ->willReturn($validated_parameters);

        $new_transition = $this->createMock(\Transition::class);
        $new_transition->method('getId')->willReturn(86);

        $this->transition_factory
            ->method('createAndSaveTransition')
            ->with($workflow, $validated_parameters)
            ->willReturn($new_transition);

        $this->state_factory->expects($this->never())->method('getStateFromValueId');
        $this->transition_creator->expects($this->never())->method('createTransitionInState');

        $result = $this->handler->handle(self::TRACKER_ID, self::FROM_ID, self::TO_ID);

        $expected = new WorkflowTransitionPOSTRepresentation($new_transition);

        $this->assertEquals($expected, $result);
    }

    public function testHandleForSimpleModeAlsoReplicatesTransitionFromFirstSibling(): void
    {
        $current_user = $this->createMock(\PFUser::class);
        $this->user_manager
            ->method('getCurrentUser')
            ->willReturn($current_user);
        $tracker = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $project = $this->createMock(\Project::class);
        $tracker->method('getProject')->willReturn($project);
        $this->tracker_factory->method('getTrackerById')->willReturn($tracker);
        $this->project_status_verificator->method('checkProjectStatusAllowsAllUsersToAccessIt');
        $this->permissions_checker->method('checkCreate');
        $this->validator->method('validateForCreation')->willReturn(
            new TransitionCreationParameters(self::FROM_ID, self::TO_ID)
        );

        $new_transition = $this->createMock(\Transition::class);
        $new_transition->method('getId')->willReturn(965);

        $this->transition_factory
            ->method('createAndSaveTransition')
            ->willReturn($new_transition);

        $workflow = $this->buildSimpleWorkflow();

        $this->workflow_factory
            ->method('getWorkflowByTrackerId')
            ->with(self::TRACKER_ID)
            ->willReturn($workflow);

        $this->state_factory->expects($this->once())
            ->method('getStateFromValueId')
            ->with($workflow, self::TO_ID);

        $this->transition_creator->expects($this->once())->method('createTransitionInState')
            ->willReturn($new_transition);

        $this->handler->handle(self::TRACKER_ID, self::FROM_ID, self::TO_ID);
    }

    public function testHandleThrowsWhenNoTrackerFound(): void
    {
        $this->user_manager
            ->method('getCurrentUser')
            ->willReturn($this->createMock(\PFUser::class));
        $this->tracker_factory
            ->method('getTrackerById')
            ->with(self::TRACKER_ID)
            ->willReturn(null);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(404);

        $this->handler->handle(self::TRACKER_ID, self::FROM_ID, self::TO_ID);
    }

    public function testHandleThrowsWhenTrackerHasNoWorkflow(): void
    {
        $this->user_manager
            ->method('getCurrentUser')
            ->willReturn($this->createMock(\PFUser::class));
        $tracker = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $project = $this->createMock(\Project::class);
        $tracker->method('getProject')->willReturn($project);
        $this->tracker_factory
            ->method('getTrackerById')
            ->willReturn($tracker);
        $this->project_status_verificator->method('checkProjectStatusAllowsAllUsersToAccessIt');
        $this->permissions_checker->method('checkCreate');

        $this->workflow_factory
            ->method('getWorkflowByTrackerId')
            ->with(self::TRACKER_ID)
            ->willReturn(null);

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(404);

        $this->handler->handle(self::TRACKER_ID, self::FROM_ID, self::TO_ID);
    }

    private function buildAdvancedWorkflow(): Workflow&MockObject
    {
        $workflow = $this->createMock(\Workflow::class);
        $workflow->method('isAdvanced')->willReturn(true);

        return $workflow;
    }

    private function buildSimpleWorkflow(): Workflow&MockObject
    {
        $workflow = $this->createMock(\Workflow::class);
        $workflow->method('isAdvanced')->willReturn(false);

        return $workflow;
    }
}
