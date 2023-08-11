<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Status;

use Project;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class UpdateStatusCheckerTest extends TestCase
{
    public function testItReturnsAFaultIfNewStatusIsPending(): void
    {
        $result = UpdateStatusChecker::checkProjectStatusCanBeUpdated(
            ProjectTestBuilder::aProject()->build(),
            Project::STATUS_PENDING,
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(SwitchingBackToPendingFault::class, $result->error);
    }

    public function testItReturnsAFaultIfProjectIsAlreadyDeleted(): void
    {
        $result = UpdateStatusChecker::checkProjectStatusCanBeUpdated(
            ProjectTestBuilder::aProject()->withStatusDeleted()->build(),
            Project::STATUS_ACTIVE,
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(UpdateAlreadyDeletedProjectFault::class, $result->error);
    }

    public function testItReturnsAFaultIfProjectToBeDeletedIsTheDefaultAdministrationProject(): void
    {
        $result = UpdateStatusChecker::checkProjectStatusCanBeUpdated(
            ProjectTestBuilder::aProject()->withId(Project::DEFAULT_ADMIN_PROJECT_ID)->withStatusActive()->build(),
            Project::STATUS_DELETED,
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(CannotDeletedDefaultAdminProjectFault::class, $result->error);
    }

    public function testItsOKIfProjectStatusCanBeUpdated(): void
    {
        $result = UpdateStatusChecker::checkProjectStatusCanBeUpdated(
            ProjectTestBuilder::aProject()->withStatusActive()->build(),
            Project::STATUS_SUSPENDED,
        );

        self::assertTrue(Result::isOk($result));
    }
}
