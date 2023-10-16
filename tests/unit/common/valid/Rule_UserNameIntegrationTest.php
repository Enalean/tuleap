<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
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

use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
final class Rule_UserNameIntegrationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;
    use GlobalLanguageMock;

    public function testOk(): void
    {
        $um = $this->createMock(\UserManager::class);
        $um->method('getUserByUserName')->willReturn(null);

        $pm = $this->createMock(\ProjectManager::class);
        $pm->method('getProjectByUnixName')->willReturn(null);

        $sm = $this->createMock(\SystemEventManager::class);
        $sm->method('isUserNameAvailable')->willReturn(true);

        $r = $this->createPartialMock(
            \Rule_UserName::class,
            [
                '_getUserManager',
                '_getProjectManager',
                '_getSystemEventManager',
            ]
        );
        $r->method('_getUserManager')->willReturn($um);
        $r->method('_getProjectManager')->willReturn($pm);
        $r->method('_getSystemEventManager')->willReturn($sm);

        self::assertTrue($r->isValid("user"));
        self::assertTrue($r->isValid("user_name"));
        self::assertTrue($r->isValid("user-name"));
    }

    public function testUserAlreadyExist(): void
    {
        $u  = \Tuleap\Test\Builders\UserTestBuilder::aUser()->build();
        $um = $this->createMock(\UserManager::class);
        $um->method('getUserByUsername')->with('user')->willReturn($u);

        $pm = $this->createMock(\ProjectManager::class);
        $pm->method('getProjectByUnixName')->willReturn(null);

        $sm = $this->createMock(\SystemEventManager::class);
        $sm->method('isUserNameAvailable')->willReturn(true);

        $r = $this->createPartialMock(
            \Rule_UserName::class,
            [
                '_getUserManager',
                '_getProjectManager',
                '_getSystemEventManager',
            ]
        );
        $r->method('_getUserManager')->willReturn($um);
        $r->method('_getProjectManager')->willReturn($pm);
        $r->method('_getSystemEventManager')->willReturn($sm);

        self::assertFalse($r->isValid("user"));
    }

    public function testProjectAlreadyExist(): void
    {
        $um = $this->createMock(\UserManager::class);
        $um->method('getUserByUserName')->willReturn(null);

        $p  = $this->createMock(\Project::class);
        $pm = $this->createMock(\ProjectManager::class);
        $pm->method('getProjectByUnixName')->with('user')->willReturn($p);

        $sm = $this->createMock(\SystemEventManager::class);
        $sm->method('isUserNameAvailable')->willReturn(true);

        $r = $this->createPartialMock(
            \Rule_UserName::class,
            [
                '_getUserManager',
                '_getProjectManager',
                '_getSystemEventManager',
            ]
        );
        $r->method('_getUserManager')->willReturn($um);
        $r->method('_getProjectManager')->willReturn($pm);
        $r->method('_getSystemEventManager')->willReturn($sm);

        self::assertFalse($r->isValid("user"));
    }

    public function testSpaceInName(): void
    {
        $um = $this->createMock(\UserManager::class);
        $um->method('getUserByUserName')->willReturn(null);

        $pm = $this->createMock(\ProjectManager::class);
        $pm->method('getProjectByUnixName')->willReturn(null);

        $sm = $this->createMock(\SystemEventManager::class);
        $sm->method('isUserNameAvailable')->willReturn(true);

        $r = $this->createPartialMock(
            \Rule_UserName::class,
            [
                '_getUserManager',
                '_getProjectManager',
                '_getSystemEventManager',
            ]
        );
        $r->method('_getUserManager')->willReturn($um);
        $r->method('_getProjectManager')->willReturn($pm);
        $r->method('_getSystemEventManager')->willReturn($sm);

        self::assertFalse($r->isValid("user name"));
    }
}
