<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker;
use Transition;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;
use Tuleap\Tracker\Workflow\PostAction\Update\SetIntValue;
use Workflow;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SetIntValueUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SetIntValueUpdater $updater;
    private SetIntValueRepository&MockObject $set_int_value_repository;
    private SetIntValueValidator&MockObject $validator;
    private Tracker $tracker;

    #[\PHPUnit\Framework\Attributes\Before]
    public function createUpdater(): void
    {
        $this->set_int_value_repository = $this->createMock(SetIntValueRepository::class);
        $this->tracker                  = TrackerTestBuilder::aTracker()->build();
        $this->validator                = $this->createMock(SetIntValueValidator::class);

        $this->updater = new SetIntValueUpdater($this->set_int_value_repository, $this->validator);
    }

    public function testUpdateAddsNewSetIntValueActions(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('getTracker')->willReturn(TrackerTestBuilder::aTracker()->build());
        $transition = $this->createMock(Transition::class);
        $transition->method('getId')->willReturn(1);
        $transition->method('getWorkflow')->willReturn($workflow);

        $added_action = new SetIntValue(43, 1);
        $actions      = new PostActionCollection($added_action);

        $this->validator
            ->method('validate')
            ->with($this->tracker, $added_action);

        $this->set_int_value_repository
            ->method('deleteAllByTransition')
            ->with($transition);

        $this->set_int_value_repository->expects($this->atLeast(1))
            ->method('create')
            ->with($transition, $added_action);

        $this->updater->updateByTransition($actions, $transition);
    }

    public function testUpdateDeleteAndRecreatesSetIntValueActionsWhichAlreadyExists(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('getTracker')->willReturn(TrackerTestBuilder::aTracker()->build());
        $transition = $this->createMock(Transition::class);
        $transition->method('getId')->willReturn(1);
        $transition->method('getWorkflow')->willReturn($workflow);

        $updated_action = new SetIntValue(43, 1);
        $actions        = new PostActionCollection($updated_action);

        $this->validator
            ->method('validate')
            ->with($this->tracker, $updated_action);

        $this->set_int_value_repository->expects($this->atLeast(1))
            ->method('deleteAllByTransition')
            ->with($transition);

        $this->set_int_value_repository->expects($this->atLeast(1))
            ->method('create')
            ->with($transition, $updated_action);

        $this->updater->updateByTransition($actions, $transition);
    }

    public function testUpdateDeletesRemovedSetIntValueActions(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('getTracker')->willReturn(TrackerTestBuilder::aTracker()->build());
        $transition = $this->createMock(Transition::class);
        $transition->method('getId')->willReturn(1);
        $transition->method('getWorkflow')->willReturn($workflow);

        $action  = new SetIntValue(43, 1);
        $actions = new PostActionCollection($action);

        $this->validator
            ->method('validate')
            ->with($this->tracker, $action);

        $this->set_int_value_repository->expects($this->atLeast(1))
            ->method('deleteAllByTransition')
            ->with($transition);

        $this->set_int_value_repository->expects($this->atLeast(1))
            ->method('create')
            ->with($transition, $action);

        $this->updater->updateByTransition($actions, $transition);
    }
}
