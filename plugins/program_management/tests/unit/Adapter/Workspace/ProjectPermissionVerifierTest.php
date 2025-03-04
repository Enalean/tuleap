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

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Workspace;

use Tuleap\ProgramManagement\Tests\Stub\ProjectIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectPermissionVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItProxiesToPFUser(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(155)->build();
        $user    = UserTestBuilder::aUser()
            ->withId(530)
            ->withoutSiteAdministrator()
            ->withAdministratorOf($project)
            ->build();

        $verifier = new ProjectPermissionVerifier(RetrieveUserStub::withUser($user));
        self::assertTrue($verifier->isProjectAdministrator(UserIdentifierStub::withId(530), ProjectIdentifierStub::buildWithId(155)));
    }
}
