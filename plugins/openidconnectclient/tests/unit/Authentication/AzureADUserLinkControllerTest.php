<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Authentication;

use Mockery;
use PHPUnit\Framework\TestCase;
use Tuleap\Layout\BaseLayout;
use Tuleap\OpenIDConnectClient\Login\Controller;

final class AzureADUserLinkControllerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItOnlyAcceptsHTTPS(): void
    {
        $login_controller = \Mockery::mock(Controller::class);
        $request          = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('isSecure')->andReturns(false);
        $request->shouldReceive('getTime')->andReturns(false);
        $request->shouldReceive('get')->withArgs(['return_to']);

        $response = Mockery::mock(BaseLayout::class);

        $response->shouldReceive('addFeedback')
                 ->withArgs(['error', 'The OpenID Connect plugin can only be used if the platform is accessible with HTTPS'])
                 ->once();

        $response->shouldReceive('redirect')->once();

        $login_controller->shouldReceive('login')->once();

        $router = new AzureADUserLinkController($login_controller);

        $router->process($request, $response, []);
    }
}
