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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Rule_ProjectNameIntegrationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testValidNamesAreValid(): void
    {
        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('getUserByUserName')->andReturns(null);

        $pm = \Mockery::spy(\ProjectManager::class);
        $pm->shouldReceive('getProjectByUnixName')->andReturns(null);

        $backend = \Mockery::spy(\Backend::class);
        $backend->shouldReceive('unixUserExists')->andReturns(false);
        $backend->shouldReceive('unixGroupExists')->andReturns(false);

        $sm = \Mockery::spy(\SystemEventManager::class);
        $sm->shouldReceive('isUserNameAvailable')->andReturns(true);
        $sm->shouldReceive('isProjectNameAvailable')->andReturns(true);

        $r = \Mockery::mock(\Rule_ProjectName::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $r->shouldReceive('_getUserManager')->andReturns($um);
        $r->shouldReceive('_getProjectManager')->andReturns($pm);
        $r->shouldReceive('_getBackend')->andReturns($backend);
        $r->shouldReceive('_getSystemEventManager')->andReturns($sm);

        $r->shouldReceive('isNameAvailable')->with("group-test")->andReturns(true);
        $r->shouldReceive('isNameAvailable')->with("test")->andReturns(true);
        $r->shouldReceive('isNameAvailable')->with("test1")->andReturns(true);

        $this->assertTrue($r->isValid("group-test"));
        $this->assertTrue($r->isValid("test"));
        $this->assertTrue($r->isValid("test1"));
    }
}
