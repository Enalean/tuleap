<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\User\Admin;

use ForgeAccess;
use ForgeConfig;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;

final class UserStatusCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private UserStatusChecker $status_checker;


    protected function setUp(): void
    {
        parent::setUp();

        $this->status_checker = new UserStatusChecker();
    }

    public function testItReturnsTrueWhenPlatformAllowRestricted(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $user = UserTestBuilder::anActiveUser()->build();

        self::assertTrue($this->status_checker->isRestrictedStatusAllowedForUser($user));
    }

    public function testItReturnsTrueWhenUserIsRestricted(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        $user = UserTestBuilder::aRestrictedUser()->build();

        self::assertTrue($this->status_checker->isRestrictedStatusAllowedForUser($user));
    }

    public function testItReturnsFalseWhenUserIsSuperUser(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        $user = UserTestBuilder::anActiveUser()->withSiteAdministrator()->build();

        self::assertFalse($this->status_checker->isRestrictedStatusAllowedForUser($user));
    }

    public function testItReturnsTrueWhenUserIsSuperUserAndHeIsRestricted(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $user = UserTestBuilder::aRestrictedUser()->withSiteAdministrator()->build();

        self::assertTrue($this->status_checker->isRestrictedStatusAllowedForUser($user));
    }

    public function testItReturnsFalseWhenUserIsNotRestrictedAndPlatformDontAllowRestricted(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        $user = UserTestBuilder::anActiveUser()->build();

        self::assertFalse($this->status_checker->isRestrictedStatusAllowedForUser($user));
    }
}
