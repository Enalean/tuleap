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

use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

require_once __DIR__ . '/bootstrap.php';

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AdminRouterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    public function testItIsOnlyAccessibleBySuperUser(): void
    {
        $controller = $this->createMock(\Tuleap\OpenIDConnectClient\Administration\Controller::class);
        $controller->method('showAdministration');

        $csrf_token = $this->createMock(\CSRFSynchronizerToken::class);
        $user       = UserTestBuilder::aUser()->withoutSiteAdministrator()->build();
        $request    = HTTPRequestBuilder::get()->withUser($user)->build();

        $response = $this->createMock(\Tuleap\Layout\BaseLayout::class);
        $response->expects(self::once())->method('addFeedback');
        $response->expects(self::once())->method('redirect');

        $router = new AdminRouter($controller, $csrf_token);
        $router->process($request, $response, []);
    }
}
