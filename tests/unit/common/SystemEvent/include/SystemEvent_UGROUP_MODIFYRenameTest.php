<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
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
final class SystemEvent_UGROUP_MODIFYRenameTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private $system_event;
    private $project;

    /**
     * @var EventManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $event_manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->event_manager = $this->createMock(\EventManager::class);
        $project_manager     = $this->createMock(\ProjectManager::class);

        EventManager::setInstance($this->event_manager);
        ProjectManager::setInstance($project_manager);

        $event_params = [
            '1',
            SystemEvent::TYPE_UGROUP_MODIFY,
            SystemEvent::OWNER_ROOT,
            '101::104::Amleth::Hamlet',
            SystemEvent::PRIORITY_HIGH,
            SystemEvent::STATUS_RUNNING,
            $_SERVER['REQUEST_TIME'],
            $_SERVER['REQUEST_TIME'],
            $_SERVER['REQUEST_TIME'],
            '',
        ];

        $this->system_event = $this->getMockBuilder(\SystemEvent_UGROUP_MODIFY::class)
            ->setConstructorArgs($event_params)
            ->onlyMethods(['getUgroupBinding', 'getBackend'])
            ->getMock();

        $ugroup_binding = $this->createMock(\UGroupBinding::class);
        $ugroup_binding->method('updateBindedUGroups')->willReturn(true);
        $ugroup_binding->method('removeAllUGroupsBinding')->willReturn(true);
        $ugroup_binding->method('getUGroupsByBindingSource')->willReturn([]);
        $ugroup_binding->method('checkUGroupValidity')->willReturn(true);

        $this->system_event->method('getUgroupBinding')->willReturn($ugroup_binding);

        $this->project = $this->createMock(\Project::class);
        $this->project->method('usesSVN')->willReturn(true);
        $this->project->method('getSVNRootPath')->willReturn('/');

        $project_manager->method('getProject')->with('101')->willReturn($this->project);
    }

    protected function tearDown(): void
    {
        EventManager::clearInstance();
        ProjectManager::clearInstance();

        parent::tearDown();
    }

    public function testSVNCoreAccessFilesAreUpdated(): void
    {
        $backend_svn = $this->createMock(\BackendSVN::class);
        $this->system_event->method('getBackend')->with('SVN')->willReturn($backend_svn);

        $this->event_manager->expects(self::once())->method('processEvent')
            ->with(
                Event::UGROUP_RENAME,
                [
                    'project'         => $this->project,
                    'new_ugroup_name' => 'Amleth',
                    'old_ugroup_name' => 'Hamlet',
                ]
            );

        $backend_svn->expects(self::once())->method('updateSVNAccess');

        $this->system_event->process();
    }
}
