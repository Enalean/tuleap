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

use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RouterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testProcessRequest(): void
    {
        $login_controller          = $this->createMock(\Tuleap\OpenIDConnectClient\Login\Controller::class);
        $account_linker_controller = $this->createStub(\Tuleap\OpenIDConnectClient\AccountLinker\Controller::class);
        $request                   = HTTPRequestBuilder::get()->build();

        $login_controller->expects(self::atLeastOnce())->method('login');

        $router = new Router($login_controller, $account_linker_controller);
        $router->process($request, LayoutBuilder::build(), []);
    }
}
