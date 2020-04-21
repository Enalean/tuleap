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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalSVNPollution;

/**
 * Test for project delete system event
 */
//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class SystemEvent_UGROUP_MODIFY_Test extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalSVNPollution;

    /**
     * ProjectUGroup modify Users fail
     *
     * @return Void
     */
    public function testUgroupModifyProcessUgroupModifyFail(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = \Mockery::mock(
            \SystemEvent_UGROUP_MODIFY::class,
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
                ''
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $evt->shouldReceive('getParametersAsArray')->andReturns(array(1, 2));

        $evt->shouldReceive('processUgroupBinding')->andReturns(false);

        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('usesSVN')->andReturns(true);

        $backendSVN = \Mockery::spy(\BackendSVN::class);
        $backendSVN->shouldReceive('updateSVNAccess')->never();

        $evt->shouldReceive('getProject')->with('1')->never()->andReturns($project);
        $evt->shouldReceive('getBackend')->with('SVN')->never()->andReturns($backendSVN);
        $evt->shouldReceive('done')->never();
        $evt->shouldReceive('error')->with("Could not process binding to this user group (2)")->once();

        // Launch the event
        $this->assertFalse($evt->process());
    }

    public function testUgroupModifyProcessSuccess(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = \Mockery::mock(
            \SystemEvent_UGROUP_MODIFY::class,
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
                ''
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $evt->shouldReceive('getParametersAsArray')->andReturns(array(1, 2));

        $evt->shouldReceive('processUgroupBinding')->andReturns(true);

        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('usesSVN')->andReturns(true);
        $evt->shouldReceive('getProject')->with(1)->andReturns($project);

        $scheduler = Mockery::mock(\Tuleap\svn\Event\UpdateProjectAccessFilesScheduler::class);
        $scheduler->shouldReceive('scheduleUpdateOfProjectAccessFiles')->once();
        $evt->injectDependencies($scheduler);

        $evt->shouldReceive('done')->once();
        $evt->shouldReceive('error')->never();

        // Launch the event
        $this->assertTrue($evt->process());
    }

    public function testUpdateSVNOfBindedUgroups(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = \Mockery::mock(
            \SystemEvent_UGROUP_MODIFY::class,
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
                ''
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $evt->shouldReceive('getParametersAsArray')->andReturns(array(1, 2));

        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('usesSVN')->andReturns(true);
        $evt->shouldReceive('getProject')->andReturns($project);

        $ugroupbinding = \Mockery::spy(\UGroupBinding::class);
        $ugroupbinding->shouldReceive('updateBindedUGroups')->andReturns(true);
        $ugroupbinding->shouldReceive('removeAllUGroupsBinding')->andReturns(true);
        $projects     = array(
            1 => array('group_id' => 101),
            2 => array('group_id' => 102)
        );
        $ugroupbinding->shouldReceive('getUGroupsByBindingSource')->andReturns($projects);
        $evt->shouldReceive('getUgroupBinding')->andReturns($ugroupbinding);

        $scheduler = Mockery::mock(\Tuleap\svn\Event\UpdateProjectAccessFilesScheduler::class);
        $scheduler->shouldReceive('scheduleUpdateOfProjectAccessFiles')->times(3);
        $evt->injectDependencies($scheduler);

        $evt->shouldReceive('done')->once();
        $evt->shouldReceive('error')->never();

        // Launch the event
        $this->assertTrue($evt->process());
    }
}
