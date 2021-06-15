<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\Access;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\VerifyUserCanAccessProjectAdministrationStub;

final class ProjectAdministrationLinkPresenterTest extends TestCase
{
    public function testItReturnsNullWhenUserCannotAccessProjectAdministration(): void
    {
        $user     = UserTestBuilder::aUser()->build();
        $project  = ProjectTestBuilder::aProject()->build();
        $verifier = VerifyUserCanAccessProjectAdministrationStub::withNotAllowedAccess();
        self::assertNull(ProjectAdministrationLinkPresenter::fromProject($verifier, $project, $user));
    }

    public function testItReturnsAPresenterWithLinkURI(): void
    {
        $user      = UserTestBuilder::aUser()->build();
        $project   = ProjectTestBuilder::aProject()->withId(201)->build();
        $verifier  = VerifyUserCanAccessProjectAdministrationStub::withPermittedAccess();
        $presenter = ProjectAdministrationLinkPresenter::fromProject($verifier, $project, $user);
        self::assertSame('/project/admin/?group_id=201', $presenter->uri);
    }
}
