<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class UserHelperTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalLanguageMock;

    public function testGetDisplayName(): void
    {
        $uh = \Mockery::mock(\UserHelper::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $uh->shouldReceive('_getCurrentUserUsernameDisplayPreference')->once()->andReturns(1);
        $uh->shouldReceive('_getCurrentUserUsernameDisplayPreference')->once()->andReturns(2);
        $uh->shouldReceive('_getCurrentUserUsernameDisplayPreference')->once()->andReturns(3);
        $uh->shouldReceive('_getCurrentUserUsernameDisplayPreference')->once()->andReturns(4);
        $uh->shouldReceive('_getCurrentUserUsernameDisplayPreference')->once()->andReturns(666);

        $uh->__construct();
        $this->assertEquals("user_name (realname)", $uh->getDisplayName("user_name", "realname"));

        $uh->__construct();
        $this->assertEquals("user_name", $uh->getDisplayName("user_name", "realname"));

        $uh->__construct();
        $this->assertEquals("realname", $uh->getDisplayName("user_name", "realname"));

        $uh->__construct();
        $this->assertEquals("realname (user_name)", $uh->getDisplayName("user_name", "realname"));

        $uh->__construct();
        $this->assertEquals("realname (user_name)", $uh->getDisplayName("user_name", "realname"));
    }

    public function testGetDisplayNameFromUser(): void
    {
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getUserName')->andReturns('user_name');
        $user->shouldReceive('getRealName')->andReturns('realname');

        $uh = \Mockery::mock(\UserHelper::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $uh->shouldReceive('_getCurrentUserUsernameDisplayPreference')->andReturns(1);
        $uh->__construct();
        $this->assertEquals("user_name (realname)", $uh->getDisplayNameFromUser($user));
        $this->assertNull($uh->getDisplayNameFromUser(null));
    }

    public function testGetDisplayNameFromUserId(): void
    {
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getUserName')->andReturns('user_name');
        $user->shouldReceive('getRealName')->andReturns('realname');

        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('isUserLoadedById')->with(123)->andReturns(true);
        $um->shouldReceive('getUserById')->with(123)->andReturns($user);

        $uh = \Mockery::mock(\UserHelper::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $uh->shouldReceive('_getCurrentUserUsernameDisplayPreference')->andReturns(1);
        $uh->shouldReceive('_getUserManager')->andReturns($um);
        $uh->__construct();
        $this->assertEquals("user_name (realname)", $uh->getDisplayNameFromUserId(123));
    }

    public function testGetDisplayNameFromUserName(): void
    {
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getUserName')->andReturns('user_name');
        $user->shouldReceive('getRealName')->andReturns('realname');

        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('isUserLoadedByUserName')->with('user_name')->andReturns(true);
        $um->shouldReceive('getUserByUserName')->with('user_name')->andReturns($user);

        $uh = \Mockery::mock(\UserHelper::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $uh->shouldReceive('_isUserNameNone')->andReturns(false);
        $uh->shouldReceive('_getCurrentUserUsernameDisplayPreference')->andReturns(1);
        $uh->shouldReceive('_getUserManager')->andReturns($um);
        $uh->__construct();
        $this->assertEquals("user_name (realname)", $uh->getDisplayNameFromUserName('user_name'));
    }

    public function testGetDisplayNameForNone(): void
    {
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('isNone')->andReturns(true);
        $user->shouldReceive('getUserName')->andReturns('None');
        $user->shouldReceive('getRealName')->andReturns('0');

        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('isUserLoadedById')->with(100)->andReturns(true);
        $um->shouldReceive('getUserById')->with(100)->andReturns($user);

        $uh = \Mockery::mock(\UserHelper::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $uh->shouldReceive('_getUserManager')->andReturns($um);
        $uh->shouldReceive('_isUserNameNone')->with('None')->andReturns(true);
        $uh->shouldReceive('_isUserNameNone')->with('Aucun')->andReturns(true);
        $uh->shouldReceive('_getCurrentUserUsernameDisplayPreference')->andReturns(4, 1, 2, 3)->times(4);

        $uh->__construct();
        $this->assertEquals("None", $uh->getDisplayNameFromUser($user));

        $uh->__construct();
        $this->assertEquals("None", $uh->getDisplayNameFromUser($user));

        $uh->__construct();
        $this->assertEquals("None", $uh->getDisplayNameFromUser($user));

        $uh->__construct();
        $this->assertEquals("None", $uh->getDisplayNameFromUser($user));
        $this->assertEquals("None", $uh->getDisplayNameFromUserId(100));
        $this->assertEquals("None", $uh->getDisplayNameFromUserName("None"));
        $this->assertEquals("Aucun", $uh->getDisplayNameFromUserName("Aucun"));
    }

    public function testInternalCachingById(): void
    {
        $dao = \Mockery::spy(\UserDao::class);
        $dar = TestHelper::arrayToDar(array('user_name' => 'user_name', 'realname' => 'realname', 'user_id' => 123));
        $dao->shouldReceive('searchByUserId')->andReturns($dar);

        $dao->shouldReceive('searchByUserName')->never();

        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('isUserLoadedById')->with(123)->andReturns(false);
        $um->shouldReceive('isUserLoadedByUserName')->with('user_name')->andReturns(false);
        $um->shouldReceive('getUserById')->never();
        $um->shouldReceive('getUserByUserName')->never();

        $uh = \Mockery::mock(\UserHelper::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $uh->shouldReceive('_getUserManager')->andReturns($um);
        $uh->shouldReceive('_isUserNameNone')->andReturns(false);
        $uh->shouldReceive('_getCurrentUserUsernameDisplayPreference')->andReturns(1);
        $uh->shouldReceive('_getUserDao')->andReturns($dao);

        $uh->__construct();
        $this->assertEquals("user_name (realname)", $uh->getDisplayNameFromUserId(123));
        $this->assertEquals("user_name (realname)", $uh->getDisplayNameFromUserName('user_name'));
    }

    public function testInternalCachingByUserName(): void
    {
        $dao = \Mockery::spy(\UserDao::class);
        $dar = TestHelper::arrayToDar(array('user_name' => 'user_name', 'realname' => 'realname', 'user_id' => 123));
        $dao->shouldReceive('searchByUserName')->andReturns($dar);

        $dao->shouldReceive('searchByUserId')->never();

        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('isUserLoadedById')->with(123)->andReturns(false);
        $um->shouldReceive('isUserLoadedByUserName')->with('user_name')->andReturns(false);
        $um->shouldReceive('getUserById')->never();
        $um->shouldReceive('getUserByUserName')->never();

        $uh = \Mockery::mock(\UserHelper::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $uh->shouldReceive('_getUserManager')->andReturns($um);
        $uh->shouldReceive('_isUserNameNone')->andReturns(false);
        $uh->shouldReceive('_getCurrentUserUsernameDisplayPreference')->andReturns(1);
        $uh->shouldReceive('_getUserDao')->andReturns($dao);

        $uh->__construct();
        $this->assertEquals("user_name (realname)", $uh->getDisplayNameFromUserName('user_name'));
        $this->assertEquals("user_name (realname)", $uh->getDisplayNameFromUserId(123));
    }

    public function testItCachesUnknownNames(): void
    {
        $name = "L'équipe de développement de PhpWiki";

        $dao = \Mockery::spy(\UserDao::class);
        $dao->shouldReceive('searchByUserName')->with($name)->andReturns(\TestHelper::emptyDar());

        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('isUserLoadedByUserName')->with($name)->andReturns(false);

        $uh = \Mockery::mock(\UserHelper::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $uh->shouldReceive('_getUserManager')->andReturns($um);
        $uh->shouldReceive('_isUserNameNone')->andReturns(false);
        $uh->shouldReceive('_getCurrentUserUsernameDisplayPreference')->andReturns(1);
        $uh->shouldReceive('_getUserDao')->andReturns($dao);

        $uh->__construct();
        $this->assertEquals($name, $uh->getDisplayNameFromUserName($name));
    }
}
