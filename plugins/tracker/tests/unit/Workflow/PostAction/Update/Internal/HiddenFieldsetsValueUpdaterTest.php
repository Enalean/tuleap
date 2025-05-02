<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

use PHPUnit\Framework\MockObject\MockObject;
use Transition;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Workflow\PostAction\Update\HiddenFieldsetsValue;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;
use Workflow;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class HiddenFieldsetsValueUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private HiddenFieldsetsValueUpdater $updater;
    private HiddenFieldsetsValueRepository&MockObject $hidden_fieldsets_value_repository;
    private HiddenFieldsetsValueValidator&MockObject $hidden_fieldsets_value_validator;

    #[\PHPUnit\Framework\Attributes\Before]
    public function createUpdater(): void
    {
        $this->hidden_fieldsets_value_repository = $this->createMock(HiddenFieldsetsValueRepository::class);

        $this->hidden_fieldsets_value_validator = $this->createMock(HiddenFieldsetsValueValidator::class);

        $this->updater = new HiddenFieldsetsValueUpdater(
            $this->hidden_fieldsets_value_repository,
            $this->hidden_fieldsets_value_validator
        );
    }

    public function testUpdateAddsNewHiddenFieldsetsActions(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('getTracker')->willReturn(TrackerTestBuilder::aTracker()->build());
        $transition = $this->createMock(Transition::class);
        $transition->method('getId')->willReturn(1);
        $transition->method('getWorkflow')->willReturn($workflow);

        $added_action = new HiddenFieldsetsValue([]);
        $actions      = new PostActionCollection($added_action);

        $this->hidden_fieldsets_value_validator->expects($this->once())->method('validate');

        $this->hidden_fieldsets_value_repository->method('deleteAllByTransition');
        $this->hidden_fieldsets_value_repository
            ->method('create')
            ->with($transition, $added_action);

        $this->updater->updateByTransition($actions, $transition);
    }

    public function testUpdateDeletesAllPreExistingHiddenFieldsetsActions(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('getTracker')->willReturn(TrackerTestBuilder::aTracker()->build());
        $transition = $this->createMock(Transition::class);
        $transition->method('getId')->willReturn(1);
        $transition->method('getWorkflow')->willReturn($workflow);

        $updated_action = new HiddenFieldsetsValue([]);
        $actions        = new PostActionCollection($updated_action);

        $this->hidden_fieldsets_value_validator->expects($this->once())->method('validate');

        $this->hidden_fieldsets_value_repository->method('create');
        $this->hidden_fieldsets_value_repository->method('deleteAllByTransition')->with($transition);

        $this->updater->updateByTransition($actions, $transition);
    }

    public function testItDoesNothingIfHiddenFieldsetsActionsAreNotValid(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('getTracker')->willReturn(TrackerTestBuilder::aTracker()->build());
        $transition = $this->createMock(Transition::class);
        $transition->method('getId')->willReturn(1);
        $transition->method('getWorkflow')->willReturn($workflow);

        $updated_action = new HiddenFieldsetsValue([]);
        $actions        = new PostActionCollection($updated_action);

        $this->hidden_fieldsets_value_validator->method('validate')->willThrowException(new InvalidPostActionException());

        $this->hidden_fieldsets_value_repository->expects($this->never())->method('deleteAllByTransition');
        $this->hidden_fieldsets_value_repository->expects($this->never())->method('create');

        $this->expectException(InvalidPostActionException::class);

        $this->updater->updateByTransition($actions, $transition);
    }
}
