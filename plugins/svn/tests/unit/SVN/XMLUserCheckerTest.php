<?php
/**
 * Copyright (c) Enalean SAS, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\SVN;

use ForgeConfig;
use Tuleap\ForgeConfigSandbox;

final class XMLUserCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    public function testItReturnsTrueIfCurrentUserIsHTTPUser(): void
    {
        ForgeConfig::set('sys_http_user', posix_getpwuid(posix_geteuid())['name']);

        $checker = new XMLUserChecker();
        self::assertTrue($checker->currentUserIsHTTPUser());
    }

    public function testItReturnsFalseIfCurrentUserIsNotHTTPUser(): void
    {
        ForgeConfig::set('sys_http_user', 'whatever');

        $checker = new XMLUserChecker();
        self::assertFalse($checker->currentUserIsHTTPUser());
    }
}
