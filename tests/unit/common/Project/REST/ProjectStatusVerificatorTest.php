<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Project\REST;

use Luracast\Restler\RestException;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\GlobalLanguageMock;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\ProjectAccessSuspendedException;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class ProjectStatusVerificatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private ProjectStatusVerificator $verificator;
    private ProjectAccessChecker&MockObject $access_checker;

    protected function setUp(): void
    {
        $this->access_checker = $this->createMock(ProjectAccessChecker::class);
        $this->verificator    = new ProjectStatusVerificator($this->access_checker);
    }

    public function testEverybodyCanAccessANotSuspendedProject(): void
    {
        self::expectNotToPerformAssertions();
        $project = ProjectTestBuilder::aProject()
            ->withStatusActive()
            ->build();

        $this->verificator->checkProjectStatusAllowsAllUsersToAccessIt($project);
    }

    public function testNobodyCanAccessASuspendedProject(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withStatusSuspended()
            ->build();

        self::expectException(RestException::class);
        self::expectExceptionCode(403);
        self::expectExceptionMessage('This project is suspended');

        $this->verificator->checkProjectStatusAllowsAllUsersToAccessIt($project);
    }

    public function testRegularUsersCantAccessASuspendedProject(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $user = UserTestBuilder::buildWithDefaults();

        $this->access_checker->method('checkUserCanAccessProject')->with($user, $project)->willThrowException(new ProjectAccessSuspendedException());

        self::expectException(RestException::class);
        self::expectExceptionCode(403);
        self::expectExceptionMessage('This project is suspended');

        $this->verificator->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $project
        );
    }

    public function testSiteAdminUsersCanAccessASuspendedProject(): void
    {
        self::expectNotToPerformAssertions();
        $project = ProjectTestBuilder::aProject()->build();

        $user = UserTestBuilder::buildWithDefaults();

        $this->access_checker->method('checkUserCanAccessProject')->with($user, $project);

        $this->verificator->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $user,
            $project
        );
    }
}
