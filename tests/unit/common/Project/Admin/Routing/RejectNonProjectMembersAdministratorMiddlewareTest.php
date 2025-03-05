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

use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Project\Admin\ProjectMembers\EnsureUserCanManageProjectMembersStub;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;
use Tuleap\Test\Stubs\RequestHandlerInterfaceStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RejectNonProjectMembersAdministratorMiddlewareTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testProcessThrowsWhenProjectIsNotAnAttributeOfRequest(): void
    {
        $middleware = new RejectNonProjectMembersAdministratorMiddleware(
            ProvideCurrentUserStub::buildCurrentUserByDefault(),
            EnsureUserCanManageProjectMembersStub::cannotManageMembers(),
        );

        $handler = RequestHandlerInterfaceStub::buildSelf();
        $request = new NullServerRequest();

        $this->expectException(\LogicException::class);
        $middleware->process($request, $handler);

        self::assertFalse($handler->hasBeenCalled());
    }

    public function testProcessSuccessWhenUserIsProjectAdmin(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $user    = UserTestBuilder::aUser()
            ->withoutSiteAdministrator()
            ->withAdministratorOf($project)
            ->build();

        $middleware = new RejectNonProjectMembersAdministratorMiddleware(
            ProvideCurrentUserStub::buildWithUser($user),
            EnsureUserCanManageProjectMembersStub::canManageMembers(),
        );

        $handler = RequestHandlerInterfaceStub::buildSelf();

        $request = (new NullServerRequest())->withAttribute(\Project::class, $project);

        $middleware->process($request, $handler);

        self::assertTrue($handler->hasBeenCalled());
        self::assertSame($user, $handler->getCapturedRequest()->getAttribute(\PFUser::class));
    }

    public function testProcessThrowsWhenUserIsNotProjectAdminNorHasDelegatedPermissions(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $user    = UserTestBuilder::aUser()
            ->withoutSiteAdministrator()
            ->build();

        $middleware = new RejectNonProjectMembersAdministratorMiddleware(
            ProvideCurrentUserStub::buildWithUser($user),
            EnsureUserCanManageProjectMembersStub::cannotManageMembers(),
        );

        $handler = RequestHandlerInterfaceStub::buildSelf();

        $request = (new NullServerRequest())->withAttribute(\Project::class, $project);

        $this->expectException(ForbiddenException::class);
        $middleware->process($request, $handler);

        self::assertFalse($handler->hasBeenCalled());
    }
}
