<?php
/**
 * Copyright (c) Enalean, 2021-Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>
 */

declare(strict_types=1);

namespace Tuleap\LDAP;

use Account_TimezonesCollection;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class WelcomeUpdateControllerTest extends TestCase
{
    private WelcomeUpdateController $controller;

    protected function setUp(): void
    {
        $this->controller = new WelcomeUpdateController(
            $this->createStub(\UserManager::class),
            $this->createStub(User\UserDao::class),
            new Account_TimezonesCollection()
        );
    }

    public function testUpdateIsRejectedWhenTimezoneIsNotProvided(): void
    {
        $request = new \HTTPRequest();
        $request->setCurrentUser(UserTestBuilder::anActiveUser()->build());
        $request->set('pv', '2');

        $layout = $this->createStub(\Layout::class);
        $layout->method('addFeedback');
        $layout->method('pv_header');
        $layout->method('pv_footer');

        $this->expectOutputRegex('/must supply a timezone/');
        $this->controller->process($request, $layout, []);
    }
}
