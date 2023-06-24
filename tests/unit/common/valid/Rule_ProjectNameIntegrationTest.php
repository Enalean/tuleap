<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
final class Rule_ProjectNameIntegrationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testValidNamesAreValid(): void
    {
        $um = $this->createMock(\UserManager::class);
        $um->method('getUserByUserName')->willReturn(null);

        $pm = $this->createMock(\ProjectManager::class);
        $pm->method('getProjectByUnixName')->willReturn(null);

        $backend = $this->createMock(\Backend::class);
        $backend->method('unixUserExists')->willReturn(false);
        $backend->method('unixGroupExists')->willReturn(false);

        $sm = $this->createMock(\SystemEventManager::class);
        $sm->method('isUserNameAvailable')->willReturn(true);
        $sm->method('isProjectNameAvailable')->willReturn(true);

        $r = $this->getMockBuilder(\Rule_ProjectName::class)
            ->onlyMethods(['_getUserManager', '_getProjectManager', '_getBackend', '_getSystemEventManager', 'isNameAvailable'])
            ->getMock();

        $r->method('_getUserManager')->willReturn($um);
        $r->method('_getProjectManager')->willReturn($pm);
        $r->method('_getBackend')->willReturn($backend);
        $r->method('_getSystemEventManager')->willReturn($sm);

        $r->method('isNameAvailable')->willReturnMap([
            ['group-test', true],
            ['test', true],
            ['test1', true],
        ]);

        self::assertTrue($r->isValid("group-test"));
        self::assertTrue($r->isValid("test"));
        self::assertTrue($r->isValid("test1"));
    }
}
