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

namespace Tuleap\Admin;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\UserTestBuilder;

final class RejectNonSiteAdministratorMiddlewareTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private MockObject&\UserManager $user_manager;
    private RejectNonSiteAdministratorMiddleware $middleware;

    protected function setUp(): void
    {
        $this->user_manager = $this->createMock(\UserManager::class);
        $this->middleware   = new RejectNonSiteAdministratorMiddleware($this->user_manager);
    }

    public function testProcessTheRequestWhenTheUserIsSiteAdministrator(): void
    {
        $user = UserTestBuilder::aUser()->withSiteAdministrator()->build();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->will(self::returnCallback(function (ServerRequestInterface $enriched_request) use ($user): ResponseInterface {
            self::assertSame($user, $enriched_request->getAttribute(\PFUser::class));

            return $this->createMock(ResponseInterface::class);
        }));
        $this->user_manager->expects(self::once())->method('getCurrentUser')->willReturn($user);

        $this->middleware->process(new NullServerRequest(), $handler);
    }

    public function testRequestIsRejectedWhenTheUserIsNotASiteAdministrator(): void
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');
        $user = UserTestBuilder::aUser()->withoutSiteAdministrator()->build();
        $this->user_manager->expects(self::once())->method('getCurrentUser')->willReturn($user);

        $this->expectException(ForbiddenException::class);
        $this->middleware->process(new NullServerRequest(), $handler);
    }
}
