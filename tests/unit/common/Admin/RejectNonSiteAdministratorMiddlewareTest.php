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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Request\ForbiddenException;

final class RejectNonSiteAdministratorMiddlewareTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var RejectNonSiteAdministratorMiddleware
     */
    private $middleware;

    protected function setUp(): void
    {
        $this->user_manager = \Mockery::mock(\UserManager::class);
        $this->middleware   = new RejectNonSiteAdministratorMiddleware($this->user_manager);
    }

    public function testProcessTheRequestWhenTheUserIsSiteAdministrator(): void
    {
        $handler = \Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('handle')->once()->with(\Mockery::capture($enriched_request));
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isSuperUser')->andReturn(true);
        $this->user_manager->shouldReceive('getCurrentUser')->once()->andReturn($user);


        $this->middleware->process(new NullServerRequest(), $handler);
        self::assertSame($user, $enriched_request->getAttribute(\PFUser::class));
    }

    public function testRequestIsRejectedWhenTheUserIsNotASiteAdministrator(): void
    {
        $handler = \Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('handle')->never();
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isSuperUser')->andReturn(false);
        $this->user_manager->shouldReceive('getCurrentUser')->once()->andReturn($user);

        $this->expectException(ForbiddenException::class);
        $this->middleware->process(new NullServerRequest(), $handler);
    }
}
