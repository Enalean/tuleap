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
final class UserHelperTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Tuleap\GlobalLanguageMock;

    public function testGetDisplayName(): void
    {
        $user_helper = $this->getMockBuilder(\UserHelper::class)
            ->onlyMethods(['_getCurrentUserUsernameDisplayPreference'])
            ->getMock();

        $user_helper->method('_getCurrentUserUsernameDisplayPreference')->willReturnOnConsecutiveCalls(1, 2, 3, 4, 666);

        $user_helper->__construct();
        self::assertEquals("user_name (realname)", $user_helper->getDisplayName("user_name", "realname"));

        $user_helper->__construct();
        self::assertEquals("user_name", $user_helper->getDisplayName("user_name", "realname"));

        $user_helper->__construct();
        self::assertEquals("realname", $user_helper->getDisplayName("user_name", "realname"));

        $user_helper->__construct();
        self::assertEquals("realname (user_name)", $user_helper->getDisplayName("user_name", "realname"));

        $user_helper->__construct();
        self::assertEquals("realname (user_name)", $user_helper->getDisplayName("user_name", "realname"));
    }

    public function testGetDisplayNameFromUser(): void
    {
        $user = \Tuleap\Test\Builders\UserTestBuilder::aUser()->withUserName('user_name')->withRealName('realname')->build();

        $user_helper = $this->getMockBuilder(\UserHelper::class)
            ->onlyMethods(['_getCurrentUserUsernameDisplayPreference'])
            ->getMock();

        $user_helper->method('_getCurrentUserUsernameDisplayPreference')->willReturn(1);

        $user_helper->__construct();
        self::assertEquals("user_name (realname)", $user_helper->getDisplayNameFromUser($user));
        self::assertNull($user_helper->getDisplayNameFromUser(null));
    }

    public function testGetDisplayNameFromUserId(): void
    {
        $user = \Tuleap\Test\Builders\UserTestBuilder::aUser()->withUserName('user_name')->withRealName('realname')->build();

        $user_manager = $this->createMock(UserManager::class);
        $user_manager->method('isUserLoadedById')->with(123)->willReturn(true);
        $user_manager->method('getUserById')->with(123)->willReturn($user);

        $user_helper = $this->getMockBuilder(\UserHelper::class)
            ->onlyMethods(['_getCurrentUserUsernameDisplayPreference', '_getUserManager'])
            ->getMock();

        $user_helper->method('_getCurrentUserUsernameDisplayPreference')->willReturn(1);
        $user_helper->method('_getUserManager')->willReturn($user_manager);

        $user_helper->__construct();
        self::assertEquals("user_name (realname)", $user_helper->getDisplayNameFromUserId(123));
    }

    public function testGetDisplayNameFromUserName(): void
    {
        $user = \Tuleap\Test\Builders\UserTestBuilder::aUser()->withUserName('user_name')->withRealName('realname')->build();

        $user_manager = $this->createMock(UserManager::class);
        $user_manager->method('isUserLoadedByUserName')->with('user_name')->willReturn(true);
        $user_manager->method('getUserByUserName')->with('user_name')->willReturn($user);

        $user_helper = $this->getMockBuilder(\UserHelper::class)
            ->onlyMethods(['_getCurrentUserUsernameDisplayPreference', '_getUserManager', '_isUserNameNone'])
            ->getMock();

        $user_helper->method('_getCurrentUserUsernameDisplayPreference')->willReturn(1);
        $user_helper->method('_getUserManager')->willReturn($user_manager);
        $user_helper->method('_isUserNameNone')->willReturn(false);

        $user_helper->__construct();
        self::assertEquals("user_name (realname)", $user_helper->getDisplayNameFromUserName('user_name'));
    }

    public function testGetDisplayNameForNone(): void
    {
        $user = \Tuleap\Test\Builders\UserTestBuilder::aUser()->withId(100)->withUserName('None')->withRealName('0')->build();

        $user_manager = $this->createMock(UserManager::class);
        $user_manager->method('isUserLoadedById')->with(100)->willReturn(true);
        $user_manager->method('getUserById')->with(100)->willReturn($user);

        $user_helper = $this->getMockBuilder(\UserHelper::class)
            ->onlyMethods(['_getCurrentUserUsernameDisplayPreference', '_getUserManager', '_isUserNameNone'])
            ->getMock();

        $user_helper->method('_getCurrentUserUsernameDisplayPreference')->willReturnOnConsecutiveCalls(4, 1, 2, 3);
        $user_helper->method('_getUserManager')->willReturn($user_manager);
        $user_helper->method('_isUserNameNone')->willReturnCallback(
            function (string $user_name): bool {
                return ($user_name === 'None' || $user_name === 'Aucun');
            }
        );

        $user_helper->__construct();
        self::assertEquals("None", $user_helper->getDisplayNameFromUser($user));

        $user_helper->__construct();
        self::assertEquals("None", $user_helper->getDisplayNameFromUser($user));

        $user_helper->__construct();
        self::assertEquals("None", $user_helper->getDisplayNameFromUser($user));

        $user_helper->__construct();
        self::assertEquals("None", $user_helper->getDisplayNameFromUser($user));
        self::assertEquals("None", $user_helper->getDisplayNameFromUserId(100));
        self::assertEquals("None", $user_helper->getDisplayNameFromUserName("None"));
        self::assertEquals("Aucun", $user_helper->getDisplayNameFromUserName("Aucun"));
    }

    public function testInternalCachingById(): void
    {
        $dao = $this->createMock(\UserDao::class);
        $dao->method('searchByUserId')->willReturn(['user_name' => 'user_name', 'realname' => 'realname', 'user_id' => 123]);
        $dao->expects(self::never())->method('searchByUserName');

        $user_manager = $this->createMock(\UserManager::class);
        $user_manager->method('isUserLoadedById')->with(123)->willReturn(false);
        $user_manager->method('isUserLoadedByUserName')->with('user_name')->willReturn(false);
        $user_manager->expects(self::never())->method('getUserById');
        $user_manager->expects(self::never())->method('getUserByUserName');

        $user_helper = $this->getMockBuilder(\UserHelper::class)
            ->onlyMethods(['_getCurrentUserUsernameDisplayPreference', '_getUserManager', '_isUserNameNone', '_getUserDao'])
            ->getMock();

        $user_helper->method('_getCurrentUserUsernameDisplayPreference')->willReturn(1);
        $user_helper->method('_getUserManager')->willReturn($user_manager);
        $user_helper->method('_isUserNameNone')->willReturn(false);
        $user_helper->method('_getUserDao')->willReturn($dao);

        $user_helper->__construct();
        self::assertEquals("user_name (realname)", $user_helper->getDisplayNameFromUserId(123));
        self::assertEquals("user_name (realname)", $user_helper->getDisplayNameFromUserName('user_name'));
    }

    public function testInternalCachingByUserName(): void
    {
        $dao = $this->createMock(\UserDao::class);
        $dao->method('searchByUserName')->willReturn(['user_name' => 'user_name', 'realname' => 'realname', 'user_id' => 123]);
        $dao->expects(self::never())->method('searchByUserId');

        $user_manager = $this->createMock(\UserManager::class);
        $user_manager->method('isUserLoadedById')->with(123)->willReturn(false);
        $user_manager->method('isUserLoadedByUserName')->with('user_name')->willReturn(false);
        $user_manager->expects(self::never())->method('getUserById');
        $user_manager->expects(self::never())->method('getUserByUserName');

        $user_helper = $this->getMockBuilder(\UserHelper::class)
            ->onlyMethods(['_getCurrentUserUsernameDisplayPreference', '_getUserManager', '_isUserNameNone', '_getUserDao'])
            ->getMock();

        $user_helper->method('_getUserManager')->willReturn($user_manager);
        $user_helper->method('_isUserNameNone')->willReturn(false);
        $user_helper->method('_getCurrentUserUsernameDisplayPreference')->willReturn(1);
        $user_helper->method('_getUserDao')->willReturn($dao);

        $user_helper->__construct();
        self::assertEquals("user_name (realname)", $user_helper->getDisplayNameFromUserName('user_name'));
        self::assertEquals("user_name (realname)", $user_helper->getDisplayNameFromUserId(123));
    }

    public function testItCachesUnknownNames(): void
    {
        $name = "L'équipe de développement de PhpWiki";

        $dao = $this->createMock(\UserDao::class);
        $dao->method('searchByUserName')->with($name)->willReturn(null);

        $user_manager = $this->createMock(\UserManager::class);
        $user_manager->method('isUserLoadedByUserName')->with($name)->willReturn(false);

        $user_helper = $this->getMockBuilder(\UserHelper::class)
            ->onlyMethods(['_getCurrentUserUsernameDisplayPreference', '_getUserManager', '_isUserNameNone', '_getUserDao'])
            ->getMock();

        $user_helper->method('_getUserManager')->willReturn($user_manager);
        $user_helper->method('_isUserNameNone')->willReturn(false);
        $user_helper->method('_getCurrentUserUsernameDisplayPreference')->willReturn(1);
        $user_helper->method('_getUserDao')->willReturn($dao);

        $user_helper->__construct();
        self::assertEquals($name, $user_helper->getDisplayNameFromUserName($name));
    }
}
