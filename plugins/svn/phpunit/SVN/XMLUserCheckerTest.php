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
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;

final class XMLUserCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    public function testItReturnsTrueIfCurrentUserIsHTTPUser(): void
    {
        ForgeConfig::set('sys_http_user', posix_getpwuid(posix_geteuid())['name']);

        $checker = new XMLUserChecker();
        $this->assertTrue($checker->currentUserIsHTTPUser());
    }

    public function testItReturnsFalseIfCurrentUserIsNotHTTPUser(): void
    {
        ForgeConfig::set('sys_http_user', 'whatever');

        $checker = new XMLUserChecker();
        $this->assertFalse($checker->currentUserIsHTTPUser());
    }
}
