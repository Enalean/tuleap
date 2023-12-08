<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\Routing;

use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class ProjectAdministratorCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ProjectAdministratorChecker $project_administrator_checker;

    protected function setUp(): void
    {
        $this->project_administrator_checker = new ProjectAdministratorChecker();
    }

    public function testcheckUserIsProjectAdministratorThrowsWhenNotAdministrator(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(104)->build();
        $user    = UserTestBuilder::aUser()
            ->withMemberOf($project)
            ->withoutSiteAdministrator()
            ->build();

        self::expectException(ForbiddenException::class);
        $this->project_administrator_checker->checkUserIsProjectAdministrator($user, $project);
    }

    public function testCheckUserIsProjectAdministratorDoesNotThrow(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(104)->build();
        $user    = UserTestBuilder::aUser()
            ->withAdministratorOf($project)
            ->withoutSiteAdministrator()
            ->build();

        self::addToAssertionCount(1);
        $this->project_administrator_checker->checkUserIsProjectAdministrator($user, $project);
    }
}
