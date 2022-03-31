<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\User;

use ForgeConfig;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;

final class UnixUserCheckerTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testUnixUserChecker(): void
    {
        ForgeConfig::set('homedir_prefix', '/home/user');
        $this->assertFalse(UnixUserChecker::doesPlatformAllowUnixUserAndIsUserNameValid('666'));
        $this->assertTrue(UnixUserChecker::doesPlatformAllowUnixUserAndIsUserNameValid('jean'));

        ForgeConfig::set('homedir_prefix', '');
        $this->assertFalse(UnixUserChecker::doesPlatformAllowUnixUserAndIsUserNameValid('666'));
        $this->assertFalse(UnixUserChecker::doesPlatformAllowUnixUserAndIsUserNameValid('jean'));
    }
}
