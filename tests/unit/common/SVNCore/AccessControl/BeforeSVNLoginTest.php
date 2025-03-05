<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\SVNCore\AccessControl;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BeforeSVNLoginTest extends TestCase
{
    public function testNoUserIsSetByDefault(): void
    {
        $expected_login_name = 'login_name';
        $expected_password   = new ConcealedString('pass');
        $expected_project    = ProjectTestBuilder::aProject()->build();
        $event               = new BeforeSVNLogin($expected_login_name, $expected_password, $expected_project);

        self::assertNull($event->getUser());
        self::assertSame($expected_login_name, $event->getLoginName());
        self::assertSame($expected_password, $event->getPassword());
        self::assertSame($expected_project, $event->project);
    }

    public function testSetUser(): void
    {
        $event = new BeforeSVNLogin('login_name', new ConcealedString('pass'), ProjectTestBuilder::aProject()->build());

        $expected_user = UserTestBuilder::buildWithDefaults();

        $event->setUser($expected_user);

        self::assertSame($expected_user, $event->getUser());
    }
}
