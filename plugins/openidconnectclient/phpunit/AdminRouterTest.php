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
use Tuleap\GlobalLanguageMock;

require_once __DIR__ . '/bootstrap.php';

class AdminRouterTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    public function testItIsOnlyAccessibleBySuperUser(): void
    {
        $controller = \Mockery::spy(\Tuleap\OpenIDConnectClient\Administration\Controller::class);
        $csrf_token = \Mockery::spy(\CSRFSynchronizerToken::class);
        $user       = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('isSuperUser')->andReturns(false);
        $request    = \Mockery::spy(\HTTPRequest::class);
        $request->shouldReceive('getCurrentUser')->andReturns($user);

        $response = Mockery::mock(\Tuleap\Layout\BaseLayout::class);
        $response->shouldReceive('addFeedback')->once();
        $response->shouldReceive('redirect')->once();

        $router = new AdminRouter($controller, $csrf_token);
        $router->process($request, $response, []);
    }
}
