<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\GlobalSVNPollution;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class SystemEvent_PROJECT_RENAME_Test extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalSVNPollution;

    /**
     * Rename project 142 'TestProj' in 'FooBar'
     */
    public function testRenameOps(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = \Mockery::mock(
            \SystemEvent_PROJECT_RENAME::class,
            [
                '1',
                SystemEvent::TYPE_PROJECT_RENAME,
                SystemEvent::OWNER_ROOT,
                '142' . SystemEvent::PARAMETER_SEPARATOR . 'FooBar',
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

        // The project
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->with(false)->andReturns('TestProj');
        $project->shouldReceive('getUnixName')->with(true)->andReturns('testproj');
        $evt->shouldReceive('getProject')->with('142')->andReturns($project);

        // SVN
        $backendSVN = \Mockery::spy(\BackendSVN::class);
        $backendSVN->shouldReceive('repositoryExists')->andReturns(true);
        $backendSVN->shouldReceive('isNameAvailable')->andReturns(true);
        $backendSVN->shouldReceive('renameSVNRepository')->with($project, 'FooBar')->once()->andReturns(true);
        $backendSVN->shouldReceive('setSVNApacheConfNeedUpdate')->once();
        $evt->shouldReceive('getBackend')->with('SVN')->andReturns($backendSVN);

        // System
        $backendSystem = \Mockery::spy(\BackendSystem::class);
        $evt->shouldReceive('getBackend')->with('System')->andReturns($backendSystem);
        $backendSystem->shouldReceive('renameFileReleasedDirectory')->with($project, 'FooBar')->once()->andReturns(true);
        $evt->shouldReceive('getBackend')->with('System')->andReturns($backendSystem);
        $evt->shouldReceive('getBackend')->with('System')->andReturns($backendSystem);

        //DB
        $evt->shouldReceive('updateDB')->andReturns(true);

        // Event
        $em = \Mockery::mock(EventManager::class);
        $em->shouldReceive('processEvent')->with('SystemEvent_PROJECT_RENAME', ['project' => $project, 'new_name' => 'FooBar']);
        $evt->shouldReceive('getEventManager')->andReturns($em);
        $evt->shouldReceive('addProjectHistory')->with('rename_done', 'TestProj :: FooBar', $project->getId())->once();
        // Expect everything went OK
        $evt->shouldReceive('done')->once();

        // Launch the event
        $this->assertTrue($evt->process());
    }

    public function testRenameSvnRepositoryFailure(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = \Mockery::mock(
            \SystemEvent_PROJECT_RENAME::class,
            [
                '1',
                SystemEvent::TYPE_PROJECT_RENAME,
                SystemEvent::OWNER_ROOT,
                '142' . SystemEvent::PARAMETER_SEPARATOR . 'FooBar',
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

        // The project
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->with(false)->andReturns('TestProj');
        $project->shouldReceive('getUnixName')->with(true)->andReturns('testproj');
        $evt->shouldReceive('getProject')->with('142')->andReturns($project);

        // SVN
        $backendSVN = \Mockery::spy(\BackendSVN::class);
        $backendSVN->shouldReceive('repositoryExists')->andReturns(true);
        $backendSVN->shouldReceive('isNameAvailable')->andReturns(true);
        $backendSVN->shouldReceive('renameSVNRepository')->with($project, 'FooBar')->once();
        $backendSVN->shouldReceive('renameSVNRepository')->with(false)->andReturns([$project, 'FooBar']);
        $backendSVN->shouldReceive('setSVNApacheConfNeedUpdate')->never();
        $evt->shouldReceive('getBackend')->with('SVN')->andReturns($backendSVN);

        $backendSystem = \Mockery::spy(\BackendSystem::class);
        $backendSystem->shouldReceive('renameFileReleasedDirectory')->with($project, 'FooBar')->once()->andReturns(true);

        $evt->shouldReceive('getBackend')->with('System')->andReturns($backendSystem);

        // DB
        $evt->shouldReceive('updateDB')->andReturns(true);

        // Event
        $em = \Mockery::mock(EventManager::class);
        $em->shouldReceive('processEvent')->with('SystemEvent_PROJECT_RENAME', ['project' => $project, 'new_name' => 'FooBar']);
        $evt->shouldReceive('getEventManager')->andReturns($em);

        $evt->shouldReceive('addProjectHistory')->with('rename_with_error', 'TestProj :: FooBar (event n°1)', $project->getId())->once();

        // There is an error, the rename in not "done"
        $evt->shouldReceive('done')->never();

        $this->assertFalse($evt->process());
    }

    public function testRenameSvnRepositoryNotAvailable(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = \Mockery::mock(
            \SystemEvent_PROJECT_RENAME::class,
            [
                '1',
                SystemEvent::TYPE_PROJECT_RENAME,
                SystemEvent::OWNER_ROOT,
                '142' . SystemEvent::PARAMETER_SEPARATOR . 'FooBar',
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

        // The project
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->with(false)->andReturns('TestProj');
        $project->shouldReceive('getUnixName')->with(true)->andReturns('testproj');
        $evt->shouldReceive('getProject')->with('142')->andReturns($project);

        // SVN
        $backendSVN = \Mockery::spy(\BackendSVN::class);
        $backendSVN->shouldReceive('repositoryExists')->andReturns(true);
        $backendSVN->shouldReceive('isNameAvailable')->andReturns(false);
        $backendSVN->shouldReceive('renameSVNRepository')->never();
        $backendSVN->shouldReceive('setSVNApacheConfNeedUpdate')->never();
        $evt->shouldReceive('getBackend')->with('SVN')->andReturns($backendSVN);

        // Project Home
        $backendSystem = \Mockery::spy(\BackendSystem::class);
        $backendSystem->shouldReceive('renameFileReleasedDirectory')->with($project, 'FooBar')->once()->andReturns(true);

        $evt->shouldReceive('getBackend')->with('System')->andReturns($backendSystem);

        // DB
        $evt->shouldReceive('updateDB')->andReturns(true);

        // Event
        $em = \Mockery::mock(EventManager::class);
        $em->shouldReceive('processEvent')->with('SystemEvent_PROJECT_RENAME', ['project' => $project, 'new_name' => 'FooBar']);
        $evt->shouldReceive('getEventManager')->andReturns($em);

        $evt->shouldReceive('addProjectHistory')->with('rename_with_error', 'TestProj :: FooBar (event n°1)', $project->getId())->once();

        // There is an error, the rename in not "done"
        $evt->shouldReceive('done')->never();

        $this->assertFalse($evt->process());
    }

    public function testRenameFRSRepositoryFailure(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = \Mockery::mock(
            \SystemEvent_PROJECT_RENAME::class,
            [
                '1',
                SystemEvent::TYPE_PROJECT_RENAME,
                SystemEvent::OWNER_ROOT,
                '142' . SystemEvent::PARAMETER_SEPARATOR . 'FooBar',
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

        // The project
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->with(false)->andReturns('TestProj');
        $project->shouldReceive('getUnixName')->with(true)->andReturns('testproj');
        $evt->shouldReceive('getProject')->with('142')->andReturns($project);

        // SVN
        $backendSVN = \Mockery::spy(\BackendSVN::class);
        $backendSVN->shouldReceive('repositoryExists')->andReturns(false);
        $evt->shouldReceive('getBackend')->with('SVN')->andReturns($backendSVN);

        // System
        $backendSystem = \Mockery::spy(\BackendSystem::class);
        $backendSystem->shouldReceive('renameFileReleasedDirectory')->with($project, 'FooBar')->once()->andReturns(false);
        $evt->shouldReceive('getBackend')->with('System')->andReturns($backendSystem);

        $evt->shouldReceive('getBackend')->with('System')->andReturns($backendSystem);

        // DB
        $evt->shouldReceive('updateDB')->andReturns(true);

        // Event
        $em = \Mockery::mock(EventManager::class);
        $em->shouldReceive('processEvent')->with('SystemEvent_PROJECT_RENAME', ['project' => $project, 'new_name' => 'FooBar']);
        $evt->shouldReceive('getEventManager')->andReturns($em);

        $evt->shouldReceive('addProjectHistory')->with('rename_with_error', 'TestProj :: FooBar (event n°1)', $project->getId())->once();

        // There is an error, the rename in not "done"
        $evt->shouldReceive('done')->never();

        $this->assertFalse($evt->process());

        // Check errors
        $this->assertEquals(SystemEvent::STATUS_ERROR, $evt->getStatus());
        $this->assertMatchesRegularExpression('/Could not rename FRS repository/i', $evt->getLog());
    }

    public function testRenameDBUpdateFailure(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = \Mockery::mock(
            \SystemEvent_PROJECT_RENAME::class,
            [
                '1',
                SystemEvent::TYPE_PROJECT_RENAME,
                SystemEvent::OWNER_ROOT,
                '142' . SystemEvent::PARAMETER_SEPARATOR . 'FooBar',
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

        // The project
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->with(false)->andReturns('TestProj');
        $project->shouldReceive('getUnixName')->with(true)->andReturns('testproj');
        $evt->shouldReceive('getProject')->with('142')->andReturns($project);

        // SVN
        $backendSVN = \Mockery::spy(\BackendSVN::class);
        $backendSVN->shouldReceive('repositoryExists')->andReturns(false);
        $evt->shouldReceive('getBackend')->with('SVN')->andReturns($backendSVN);

        // System
        $backendSystem = \Mockery::spy(\BackendSystem::class);

        //FRS
        $backendSystem->shouldReceive('renameFileReleasedDirectory')->andReturns(true);

        $evt->shouldReceive('getBackend')->with('System')->andReturns($backendSystem);

        // DB
        $evt->shouldReceive('updateDB')->andReturns(false);

        // Event
        $em = \Mockery::mock(EventManager::class);
        $em->shouldReceive('processEvent')->with('SystemEvent_PROJECT_RENAME', ['project' => $project, 'new_name' => 'FooBar']);
        $evt->shouldReceive('getEventManager')->andReturns($em);

        $evt->shouldReceive('addProjectHistory')->with('rename_with_error', 'TestProj :: FooBar (event n°1)', $project->getId())->once();

        // There is an error, the rename in not "done"
        $evt->shouldReceive('done')->never();

        $this->assertFalse($evt->process());
    }

    public function testMultipleErrorLogs(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = \Mockery::mock(
            \SystemEvent_PROJECT_RENAME::class,
            [
                '1',
                SystemEvent::TYPE_PROJECT_RENAME,
                SystemEvent::OWNER_ROOT,
                '142' . SystemEvent::PARAMETER_SEPARATOR . 'FooBar',
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

        // The project
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->with(false)->andReturns('TestProj');
        $project->shouldReceive('getUnixName')->with(true)->andReturns('testproj');
        $evt->shouldReceive('getProject')->with('142')->andReturns($project);

        // Error in SVN
        $backendSVN = \Mockery::spy(\BackendSVN::class);
        $backendSVN->shouldReceive('repositoryExists')->andReturns(true);
        $backendSVN->shouldReceive('isNameAvailable')->andReturns(false);
        $evt->shouldReceive('getBackend')->with('SVN')->andReturns($backendSVN);

        // System
        $backendSystem = \Mockery::spy(\BackendSystem::class);

        //FRS
        $backendSystem->shouldReceive('renameFileReleasedDirectory')->andReturns(true);

        $evt->shouldReceive('getBackend')->with('System')->andReturns($backendSystem);

        // DB
        $evt->shouldReceive('updateDB')->andReturns(true);

        // Event
        $evt->shouldReceive('getEventManager')->andReturns(\Mockery::spy(EventManager::class));
        $evt->shouldReceive('addProjectHistory')->once();

        $evt->process();

        $this->assertEquals(SystemEvent::STATUS_ERROR, $evt->getStatus());
        $this->assertMatchesRegularExpression('/.*SVN repository.*not available/', $evt->getLog());
    }
}
