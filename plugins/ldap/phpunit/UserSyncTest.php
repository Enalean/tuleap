<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2004-2010. All rights reserved
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

namespace Tuleap\LDAP;

use LDAP_UserSync;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class UserSyncTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testNoUpdateWhenNoDifference(): void
    {
        $user = \Mockery::mock(\PFUser::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $user->shouldReceive('getRealName')->andReturns('toto');
        $user->shouldReceive('getEmail')->andReturns('toto');
        $user->shouldReceive('setRealName')->never();
        $user->shouldReceive('setEmail')->never();

        $lr = \Mockery::mock(\LDAPResult::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $lr->shouldReceive('getCommonName')->andReturns('toto');
        $lr->shouldReceive('getEmail')->andReturns('toto');

        $sync = new LDAP_UserSync();
        $sync->sync($user, $lr);
    }

    public function testUserUpdateEmailIfLdapDoesntMatch(): void
    {
        $user = \Mockery::mock(\PFUser::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $user->shouldReceive('getRealName')->andReturns('toto');
        $user->shouldReceive('getEmail')->andReturns('toto');
        $user->shouldReceive('setRealName')->never();
        $user->shouldReceive('setEmail')->with('foobar')->once();

        $lr = \Mockery::mock(\LDAPResult::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $lr->shouldReceive('getCommonName')->andReturns('toto');
        $lr->shouldReceive('getEmail')->andReturns('foobar');

        $sync = new LDAP_UserSync();
        $sync->sync($user, $lr);
    }


    public function testUserUpdateRealnameIfLdapDoesntMatch(): void
    {
        $user = \Mockery::mock(\PFUser::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $user->shouldReceive('getRealName')->andReturns('toto');
        $user->shouldReceive('getEmail')->andReturns('toto');
        $user->shouldReceive('setRealName')->with('foobar')->once();
        $user->shouldReceive('setEmail')->never();

        $lr = \Mockery::mock(\LDAPResult::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $lr->shouldReceive('getCommonName')->andReturns('foobar');
        $lr->shouldReceive('getEmail')->andReturns('toto');

        $sync = new LDAP_UserSync();
        $sync->sync($user, $lr);
    }

    public function testChangeUserStatusWithDedicatedCode(): void
    {
        $user = \Mockery::mock(\PFUser::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $user->shouldReceive('getRealName')->andReturns('toto');
        $user->shouldReceive('getEmail')->andReturns('toto');
        $user->shouldReceive('getStatus')->andReturns(PFUser::STATUS_ACTIVE);
        $user->shouldReceive('setRealName')->never();
        $user->shouldReceive('setEmail')->never();
        $user->shouldReceive('setStatus')->with(PFUser::STATUS_RESTRICTED)->once();

        $lr = \Mockery::mock(\LDAPResult::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $lr->shouldReceive('getCommonName')->andReturns('toto');
        $lr->shouldReceive('getEmail')->andReturns('toto');
        $lr->shouldReceive('get')->with('employeetype')->andReturns('contractor');

        include_once __DIR__ . '/../site-content/en_US/synchronize_user.txt';
        $sync = new \LDAPPluginCustomUserSync();
        $sync->sync($user, $lr);
    }
}
