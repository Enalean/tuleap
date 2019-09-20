<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

Mock::generatePartial(
    'SystemEvent_UGROUP_MODIFY',
    'SystemEvent_UGROUP_MODIFY_TestVersion',
    array('getProject',
                            'getBackend',
                            'done',
                            'processUgroupBinding',
                            'error',
    'getParametersAsArray')
);
Mock::generatePartial(
    'SystemEvent_UGROUP_MODIFY',
    'SystemEvent_UGROUP_MODIFY_TestUGroupVersion',
    array(
        'getProject',
        'getBackend',
        'done',
        'getUgroupBinding',
        'error',
        'getParametersAsArray'
    )
);

Mock::generate('BackendSystem');
Mock::generate('Project');
Mock::generate('BackendSVN');
Mock::generate('UGroupBinding');

/**
 * Test for project delete system event
 */
class SystemEvent_UGROUP_MODIFY_Test extends TuleapTestCase
{

    /**
     * ProjectUGroup modify Users fail
     *
     * @return Void
     */
    public function testUgroupModifyProcessUgroupModifyFail()
    {
        $evt = new SystemEvent_UGROUP_MODIFY_TestVersion();
        $evt->__construct('1', SystemEvent::TYPE_UGROUP_MODIFY, SystemEvent::OWNER_ROOT, '1', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');
        $evt->setReturnValue('getParametersAsArray', array(1, 2));

        $evt->setReturnValue('processUgroupBinding', false);

        $project = new MockProject();
        $project->setReturnValue('usesSVN', true);
        $evt->setReturnValue('getProject', $project, array('1'));

        $backendSVN = new MockBackendSVN();
        $backendSVN->expectNever('updateSVNAccess');
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        $evt->expectNever('getProject');
        $evt->expectNever('getBackend');
        $evt->expectNever('done');
        $evt->expectOnce('error', array("Could not process binding to this user group (2)"));

        // Launch the event
        $this->assertFalse($evt->process());
    }

    /**
     * ProjectUGroup modify SVN fail
     *
     * @return Void
     */
    public function testUgroupModifyProcessSVNFail()
    {
        $evt = new SystemEvent_UGROUP_MODIFY_TestVersion();
        $evt->__construct('1', SystemEvent::TYPE_UGROUP_MODIFY, SystemEvent::OWNER_ROOT, '1', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');
        $evt->setReturnValue('getParametersAsArray', array(1, 2));

        $evt->setReturnValue('processUgroupBinding', true);

        $project = new MockProject();
        $project->setReturnValue('usesSVN', true);
        stub($evt)->getProject(1)->returns($project);

        $backendSVN = new MockBackendSVN();
        $backendSVN->setReturnValue('updateSVNAccess', false);
        $backendSVN->expectOnce('updateSVNAccess');
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        $evt->expectOnce('getProject');
        $evt->expectOnce('getBackend');
        $evt->expectNever('done');
        $evt->expectOnce('error', array("Could not update SVN access file (1)"));

        // Launch the event
        $this->assertFalse($evt->process());
    }

    /**
     * ProjectUGroup modify Success
     *
     * @return Void
     */
    public function testUgroupModifyProcessSuccess()
    {
        $evt = new SystemEvent_UGROUP_MODIFY_TestVersion();
        $evt->__construct('1', SystemEvent::TYPE_UGROUP_MODIFY, SystemEvent::OWNER_ROOT, '1', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');
        $evt->setReturnValue('getParametersAsArray', array(1, 2));

        $evt->setReturnValue('processUgroupBinding', true);

        $project = new MockProject();
        $project->setReturnValue('usesSVN', true);
        stub($evt)->getProject(1)->returns($project);

        $backendSVN = new MockBackendSVN();
        $backendSVN->setReturnValue('updateSVNAccess', true);
        $backendSVN->expectOnce('updateSVNAccess');
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        $evt->expectOnce('getBackend');
        $evt->expectOnce('done');
        $evt->expectNever('error');

        // Launch the event
        $this->assertTrue($evt->process());
    }

    public function testUpdateSVNOfBindedUgroups()
    {
        $evt = new SystemEvent_UGROUP_MODIFY_TestUGroupVersion();
        $evt->__construct('1', SystemEvent::TYPE_UGROUP_MODIFY, SystemEvent::OWNER_ROOT, '1', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');
        $evt->setReturnValue('getParametersAsArray', array(1, 2));

        $project = mock('Project');
        $project->setReturnValue('usesSVN', true);
        $evt->setReturnValue('getProject', $project);

        $ugroupbinding = mock('UgroupBinding');
        $ugroupbinding->setReturnValue('updateBindedUGroups', true);
        $ugroupbinding->setReturnValue('removeAllUGroupsBinding', true);
        $projects     = array(
            1 => array('group_id' => 101),
            2 => array('group_id' => 102)
        );
        $ugroupbinding->setReturnValue('getUGroupsByBindingSource', $projects);
        $evt->setReturnValue('getUgroupBinding', $ugroupbinding);

        $backendSVN = mock('BackendSVN');
        $backendSVN->setReturnValue('updateSVNAccess', true);
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        $backendSVN->expectCallCount('updateSVNAccess', 3);
        $evt->expectOnce('done');
        $evt->expectNever('error');

        // Launch the event
        $this->assertTrue($evt->process());
    }
}

class SystemEvent_UGROUP_MODIFY_RenameTest extends TuleapTestCase
{

    private $system_event;
    private $project;

    public function setUp()
    {
        parent::setUp();

        EventManager::setInstance(\Mockery::mock(\EventManager::class));
        ProjectManager::setInstance(mock('ProjectManager'));

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

        $this->system_event= partial_mock(
            'SystemEvent_UGROUP_MODIFY',
            array(
                'getUgroupBinding'
            ),
            $event_params
        );

        $ugroup_binding = mock('UgroupBinding');
        stub($ugroup_binding)->updateBindedUGroups()->returns(true);
        stub($ugroup_binding)->removeAllUGroupsBinding()->returns(true);
        stub($ugroup_binding)->getUGroupsByBindingSource()->returns(array());

        stub($this->system_event)->getUgroupBinding()->returns($ugroup_binding);

        $this->project = mock('Project');
        stub(ProjectManager::instance())->getProject('101')->returns($this->project);
    }

    public function tearDown()
    {
        EventManager::clearInstance();
        ProjectManager::clearInstance();

        parent::tearDown();
    }

    public function itWarnsOthersThatUGroupHasBeenModified()
    {
        expect(EventManager::instance())->processEvent(
            Event::UGROUP_MODIFY,
            array(
                'project'         => $this->project,
                'new_ugroup_name' => 'Amleth',
                'old_ugroup_name' => 'Hamlet'
            )
        )->once();

        $this->system_event->process();
    }
}
