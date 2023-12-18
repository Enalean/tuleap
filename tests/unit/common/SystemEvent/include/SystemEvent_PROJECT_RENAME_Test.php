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


//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
final class SystemEvent_PROJECT_RENAME_Test extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * Rename project 142 'TestProj' in 'FooBar'
     */
    public function testRenameOps(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = $this->getMockBuilder(\SystemEvent_PROJECT_RENAME::class)
            ->setConstructorArgs(
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
            ->onlyMethods([
                'getProject',
                'getBackend',
                'updateDB',
                'getEventManager',
                'addProjectHistory',
                'done',
            ])
            ->getMock();

        // The project
        $project = \Tuleap\Test\Builders\ProjectTestBuilder::aProject()->withUnixName('TestProj')->withId(142)->build();
        $evt->method('getProject')->with('142')->willReturn($project);

        // SVN
        $backendSVN = $this->createMock(\BackendSVN::class);
        $backendSVN->method('repositoryExists')->willReturn(true);
        $backendSVN->method('isNameAvailable')->willReturn(true);
        $backendSVN->expects(self::once())->method('renameSVNRepository')->with($project, 'FooBar')->willReturn(true);
        $backendSVN->expects(self::once())->method('setSVNApacheConfNeedUpdate');

        // System
        $backendSystem = $this->createMock(\BackendSystem::class);
        $backendSystem->expects(self::once())->method('renameFileReleasedDirectory')->with($project, 'FooBar')->willReturn(true);

        $evt->method('getBackend')->willReturnMap([
            ['SVN', $backendSVN],
            ['System', $backendSystem],
        ]);

        //DB
        $evt->method('updateDB')->willReturn(true);

        // Event
        $em = $this->createMock(EventManager::class);
        $em->method('processEvent')->with('SystemEvent_PROJECT_RENAME', ['project' => $project, 'new_name' => 'FooBar']);
        $evt->method('getEventManager')->willReturn($em);
        $evt->expects(self::once())->method('addProjectHistory')->with('rename_done', 'TestProj :: FooBar', $project->getId());
        // Expect everything went OK
        $evt->expects(self::once())->method('done');

        // Launch the event
        self::assertTrue($evt->process());
    }

    public function testRenameSvnRepositoryFailure(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = $this->getMockBuilder(\SystemEvent_PROJECT_RENAME::class)
            ->setConstructorArgs(
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
            ->onlyMethods([
                'getProject',
                'getBackend',
                'updateDB',
                'getEventManager',
                'addProjectHistory',
                'done',
            ])
            ->getMock();

        // The project
        $project = \Tuleap\Test\Builders\ProjectTestBuilder::aProject()->withUnixName('TestProj')->withId(142)->build();
        $evt->method('getProject')->with('142')->willReturn($project);

        // SVN
        $backendSVN = $this->createMock(\BackendSVN::class);
        $backendSVN->method('repositoryExists')->willReturn(true);
        $backendSVN->method('isNameAvailable')->willReturn(true);
        $backendSVN->expects(self::once())->method('renameSVNRepository')->with($project, 'FooBar')->willReturn(false);
        $backendSVN->expects(self::never())->method('setSVNApacheConfNeedUpdate');

        $backendSystem = $this->createMock(\BackendSystem::class);
        $backendSystem->expects(self::once())->method('renameFileReleasedDirectory')->with($project, 'FooBar')->willReturn(true);

        $evt->method('getBackend')->willReturnMap([
            ['SVN', $backendSVN],
            ['System', $backendSystem],
        ]);

        // DB
        $evt->method('updateDB')->willReturn(true);

        // Event
        $em = $this->createMock(EventManager::class);
        $em->method('processEvent')->with('SystemEvent_PROJECT_RENAME', ['project' => $project, 'new_name' => 'FooBar']);
        $evt->method('getEventManager')->willReturn($em);

        $evt->expects(self::once())->method('addProjectHistory')->with('rename_with_error', 'TestProj :: FooBar (event n°1)', $project->getId());

        // There is an error, the rename in not "done"
        $evt->expects(self::never())->method('done');

        self::assertFalse($evt->process());
    }

    public function testRenameSvnRepositoryNotAvailable(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = $this->getMockBuilder(\SystemEvent_PROJECT_RENAME::class)
            ->setConstructorArgs(
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
            ->onlyMethods([
                'getProject',
                'getBackend',
                'updateDB',
                'getEventManager',
                'addProjectHistory',
                'done',
            ])
            ->getMock();

        // The project
        $project = \Tuleap\Test\Builders\ProjectTestBuilder::aProject()->withUnixName('TestProj')->withId(142)->build();
        $evt->method('getProject')->with('142')->willReturn($project);

        // SVN
        $backendSVN = $this->createMock(\BackendSVN::class);
        $backendSVN->method('repositoryExists')->willReturn(true);
        $backendSVN->method('isNameAvailable')->willReturn(false);
        $backendSVN->expects(self::never())->method('renameSVNRepository');
        $backendSVN->expects(self::never())->method('setSVNApacheConfNeedUpdate');

        // Project Home
        $backendSystem = $this->createMock(\BackendSystem::class);
        $backendSystem->expects(self::once())->method('renameFileReleasedDirectory')->with($project, 'FooBar')->willReturn(true);

        $evt->method('getBackend')->willReturnMap([
            ['SVN', $backendSVN],
            ['System', $backendSystem],
        ]);

        // DB
        $evt->method('updateDB')->willReturn(true);

        // Event
        $em = $this->createMock(EventManager::class);
        $em->method('processEvent')->with('SystemEvent_PROJECT_RENAME', ['project' => $project, 'new_name' => 'FooBar']);
        $evt->method('getEventManager')->willReturn($em);

        $evt->expects(self::once())->method('addProjectHistory')->with('rename_with_error', 'TestProj :: FooBar (event n°1)', $project->getId());

        // There is an error, the rename in not "done"
        $evt->expects(self::never())->method('done');

        self::assertFalse($evt->process());
    }

    public function testRenameFRSRepositoryFailure(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = $this->getMockBuilder(\SystemEvent_PROJECT_RENAME::class)
            ->setConstructorArgs(
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
            ->onlyMethods([
                'getProject',
                'getBackend',
                'updateDB',
                'getEventManager',
                'addProjectHistory',
                'done',
            ])
            ->getMock();

        // The project
        $project = \Tuleap\Test\Builders\ProjectTestBuilder::aProject()->withUnixName('TestProj')->withId(142)->build();
        $evt->method('getProject')->with('142')->willReturn($project);

        // SVN
        $backendSVN = $this->createMock(\BackendSVN::class);
        $backendSVN->method('repositoryExists')->willReturn(false);

        // System
        $backendSystem = $this->createMock(\BackendSystem::class);
        $backendSystem->expects(self::once())->method('renameFileReleasedDirectory')->with($project, 'FooBar')->willReturn(false);

        $evt->method('getBackend')->willReturnMap([
            ['SVN', $backendSVN],
            ['System', $backendSystem],
        ]);

        // DB
        $evt->method('updateDB')->willReturn(true);

        // Event
        $em = $this->createMock(EventManager::class);
        $em->method('processEvent')->with('SystemEvent_PROJECT_RENAME', ['project' => $project, 'new_name' => 'FooBar']);
        $evt->method('getEventManager')->willReturn($em);

        $evt->expects(self::once())->method('addProjectHistory')->with('rename_with_error', 'TestProj :: FooBar (event n°1)', $project->getId());

        // There is an error, the rename in not "done"
        $evt->expects(self::never())->method('done');

        self::assertFalse($evt->process());

        // Check errors
        self::assertEquals(SystemEvent::STATUS_ERROR, $evt->getStatus());
        self::assertMatchesRegularExpression('/Could not rename FRS repository/i', $evt->getLog());
    }

    public function testRenameDBUpdateFailure(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = $this->getMockBuilder(\SystemEvent_PROJECT_RENAME::class)
            ->setConstructorArgs(
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
            ->onlyMethods([
                'getProject',
                'getBackend',
                'updateDB',
                'getEventManager',
                'addProjectHistory',
                'done',
            ])
            ->getMock();

        // The project
        $project = \Tuleap\Test\Builders\ProjectTestBuilder::aProject()->withUnixName('TestProj')->withId(142)->build();
        $evt->method('getProject')->with('142')->willReturn($project);

        // SVN
        $backendSVN = $this->createMock(\BackendSVN::class);
        $backendSVN->method('repositoryExists')->willReturn(false);

        // System
        $backendSystem = $this->createMock(\BackendSystem::class);

        //FRS
        $backendSystem->method('renameFileReleasedDirectory')->willReturn(true);

        $evt->method('getBackend')->willReturnMap([
            ['SVN', $backendSVN],
            ['System', $backendSystem],
        ]);

        // DB
        $evt->method('updateDB')->willReturn(false);

        // Event
        $em = $this->createMock(EventManager::class);
        $em->method('processEvent')->with('SystemEvent_PROJECT_RENAME', ['project' => $project, 'new_name' => 'FooBar']);
        $evt->method('getEventManager')->willReturn($em);

        $evt->expects(self::once())->method('addProjectHistory')->with('rename_with_error', 'TestProj :: FooBar (event n°1)', $project->getId());

        // There is an error, the rename in not "done"
        $evt->expects(self::never())->method('done');

        self::assertFalse($evt->process());
    }

    public function testMultipleErrorLogs(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = $this->getMockBuilder(\SystemEvent_PROJECT_RENAME::class)
            ->setConstructorArgs(
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
            ->onlyMethods([
                'getProject',
                'getBackend',
                'updateDB',
                'getEventManager',
                'addProjectHistory',
                'done',
            ])
            ->getMock();

        // The project
        $project = \Tuleap\Test\Builders\ProjectTestBuilder::aProject()->withUnixName('TestProj')->withId(142)->build();
        $evt->method('getProject')->with('142')->willReturn($project);

        // Error in SVN
        $backendSVN = $this->createMock(\BackendSVN::class);
        $backendSVN->method('repositoryExists')->willReturn(true);
        $backendSVN->method('isNameAvailable')->willReturn(false);

        // System
        $backendSystem = $this->createMock(\BackendSystem::class);

        //FRS
        $backendSystem->method('renameFileReleasedDirectory')->willReturn(true);

        $evt->method('getBackend')->willReturnMap([
            ['SVN', $backendSVN],
            ['System', $backendSystem],
        ]);

        // DB
        $evt->method('updateDB')->willReturn(true);

        // Event
        $event_manager = $this->createMock(EventManager::class);
        $event_manager->method('processEvent');
        $evt->method('getEventManager')->willReturn($event_manager);
        $evt->expects(self::once())->method('addProjectHistory');

        $evt->process();

        self::assertEquals(SystemEvent::STATUS_ERROR, $evt->getStatus());
        self::assertMatchesRegularExpression('/.*SVN repository.*not available/', $evt->getLog());
    }
}
