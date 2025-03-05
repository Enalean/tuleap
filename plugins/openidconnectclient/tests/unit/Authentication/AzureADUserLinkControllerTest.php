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

use Tuleap\OpenIDConnectClient\Login\Controller;
use Tuleap\Test\Builders\LayoutBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AzureADUserLinkControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testProcessRequest(): void
    {
        $login_controller = $this->createMock(Controller::class);
        $request          = $this->createMock(\HTTPRequest::class);
        $request->method('getTime')->willReturn(false);
        $request->method('get')->with('return_to');

        $login_controller->expects(self::once())->method('login');

        $router = new AzureADUserLinkController($login_controller);

        $router->process($request, LayoutBuilder::build(), []);
    }
}
