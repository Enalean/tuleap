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

use Tuleap\GlobalSVNPollution;

/**
 * Test for project delete system event
 */
//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
final class SystemEvent_UGROUP_MODIFY_Test extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalSVNPollution;

    /**
     * ProjectUGroup modify Users fail
     *
     * @return Void
     */
    public function testUgroupModifyProcessUgroupModifyFail(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = $this->getMockBuilder(\SystemEvent_UGROUP_MODIFY::class)
            ->setConstructorArgs(
                [
                    '1',
                    SystemEvent::TYPE_UGROUP_MODIFY,
                    SystemEvent::OWNER_ROOT,
                    '1',
                    SystemEvent::PRIORITY_HIGH,
                    SystemEvent::STATUS_RUNNING,
                    $now,
                    $now,
                    $now,
                    '',
                ]
            )
            ->onlyMethods([
                'getParametersAsArray',
                'processUgroupBinding',
                'getProject',
                'getBackend',
                'done',
                'error',
            ])
            ->getMock();

        $evt->method('getParametersAsArray')->willReturn([1, 2]);

        $evt->method('processUgroupBinding')->willReturn(false);

        $project = $this->createMock(\Project::class);
        $project->method('usesSVN')->willReturn(true);

        $backendSVN = $this->createMock(\BackendSVN::class);
        $backendSVN->expects(self::never())->method('updateSVNAccess');

        $evt->expects(self::never())->method('getProject')->with('1')->willReturn($project);
        $evt->expects(self::never())->method('getBackend')->with('SVN')->willReturn($backendSVN);
        $evt->expects(self::never())->method('done');
        $evt->expects(self::once())->method('error')->with("Could not process binding to this user group (2)");

        // Launch the event
        self::assertFalse($evt->process());
    }

    public function testUgroupModifyProcessSuccess(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = $this->getMockBuilder(\SystemEvent_UGROUP_MODIFY::class)
            ->setConstructorArgs(
                [
                    '1',
                    SystemEvent::TYPE_UGROUP_MODIFY,
                    SystemEvent::OWNER_ROOT,
                    '1',
                    SystemEvent::PRIORITY_HIGH,
                    SystemEvent::STATUS_RUNNING,
                    $now,
                    $now,
                    $now,
                    '',
                ]
            )
            ->onlyMethods([
                'getParametersAsArray',
                'processUgroupBinding',
                'getProject',
                'getBackend',
                'done',
                'error',
            ])
            ->getMock();

        $evt->method('getParametersAsArray')->willReturn([1, 2]);

        $evt->method('processUgroupBinding')->willReturn(true);

        $project = $this->createMock(\Project::class);
        $project->method('usesSVN')->willReturn(true);
        $evt->method('getProject')->with(1)->willReturn($project);

        $scheduler = $this->createMock(\Tuleap\SVNCore\Event\UpdateProjectAccessFilesScheduler::class);
        $scheduler->expects(self::once())->method('scheduleUpdateOfProjectAccessFiles');
        $evt->injectDependencies($scheduler);

        $evt->expects(self::once())->method('done');
        $evt->expects(self::never())->method('error');

        // Launch the event
        self::assertTrue($evt->process());
    }

    public function testUpdateSVNOfBindedUgroups(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = $this->getMockBuilder(\SystemEvent_UGROUP_MODIFY::class)
            ->setConstructorArgs(
                [
                    '1',
                    SystemEvent::TYPE_UGROUP_MODIFY,
                    SystemEvent::OWNER_ROOT,
                    '1',
                    SystemEvent::PRIORITY_HIGH,
                    SystemEvent::STATUS_RUNNING,
                    $now,
                    $now,
                    $now,
                    '',
                ]
            )
            ->onlyMethods([
                'getParametersAsArray',
                'getProject',
                'getBackend',
                'done',
                'error',
                'getUgroupBinding',
            ])
            ->getMock();

        $evt->method('getParametersAsArray')->willReturn([1, 2]);

        $project = $this->createMock(\Project::class);
        $project->method('usesSVN')->willReturn(true);
        $evt->method('getProject')->willReturn($project);

        $ugroupbinding = $this->createMock(\UGroupBinding::class);
        $ugroupbinding->method('updateBindedUGroups')->willReturn(true);
        $ugroupbinding->method('removeAllUGroupsBinding')->willReturn(true);
        $ugroupbinding->method('checkUGroupValidity')->willReturn(true);
        $projects = [
            1 => ['group_id' => 101],
            2 => ['group_id' => 102],
        ];
        $ugroupbinding->method('getUGroupsByBindingSource')->willReturn($projects);
        $evt->method('getUgroupBinding')->willReturn($ugroupbinding);

        $scheduler = $this->createMock(\Tuleap\SVNCore\Event\UpdateProjectAccessFilesScheduler::class);
        $scheduler->expects(self::exactly(3))->method('scheduleUpdateOfProjectAccessFiles');
        $evt->injectDependencies($scheduler);

        $evt->expects(self::once())->method('done');
        $evt->expects(self::never())->method('error');

        // Launch the event
        self::assertTrue($evt->process());
    }
}
