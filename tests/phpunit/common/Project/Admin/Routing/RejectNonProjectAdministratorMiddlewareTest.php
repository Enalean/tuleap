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

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\Builders\UserTestBuilder;

final class RejectNonProjectAdministratorMiddlewareTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var RejectNonProjectAdministratorMiddleware
     */
    private $middleware;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ProjectAdministratorChecker
     */
    private $checker;

    protected function setUp(): void
    {
        $this->user_manager = M::mock(\UserManager::class);
        $this->checker      = M::mock(ProjectAdministratorChecker::class);
        $this->middleware   = new RejectNonProjectAdministratorMiddleware($this->user_manager, $this->checker);
    }

    public function testProcessThrowsWhenProjectIsNotAnAttributeOfRequest(): void
    {
        $handler = M::mock(RequestHandlerInterface::class);
        $handler->shouldNotReceive('handle');

        $request = new NullServerRequest();

        $this->expectException(\LogicException::class);
        $this->middleware->process($request, $handler);
    }

    public function testProcess(): void
    {
        $handler = M::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('handle')->once()->with(M::capture($enriched_request));
        $user = UserTestBuilder::aUser()->build();
        $this->user_manager->shouldReceive('getCurrentUser')->once()->andReturn($user);

        $project = new \Project(['group_id' => 102]);
        $request = (new NullServerRequest())->withAttribute(\Project::class, $project);

        $this->checker->shouldReceive('checkUserIsProjectAdministrator')
            ->once()
            ->with($user, $project);

        $this->middleware->process($request, $handler);
        $this->assertSame($user, $enriched_request->getAttribute(\PFUser::class));
    }
}
