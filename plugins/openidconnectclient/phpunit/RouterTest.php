<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class RouterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItOnlyAcceptsHTTPS(): void
    {
        $login_controller          = \Mockery::spy(\Tuleap\OpenIDConnectClient\Login\Controller::class);
        $account_linker_controller = \Mockery::spy(\Tuleap\OpenIDConnectClient\AccountLinker\Controller::class);
        $request                   = \Mockery::spy(\HTTPRequest::class);
        $request->shouldReceive('isSecure')->andReturns(false);

        $response = Mockery::mock(\Tuleap\Layout\BaseLayout::class);
        $response->shouldReceive('addFeedback')->once();
        $response->shouldReceive('redirect')->once();

        $router = new Router($login_controller, $account_linker_controller);
        $router->process($request, $response, []);
    }
}
