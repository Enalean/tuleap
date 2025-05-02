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
use Transition;
use Tuleap\Tracker\Workflow\PostAction\Update\CIBuildValue;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CIBuildUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private CIBuildValueUpdater $updater;
    private CIBuildValueRepository&MockObject $ci_build_repository;
    private CIBuildValueValidator&MockObject $validator;

    #[\PHPUnit\Framework\Attributes\Before]
    public function createUpdater(): void
    {
        $this->ci_build_repository = $this->createMock(CIBuildValueRepository::class);

        $this->validator = $this->createMock(CIBuildValueValidator::class);
        $this->updater   = new CIBuildValueUpdater($this->ci_build_repository, $this->validator);
    }

    public function testUpdateAddsNewCIBuildActions(): void
    {
        $transition = $this->createMock(Transition::class);
        $transition->method('getId')->willReturn(1);

        $added_action = new CIBuildValue('http://example.test');
        $actions      = new PostActionCollection($added_action);

        $this->validator
            ->method('validate')
            ->with($added_action);

        $this->ci_build_repository->expects($this->atLeast(1))
            ->method('create')
            ->with($transition, $added_action);

        $this->ci_build_repository->method('deleteAllByTransition');

        $this->updater->updateByTransition($actions, $transition);
    }

    public function testUpdateDeleteAndCreateCIBuildActionsWhichAlreadyExists(): void
    {
        $transition = $this->createMock(Transition::class);
        $transition->method('getId')->willReturn(1);

        $updated_action = new CIBuildValue('http://example.test');
        $actions        = new PostActionCollection($updated_action);

        $this->validator
            ->method('validate')
            ->with($updated_action);

        $this->ci_build_repository->expects($this->atLeast(1))
            ->method('deleteAllByTransition')
            ->with($transition);

        $this->ci_build_repository
            ->method('create')
            ->with($transition, $updated_action);

        $this->updater->updateByTransition($actions, $transition);
    }

    public function testUpdateDeletesRemovedCIBuildActions(): void
    {
        $transition = $this->createMock(Transition::class);
        $transition->method('getId')->willReturn(1);

        $action  = new CIBuildValue('http://example.test');
        $actions = new PostActionCollection($action);

        $this->validator
            ->method('validate')
            ->with($action);

        $this->ci_build_repository->expects($this->atLeast(1))
            ->method('deleteAllByTransition')
            ->with($transition);

        $this->ci_build_repository
            ->method('create')
            ->with($transition, $action);

        $this->updater->updateByTransition($actions, $transition);
    }
}
