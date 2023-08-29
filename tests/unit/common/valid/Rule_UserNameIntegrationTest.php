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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Rule_UserNameIntegrationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    public function testOk(): void
    {
        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('getUserByUserName')->andReturns(null);

        $pm = \Mockery::spy(\ProjectManager::class);
        $pm->shouldReceive('getProjectByUnixName')->andReturns(null);

        $sm = \Mockery::spy(\SystemEventManager::class);
        $sm->shouldReceive('isUserNameAvailable')->andReturns(true);

        $r = \Mockery::mock(\Rule_UserName::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $r->shouldReceive('_getUserManager')->andReturns($um);
        $r->shouldReceive('_getProjectManager')->andReturns($pm);
        $r->shouldReceive('_getSystemEventManager')->andReturns($sm);

        self::assertTrue($r->isValid("user"));
        self::assertTrue($r->isValid("user_name"));
        self::assertTrue($r->isValid("user-name"));
    }

    public function testUserAlreadyExist(): void
    {
        $u  = \Mockery::spy(\PFUser::class);
        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('getUserByUsername')->with('user')->andReturns($u);

        $pm = \Mockery::spy(\ProjectManager::class);
        $pm->shouldReceive('getProjectByUnixName')->andReturns(null);

        $backend = \Mockery::spy(\Backend::class);

        $sm = \Mockery::spy(\SystemEventManager::class);
        $sm->shouldReceive('isUserNameAvailable')->andReturns(true);

        $r = \Mockery::mock(\Rule_UserName::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $r->shouldReceive('_getUserManager')->andReturns($um);
        $r->shouldReceive('_getProjectManager')->andReturns($pm);
        $r->shouldReceive('_getBackend')->andReturns($backend);
        $r->shouldReceive('_getSystemEventManager')->andReturns($sm);

        self::assertFalse($r->isValid("user"));
    }

    public function testProjectAlreadyExist(): void
    {
        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('getUserByUserName')->andReturns(null);

        $p  = \Mockery::spy(\Project::class);
        $pm = \Mockery::spy(\ProjectManager::class);
        $pm->shouldReceive('getProjectByUnixName')->with('user')->andReturns($p);

        $sm = \Mockery::spy(\SystemEventManager::class);
        $sm->shouldReceive('isUserNameAvailable')->andReturns(true);

        $r = \Mockery::mock(\Rule_UserName::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $r->shouldReceive('_getUserManager')->andReturns($um);
        $r->shouldReceive('_getProjectManager')->andReturns($pm);
        $r->shouldReceive('_getSystemEventManager')->andReturns($sm);

        self::assertFalse($r->isValid("user"));
    }

    public function testSpaceInName(): void
    {
        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('getUserByUserName')->andReturns(null);

        $pm = \Mockery::spy(\ProjectManager::class);
        $pm->shouldReceive('getProjectByUnixName')->andReturns(null);

        $sm = \Mockery::spy(\SystemEventManager::class);
        $sm->shouldReceive('isUserNameAvailable')->andReturns(true);

        $r = \Mockery::mock(\Rule_UserName::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $r->shouldReceive('_getUserManager')->andReturns($um);
        $r->shouldReceive('_getProjectManager')->andReturns($pm);
        $r->shouldReceive('_getSystemEventManager')->andReturns($sm);

        self::assertFalse($r->isValid("user name"));
    }
}
