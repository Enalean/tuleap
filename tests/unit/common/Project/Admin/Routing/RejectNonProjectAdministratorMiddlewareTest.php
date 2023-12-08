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

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\Builders\UserTestBuilder;

final class RejectNonProjectAdministratorMiddlewareTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private RejectNonProjectAdministratorMiddleware $middleware;
    private \UserManager&MockObject $user_manager;
    private ProjectAdministratorChecker&MockObject $checker;

    protected function setUp(): void
    {
        $this->user_manager = $this->createMock(\UserManager::class);
        $this->checker      = $this->createMock(ProjectAdministratorChecker::class);
        $this->middleware   = new RejectNonProjectAdministratorMiddleware($this->user_manager, $this->checker);
    }

    public function testProcessThrowsWhenProjectIsNotAnAttributeOfRequest(): void
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $request = new NullServerRequest();

        self::expectException(\LogicException::class);
        $this->middleware->process($request, $handler);
    }

    public function testProcess(): void
    {
        $handler          = $this->createMock(RequestHandlerInterface::class);
        $enriched_request = null;
        $handler->expects(self::once())->method('handle')
            ->with(self::callback(function ($argument) use (&$enriched_request) {
                $enriched_request = $argument;

                return true;
            }));
        $user = UserTestBuilder::aUser()->build();
        $this->user_manager->expects(self::once())->method('getCurrentUser')->willReturn($user);

        $project = new \Project(['group_id' => 102]);
        $request = (new NullServerRequest())->withAttribute(\Project::class, $project);

        $this->checker
            ->expects(self::once())
            ->method('checkUserIsProjectAdministrator')
            ->with($user, $project);

        $this->middleware->process($request, $handler);
        self::assertSame($user, $enriched_request->getAttribute(\PFUser::class));
    }
}
