<?php
/**
 * Copyright (c) Enalean, 2012—present. All Rights Reserved.
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
use org\bovigo\vfs\vfsStream;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalSVNPollution;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class SystemEvent_USER_RENAME_Test extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;
    use GlobalSVNPollution;

    protected function setUp(): void
    {
        parent::setUp();
        ForgeConfig::set('homedir_prefix', vfsStream::setup()->url());
    }

    /**
     * Rename user 142 'mickey' in 'tazmani'
     */
    public function testRenameOps(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = \Mockery::mock(
            \SystemEvent_USER_RENAME::class,
            [
                '1',
                SystemEvent::TYPE_USER_RENAME,
                SystemEvent::OWNER_ROOT,
                '142' . SystemEvent::PARAMETER_SEPARATOR . 'tazmani',
                SystemEvent::PRIORITY_HIGH,
                SystemEvent::STATUS_RUNNING,
                $now,
                $now,
                $now,
                '',
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // The user
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getUserName')->andReturns('mickey');
        $evt->shouldReceive('getUser')->with('142')->andReturns($user);

        // System
        $backendSystem = \Mockery::spy(\BackendSystem::class);
        $backendSystem->shouldReceive('userHomeExists')->andReturns(true);
        $backendSystem->shouldReceive('isUserNameAvailable')->andReturns(true);
        $backendSystem->shouldReceive('renameUserHomeDirectory')->with($user, 'tazmani')->once()->andReturns(true);
        $evt->shouldReceive('getBackend')->with('System')->andReturns($backendSystem);

        // DB
        $evt->shouldReceive('updateDB')->andReturns(true);

        // SVN
        $backendSVN = \Mockery::spy(\BackendSVN::class);
        $backendSVN->shouldReceive('updateSVNAccessForGivenMember')->andReturns(true);
        $evt->shouldReceive('getBackend')->with('SVN')->andReturns($backendSVN);

        // Expect everything went OK
        $evt->shouldReceive('done')->once();

        // Launch the event
        $this->assertTrue($evt->process());
    }

    public function testRenameUserRepositoryFailure(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = \Mockery::mock(
            \SystemEvent_USER_RENAME::class,
            [
                '1',
                SystemEvent::TYPE_USER_RENAME,
                SystemEvent::OWNER_ROOT,
                '142' . SystemEvent::PARAMETER_SEPARATOR . 'tazmani',
                SystemEvent::PRIORITY_HIGH,
                SystemEvent::STATUS_RUNNING,
                $now,
                $now,
                $now,
                '',
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // The user
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getUserName')->andReturns('mickey');
        $evt->shouldReceive('getUser')->with('142')->andReturns($user);

        // System
        $backendSystem = \Mockery::spy(\BackendSystem::class);
        $backendSystem->shouldReceive('userHomeExists')->andReturns(true);
        $backendSystem->shouldReceive('isUserNameAvailable')->andReturns(true);
        $backendSystem->shouldReceive('renameUserHomeDirectory')->with($user, 'tazmani')->once()->andReturns(false);
        $evt->shouldReceive('getBackend')->with('System')->andReturns($backendSystem);

        // DB
        $evt->shouldReceive('updateDB')->andReturns(true);

        // SVN
        $backendSVN = \Mockery::spy(\BackendSVN::class);
        $backendSVN->shouldReceive('updateSVNAccessForGivenMember')->andReturns(true);
        $evt->shouldReceive('getBackend')->with('SVN')->andReturns($backendSVN);

        // There is an error, the rename is not "done"
        $evt->shouldReceive('done')->never();

        $this->assertFalse($evt->process());

        // Check errors
        $this->assertEquals(SystemEvent::STATUS_ERROR, $evt->getStatus());
        $this->assertMatchesRegularExpression('/Could not rename user home/i', $evt->getLog());
    }

    public function testUpdateSVNAccessFailure(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = \Mockery::mock(
            \SystemEvent_USER_RENAME::class,
            [
                '1',
                SystemEvent::TYPE_USER_RENAME,
                SystemEvent::OWNER_ROOT,
                '142' . SystemEvent::PARAMETER_SEPARATOR . 'tazmani',
                SystemEvent::PRIORITY_HIGH,
                SystemEvent::STATUS_RUNNING,
                $now,
                $now,
                $now,
                '',
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // The user
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getUserName')->andReturns('mickey');
        $evt->shouldReceive('getUser')->with('142')->andReturns($user);

        // System
        $backendSystem = \Mockery::spy(\BackendSystem::class);
        $backendSystem->shouldReceive('userHomeExists')->andReturns(true);
        $backendSystem->shouldReceive('isUserNameAvailable')->andReturns(true);
        $backendSystem->shouldReceive('renameUserHomeDirectory')->with($user, 'tazmani')->once()->andReturns(true);
        $evt->shouldReceive('getBackend')->with('System')->andReturns($backendSystem);

        // DB
        $evt->shouldReceive('updateDB')->andReturns(true);

        // SVN
        $backendSVN = \Mockery::spy(\BackendSVN::class);
        $backendSVN->shouldReceive('updateSVNAccessForGivenMember')->andReturns(false);
        $evt->shouldReceive('getBackend')->with('SVN')->andReturns($backendSVN);

        // There is an error, the rename is not "done"
        $evt->shouldReceive('done')->never();

        $this->assertFalse($evt->process());

        // Check errors
        $this->assertEquals(SystemEvent::STATUS_ERROR, $evt->getStatus());
        $this->assertMatchesRegularExpression('/Could not update SVN access files for the user/i', $evt->getLog());
    }
}
