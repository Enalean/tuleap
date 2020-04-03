<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

class UserStatusCheckerTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \PFUser
     */
    private $user;

    /**
     * @var UserStatusChecker
     */
    private $status_checker;


    protected function setUp(): void
    {
        parent::setUp();

        $this->status_checker = new UserStatusChecker();
        $this->user           = \Mockery::spy(\PFUser::class);

        ForgeConfig::store();
    }

    public function testItReturnsTrueWhenPlatformAllowRestricted(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $this->user->shouldReceive('isRestricted')->andReturns(false);

        $this->assertTrue($this->status_checker->isRestrictedStatusAllowedForUser($this->user));
    }

    public function testItReturnsTrueWhenUserIsRestricted(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        $this->user->shouldReceive('isRestricted')->andReturns(true);

        $this->assertTrue($this->status_checker->isRestrictedStatusAllowedForUser($this->user));
    }

    public function testItReturnsFalseWhenUserIsSuperUser(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        $this->user->shouldReceive('isSuperUser')->andReturn(true);
        $this->user->shouldReceive('isRestricted')->andReturns(false);

        $this->assertFalse($this->status_checker->isRestrictedStatusAllowedForUser($this->user));
    }

    public function testItReturnsTrueWhenUserIsSuperUserAndHeIsRestricted(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $this->user->shouldReceive('isSuperUser')->andReturn(true);
        $this->user->shouldReceive('isRestricted')->andReturns(true);

        $this->assertTrue($this->status_checker->isRestrictedStatusAllowedForUser($this->user));
    }

    public function testItReturnsFalseWhenUserIsNotRestrictedAndPlatformDontAllowRestricted(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        $this->user->shouldReceive('isRestricted')->andReturns(false);

        $this->assertFalse($this->status_checker->isRestrictedStatusAllowedForUser($this->user));
    }
}
