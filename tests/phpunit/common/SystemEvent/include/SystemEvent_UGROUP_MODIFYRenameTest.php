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

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class SystemEvent_UGROUP_MODIFYRenameTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalSVNPollution;

    private $system_event;
    private $project;

    /**
     * @var EventManager|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $event_manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->event_manager = \Mockery::mock(\EventManager::class);
        $project_manager = \Mockery::spy(\ProjectManager::class);

        EventManager::setInstance($this->event_manager);
        ProjectManager::setInstance($project_manager);

        $event_params = array(
            '1',
            SystemEvent::TYPE_UGROUP_MODIFY,
            SystemEvent::OWNER_ROOT,
            '101::104::Amleth::Hamlet',
            SystemEvent::PRIORITY_HIGH,
            SystemEvent::STATUS_RUNNING,
            $_SERVER['REQUEST_TIME'],
            $_SERVER['REQUEST_TIME'],
            $_SERVER['REQUEST_TIME'],
            ''
        );

        $this->system_event = \Mockery::mock(\SystemEvent_UGROUP_MODIFY::class, $event_params)->makePartial()->shouldAllowMockingProtectedMethods();

        $ugroup_binding = \Mockery::spy(\UGroupBinding::class);
        $ugroup_binding->shouldReceive('updateBindedUGroups')->andReturns(true);
        $ugroup_binding->shouldReceive('removeAllUGroupsBinding')->andReturns(true);
        $ugroup_binding->shouldReceive('getUGroupsByBindingSource')->andReturns(array());

        $this->system_event->shouldReceive('getUgroupBinding')->andReturns($ugroup_binding);

        $this->project = \Mockery::spy(\Project::class);
        $project_manager->shouldReceive('getProject')->with('101')->andReturns($this->project);
    }

    protected function tearDown(): void
    {
        EventManager::clearInstance();
        ProjectManager::clearInstance();

        parent::tearDown();
    }

    public function testItWarnsOthersThatUGroupHasBeenModified(): void
    {
        $this->event_manager->shouldReceive('processEvent')
            ->with(
                Event::UGROUP_RENAME,
                array(
                    'project'         => $this->project,
                    'new_ugroup_name' => 'Amleth',
                    'old_ugroup_name' => 'Hamlet'
                )
            )
            ->once();

        $this->system_event->process();
    }

    public function testSVNCoreAccessFilesAreUpdated(): void
    {
        $this->project->shouldReceive('usesSVN')->andReturnTrue();

        $backend_svn = Mockery::mock(\BackendCVS::class);
        $this->system_event->shouldReceive('getBackend')->with('SVN')->andReturn($backend_svn);

        $this->event_manager->shouldReceive('processEvent');

        $backend_svn->shouldReceive('updateSVNAccess')->once();

        $this->system_event->process();
    }
}
