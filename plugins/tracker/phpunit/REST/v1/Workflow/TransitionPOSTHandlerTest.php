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

namespace Tuleap\Tracker\REST\v1\Workflow;

require_once __DIR__ . '/../../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use TrackerFactory;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\REST\WorkflowTransitionPOSTRepresentation;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionCreator;
use Tuleap\Tracker\Workflow\SimpleMode\TransitionReplicator;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\Tracker\Workflow\Transition\TransitionCreationParameters;
use UserManager;

class TransitionPOSTHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var TransitionPOSTHandler */
    private $handler;
    /** @var Mockery\MockInterface | UserManager */
    private $user_manager;
    /** @var Mockery\MockInterface */
    private $tracker_factory;
    /** @var Mockery\MockInterface */
    private $project_status_verificator;
    /** @var Mockery\MockInterface */
    private $permissions_checker;
    /** @var Mockery\MockInterface */
    private $workflow_factory;
    /** @var Mockery\MockInterface */
    private $transition_factory;
    /** @var Mockery\MockInterface */
    private $validator;
    /** @var Mockery\MockInterface */
    private $state_factory;
    /** @var Mockery\MockInterface */
    private $transition_creator;

    private const TRACKER_ID = 196;
    private const FROM_ID = 134;
    private const TO_ID = 279;

    protected function setUp(): void
    {
        $this->user_manager               = Mockery::mock(UserManager::class);
        $this->tracker_factory            = Mockery::mock(TrackerFactory::class);
        $this->project_status_verificator = Mockery::mock(ProjectStatusVerificator::class);
        $this->permissions_checker        = Mockery::mock(TransitionsPermissionsChecker::class);
        $this->workflow_factory           = Mockery::mock(\WorkflowFactory::class);
        $this->transition_factory         = Mockery::mock(\TransitionFactory::class);
        $this->validator                  = Mockery::mock(TransitionValidator::class);
        $this->state_factory              = Mockery::mock(StateFactory::class);
        $this->transition_creator         = Mockery::mock(TransitionCreator::class);

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

    public function testHandleCreatesATransitionAndReturnsItsRepresentation()
    {
        $current_user = Mockery::mock(\PFUser::class);
        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->andReturn($current_user);
        $tracker = Mockery::mock(\Tracker::class);
        $project = Mockery::mock(\Project::class);
        $tracker->shouldReceive('getProject')->andReturn($project);
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(self::TRACKER_ID)
            ->andReturn($tracker);
        $this->project_status_verificator
            ->shouldReceive('checkProjectStatusAllowsAllUsersToAccessIt')
            ->with($project);
        $this->permissions_checker
            ->shouldReceive('checkCreate')
            ->with($current_user, $tracker);
        $workflow = $this->buildAdvancedWorkflow();
        $this->workflow_factory
            ->shouldReceive('getWorkflowByTrackerId')
            ->with(self::TRACKER_ID)
            ->andReturn($workflow);
        $validated_parameters = new TransitionCreationParameters(self::FROM_ID, self::TO_ID);
        $this->validator
            ->shouldReceive('validateForCreation')
            ->with($workflow, self::FROM_ID, self::TO_ID)
            ->andReturn($validated_parameters);

        $new_transition = Mockery::mock(\Transition::class)
            ->shouldReceive('getId')
            ->andReturn(86)
            ->getMock();
        $this->transition_factory
            ->shouldReceive('createAndSaveTransition')
            ->with($workflow, $validated_parameters)
            ->andReturn($new_transition);

        $this->state_factory->shouldNotReceive('getStateFromValueId');
        $this->transition_creator->shouldNotReceive('createTransitionInState');

        $result = $this->handler->handle(self::TRACKER_ID, self::FROM_ID, self::TO_ID);

        $expected = new WorkflowTransitionPOSTRepresentation();
        $expected->build($new_transition);

        $this->assertEquals($expected, $result);
    }

    public function testHandleForSimpleModeAlsoReplicatesTransitionFromFirstSibling()
    {
        $current_user = Mockery::mock(\PFUser::class);
        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->andReturn($current_user);
        $tracker = Mockery::mock(\Tracker::class);
        $project = Mockery::mock(\Project::class);
        $tracker->shouldReceive('getProject')->andReturn($project);
        $this->tracker_factory->allows('getTrackerById')->andReturn($tracker);
        $this->project_status_verificator->allows('checkProjectStatusAllowsAllUsersToAccessIt');
        $this->permissions_checker->allows('checkCreate');
        $this->validator->allows('validateForCreation')->andReturn(
            new TransitionCreationParameters(self::FROM_ID, self::TO_ID)
        );

        $new_transition = Mockery::mock(\Transition::class)
            ->shouldReceive('getId')
            ->andReturn(965)
            ->getMock();

        $this->transition_factory
            ->shouldReceive('createAndSaveTransition')
            ->andReturn($new_transition);

        $workflow = $this->buildSimpleWorkflow();

        $this->workflow_factory
            ->shouldReceive('getWorkflowByTrackerId')
            ->with(self::TRACKER_ID)
            ->andReturn($workflow);

        $this->state_factory
            ->shouldReceive('getStateFromValueId')
            ->with($workflow, self::TO_ID)
            ->once();

        $this->transition_creator->shouldReceive('createTransitionInState')
            ->once()
            ->andReturn($new_transition);

        $this->handler->handle(self::TRACKER_ID, self::FROM_ID, self::TO_ID);
    }

    public function testHandleThrowsWhenNoTrackerFound()
    {
        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->andReturn(Mockery::mock(\PFUser::class));
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(self::TRACKER_ID)
            ->andReturnNull();

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(404);

        $this->handler->handle(self::TRACKER_ID, self::FROM_ID, self::TO_ID);
    }

    public function testHandleThrowsWhenTrackerHasNoWorkflow()
    {
        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->andReturn(Mockery::mock(\PFUser::class));
        $tracker = Mockery::mock(\Tracker::class);
        $project = Mockery::mock(\Project::class);
        $tracker->shouldReceive('getProject')->andReturn($project);
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->andReturn($tracker);
        $this->project_status_verificator->shouldReceive('checkProjectStatusAllowsAllUsersToAccessIt');
        $this->permissions_checker->shouldReceive('checkCreate');

        $this->workflow_factory
            ->shouldReceive('getWorkflowByTrackerId')
            ->with(self::TRACKER_ID)
            ->andReturnNull();

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(404);

        $this->handler->handle(self::TRACKER_ID, self::FROM_ID, self::TO_ID);
    }

    /**
     * @return Mockery\MockInterface|\Workflow
     */
    private function buildAdvancedWorkflow()
    {
        return Mockery::mock(\Workflow::class)
            ->shouldReceive('isAdvanced')
            ->andReturnTrue()
            ->getMock();
    }

    /**
     * @return Mockery\MockInterface|\Workflow
     */
    private function buildSimpleWorkflow()
    {
        return Mockery::mock(\Workflow::class)
            ->shouldReceive('isAdvanced')
            ->andReturnFalse()
            ->getMock();
    }
}
