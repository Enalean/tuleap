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
Mock::generatePartial('SystemEvent_PROJECT_RENAME', 'SystemEvent_PROJECT_RENAME_TestVersion', array('getProject', 'getBackend', 'getEventManager', 'done', 'updateDB', 'addProjectHistory'));

Mock::generate('Project');

Mock::generate('BackendSystem');

Mock::generate('BackendSVN');

Mock::generate('BackendCVS');

class SystemEvent_PROJECT_RENAME_Test extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        ForgeConfig::store();
        ForgeConfig::set('grpdir_prefix', '/tmp');
    }

    function tearDown()
    {
        ForgeConfig::restore();
        parent::tearDown();
    }

    /**
     * Rename project 142 'TestProj' in 'FooBar'
     */
    public function testRenameOps()
    {
        $evt = new SystemEvent_PROJECT_RENAME_TestVersion($this);
        $evt->__construct('1', SystemEvent::TYPE_PROJECT_RENAME, SystemEvent::OWNER_ROOT, '142'.SystemEvent::PARAMETER_SEPARATOR.'FooBar', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');

        // The project
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProj', array(false));
        $project->setReturnValue('getUnixName', 'testproj', array(true));
        $evt->setReturnValue('getProject', $project, array('142'));

        // SVN
        $backendSVN = new MockBackendSVN($this);
        $backendSVN->setReturnValue('repositoryExists', true);
        $backendSVN->setReturnValue('isNameAvailable', true);
        $backendSVN->setReturnValue('renameSVNRepository', true);
        $backendSVN->expectOnce('renameSVNRepository', array($project, 'FooBar'));
        $backendSVN->expectOnce('setSVNApacheConfNeedUpdate');
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        // CVS
        $backendCVS = new MockBackendCVS($this);
        $backendCVS->setReturnValue('repositoryExists', true);
        $backendCVS->setReturnValue('isNameAvailable', true);
        $backendCVS->setReturnValue('renameCVSRepository', true);
        $backendCVS->expectOnce('renameCVSRepository', array($project, 'FooBar'));
        $backendCVS->expectOnce('setCVSRootListNeedUpdate');
        $evt->setReturnValue('getBackend', $backendCVS, array('CVS'));

        // System
        $backendSystem = new MockBackendSystem($this);
        $backendSystem->setReturnValue('projectHomeExists', true);
        $backendSystem->setReturnValue('isProjectNameAvailable', true);
        $backendSystem->setReturnValue('renameProjectHomeDirectory', true);
        $backendSystem->expectOnce('renameProjectHomeDirectory', array($project, 'FooBar'));
        $backendSystem->expectOnce('setNeedRefreshGroupCache');
        $evt->setReturnValue('getBackend', $backendSystem, array('System'));

        //FRS
        $backendSystem->setReturnValue('renameFileReleasedDirectory', true);
        $backendSystem->expectOnce('renameFileReleasedDirectory', array($project, 'FooBar'));
        $evt->setReturnValue('getBackend', $backendSystem, array('System'));

        //FTP
        $backendSystem->setReturnValue('renameAnonFtpDirectory', true);
        $backendSystem->expectOnce('renameAnonFtpDirectory', array($project, 'FooBar'));
        $evt->setReturnValue('getBackend', $backendSystem, array('System'));

        //DB
        $evt->setReturnValue('updateDB', true);

        // Event
        $em = \Mockery::mock(EventManager::class);
        $em->shouldReceive('processEvent')->with('SystemEvent_PROJECT_RENAME', array('project' => $project, 'new_name' => 'FooBar'));
        $evt->setReturnValue('getEventManager', $em);
        $evt->expectOnce('addProjectHistory', array('rename_done', 'TestProj :: FooBar', $project->getId()));
        // Expect everything went OK
        $evt->expectOnce('done');

        // Launch the event
        $this->assertTrue($evt->process());
    }

    public function testRenameSvnRepositoryFailure()
    {
        $evt = new SystemEvent_PROJECT_RENAME_TestVersion($this);
        $evt->__construct('1', SystemEvent::TYPE_PROJECT_RENAME, SystemEvent::OWNER_ROOT, '142'.SystemEvent::PARAMETER_SEPARATOR.'FooBar', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');

        // The project
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProj', array(false));
        $project->setReturnValue('getUnixName', 'testproj', array(true));
        $evt->setReturnValue('getProject', $project, array('142'));

        // SVN
        $backendSVN = new MockBackendSVN($this);
        $backendSVN->setReturnValue('repositoryExists', true);
        $backendSVN->setReturnValue('isNameAvailable', true);
        $backendSVN->expectOnce('renameSVNRepository', array($project, 'FooBar'));
        $backendSVN->setReturnValue('renameSVNRepository', array($project, 'FooBar'), array(false));
        $backendSVN->expectNever('setSVNApacheConfNeedUpdate');
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        // CVS no rep, just ensure test
        $backendCVS = new MockBackendCVS($this);
        $backendCVS->setReturnValue('repositoryExists', false);
        $backendCVS->expectOnce('repositoryExists', false);
        $evt->setReturnValue('getBackend', $backendCVS, array('CVS'));

        // Project Home
        $backendSystem = new MockBackendSystem($this);
        $backendSystem->setReturnValue('projectHomeExists', false);
        $backendSystem->expectOnce('projectHomeExists', false);
        $backendSystem->expectNever('setNeedRefreshGroupCache');

        // FRS
        $backendSystem->setReturnValue('renameFileReleasedDirectory', true);
        $backendSystem->expectOnce('renameFileReleasedDirectory', array($project, 'FooBar'));

        // FTP
        $backendSystem->setReturnValue('renameAnonFtpDirectory', true);
        $backendSystem->expectOnce('renameAnonFtpDirectory', array($project, 'FooBar'));

        $evt->setReturnValue('getBackend', $backendSystem, array('System'));

        // DB
        $evt->setReturnValue('updateDB', true);

        // Event
        $em = \Mockery::mock(EventManager::class);
        $em->shouldReceive('processEvent')->with('SystemEvent_PROJECT_RENAME', array('project' => $project, 'new_name' => 'FooBar'));
        $evt->setReturnValue('getEventManager', $em);

        $evt->expectOnce('addProjectHistory', array('rename_with_error', 'TestProj :: FooBar (event n°1)', $project->getId()));

        // There is an error, the rename in not "done"
        $evt->expectNever('done');

        $this->assertFalse($evt->process());
    }

    public function testRenameSvnRepositoryNotAvailable()
    {
        $evt = new SystemEvent_PROJECT_RENAME_TestVersion($this);
        $evt->__construct('1', SystemEvent::TYPE_PROJECT_RENAME, SystemEvent::OWNER_ROOT, '142'.SystemEvent::PARAMETER_SEPARATOR.'FooBar', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');

        // The project
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProj', array(false));
        $project->setReturnValue('getUnixName', 'testproj', array(true));
        $evt->setReturnValue('getProject', $project, array('142'));

        // SVN
        $backendSVN = new MockBackendSVN($this);
        $backendSVN->setReturnValue('repositoryExists', true);
        $backendSVN->setReturnValue('isNameAvailable', false);
        $backendSVN->expectNever('renameSVNRepository', array($project, 'FooBar'));
        $backendSVN->expectNever('setSVNApacheConfNeedUpdate');
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        // CVS no rep, just ensure test
        $backendCVS = new MockBackendCVS($this);
        $backendCVS->setReturnValue('repositoryExists', false);
        $backendCVS->expectOnce('repositoryExists', false);
        $evt->setReturnValue('getBackend', $backendCVS, array('CVS'));

        // Project Home
        $backendSystem = new MockBackendSystem($this);
        $backendSystem->setReturnValue('projectHomeExists', false);
        $backendSystem->expectOnce('projectHomeExists', false);
        $backendSystem->expectNever('setNeedRefreshGroupCache');

        // FRS
        $backendSystem->setReturnValue('renameFileReleasedDirectory', true);
        $backendSystem->expectOnce('renameFileReleasedDirectory', array($project, 'FooBar'));

        // FTP
        $backendSystem->setReturnValue('renameAnonFtpDirectory', true);
        $backendSystem->expectOnce('renameAnonFtpDirectory', array($project, 'FooBar'));

        $evt->setReturnValue('getBackend', $backendSystem, array('System'));

        // DB
        $evt->setReturnValue('updateDB', true);

        // Event
        $em = \Mockery::mock(EventManager::class);
        $em->shouldReceive('processEvent')->with('SystemEvent_PROJECT_RENAME', array('project' => $project, 'new_name' => 'FooBar'));
        $evt->setReturnValue('getEventManager', $em);

        $evt->expectOnce('addProjectHistory', array('rename_with_error', 'TestProj :: FooBar (event n°1)', $project->getId()));

        // There is an error, the rename in not "done"
        $evt->expectNever('done');

        $this->assertFalse($evt->process());
    }

    public function testRenameCVSRepositoryFailure()
    {
        $evt = new SystemEvent_PROJECT_RENAME_TestVersion($this);
        $evt->__construct('1', SystemEvent::TYPE_PROJECT_RENAME, SystemEvent::OWNER_ROOT, '142'.SystemEvent::PARAMETER_SEPARATOR.'FooBar', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');

        // The project
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProj', array(false));
        $project->setReturnValue('getUnixName', 'testproj', array(true));
        $evt->setReturnValue('getProject', $project, array('142'));

        // SVN
        $backendSVN = new MockBackendSVN($this);
        $backendSVN->setReturnValue('repositoryExists', false);
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        // CVS
        $backendCVS = new MockBackendCVS($this);
        $backendCVS->setReturnValue('repositoryExists', true);
        $backendCVS->setReturnValue('isNameAvailable', true);
        $backendCVS->expectOnce('renameCVSRepository', array($project, 'FooBar'));
        $backendCVS->setReturnValue('renameCVSRepository', false);
        $backendCVS->expectNever('setCVSRootListNeedUpdate');
        $evt->setReturnValue('getBackend', $backendCVS, array('CVS'));

        // Project Home
        $backendSystem = new MockBackendSystem($this);
        $backendSystem->setReturnValue('projectHomeExists', false);
        $backendSystem->expectOnce('projectHomeExists', false);
        $backendSystem->expectNever('setNeedRefreshGroupCache');

        // FRS
        $backendSystem->setReturnValue('renameFileReleasedDirectory', true);
        $backendSystem->expectOnce('renameFileReleasedDirectory', array($project, 'FooBar'));

        // FTP
        $backendSystem->setReturnValue('renameAnonFtpDirectory', true);
        $backendSystem->expectOnce('renameAnonFtpDirectory', array($project, 'FooBar'));

        $evt->setReturnValue('getBackend', $backendSystem, array('System'));

        // DB
        $evt->setReturnValue('updateDB', true);

        // Event
        $em = \Mockery::mock(EventManager::class);
        $em->shouldReceive('processEvent')->with('SystemEvent_PROJECT_RENAME', array('project' => $project, 'new_name' => 'FooBar'));
        $evt->setReturnValue('getEventManager', $em);

        $evt->expectOnce('addProjectHistory', array('rename_with_error', 'TestProj :: FooBar (event n°1)', $project->getId()));

        // There is an error, the rename in not "done"
        $evt->expectNever('done');

        $this->assertFalse($evt->process());

        // Check errors
        $this->assertEqual($evt->getStatus(), SystemEvent::STATUS_ERROR);
        $this->assertPattern('/could not rename CVS/i', $evt->getLog());
    }

    public function testRenameHomeRepositoryFailure()
    {
        $evt = new SystemEvent_PROJECT_RENAME_TestVersion($this);
        $evt->__construct('1', SystemEvent::TYPE_PROJECT_RENAME, SystemEvent::OWNER_ROOT, '142'.SystemEvent::PARAMETER_SEPARATOR.'FooBar', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');

        // The project
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProj', array(false));
        $project->setReturnValue('getUnixName', 'testproj', array(true));
        $evt->setReturnValue('getProject', $project, array('142'));

        // SVN
        $backendSVN = new MockBackendSVN($this);
        $backendSVN->setReturnValue('repositoryExists', false);
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        // CVS
        $backendCVS = new MockBackendCVS($this);
        $backendCVS->setReturnValue('repositoryExists', false);
        $evt->setReturnValue('getBackend', $backendCVS, array('CVS'));

        // System
        $backendSystem = new MockBackendSystem($this);
        $backendSystem->setReturnValue('projectHomeExists', true);
        $backendSystem->setReturnValue('isProjectNameAvailable', true);
        $backendSystem->expectOnce('renameProjectHomeDirectory', array($project, 'FooBar'));
        $backendSystem->setReturnValue('renameProjectHomeDirectory', false);
        $backendSystem->expectNever('setNeedRefreshGroupCache');

        // FRS
        $backendSystem->setReturnValue('renameFileReleasedDirectory', true);
        $backendSystem->expectOnce('renameFileReleasedDirectory', array($project, 'FooBar'));

        // FTP
        $backendSystem->setReturnValue('renameAnonFtpDirectory', true);
        $backendSystem->expectOnce('renameAnonFtpDirectory', array($project, 'FooBar'));

        $evt->setReturnValue('getBackend', $backendSystem, array('System'));

        // DB
        $evt->setReturnValue('updateDB', true);

        // Event
        $em = \Mockery::mock(EventManager::class);
        $em->shouldReceive('processEvent')->with('SystemEvent_PROJECT_RENAME', array('project' => $project, 'new_name' => 'FooBar'));
        $evt->setReturnValue('getEventManager', $em);

        $evt->expectOnce('addProjectHistory', array('rename_with_error', 'TestProj :: FooBar (event n°1)', $project->getId()));

        // There is an error, the rename in not "done"
        $evt->expectNever('done');

        $this->assertFalse($evt->process());

        // Check errors
        $this->assertEqual($evt->getStatus(), SystemEvent::STATUS_ERROR);
        $this->assertPattern('/Could not rename project home/i', $evt->getLog());
    }

    public function testRenameFRSRepositoryFailure()
    {
        $evt = new SystemEvent_PROJECT_RENAME_TestVersion($this);
        $evt->__construct('1', SystemEvent::TYPE_PROJECT_RENAME, SystemEvent::OWNER_ROOT, '142'.SystemEvent::PARAMETER_SEPARATOR.'FooBar', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');

        // The project
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProj', array(false));
        $project->setReturnValue('getUnixName', 'testproj', array(true));
        $evt->setReturnValue('getProject', $project, array('142'));

        // SVN
        $backendSVN = new MockBackendSVN($this);
        $backendSVN->setReturnValue('repositoryExists', false);
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        // CVS
        $backendCVS = new MockBackendCVS($this);
        $backendCVS->setReturnValue('repositoryExists', false);
        $evt->setReturnValue('getBackend', $backendCVS, array('CVS'));

        // System
        $backendSystem = new MockBackendSystem($this);
        $backendSystem->setReturnValue('projectHomeExists', false);

        //FRS
        $backendSystem->expectOnce('renameFileReleasedDirectory', array($project, 'FooBar'));
        $backendSystem->setReturnValue('renameFileReleasedDirectory', false);
        $evt->setReturnValue('getBackend', $backendSystem, array('System'));

        // FTP
        $backendSystem->setReturnValue('renameAnonFtpDirectory', true);
        $backendSystem->expectOnce('renameAnonFtpDirectory', array($project, 'FooBar'));

        $evt->setReturnValue('getBackend', $backendSystem, array('System'));

        // DB
        $evt->setReturnValue('updateDB', true);

        // Event
        $em = \Mockery::mock(EventManager::class);
        $em->shouldReceive('processEvent')->with('SystemEvent_PROJECT_RENAME', array('project' => $project, 'new_name' => 'FooBar'));
        $evt->setReturnValue('getEventManager', $em);

        $evt->expectOnce('addProjectHistory', array('rename_with_error', 'TestProj :: FooBar (event n°1)', $project->getId()));

        // There is an error, the rename in not "done"
        $evt->expectNever('done');

        $this->assertFalse($evt->process());

        // Check errors
        $this->assertEqual($evt->getStatus(), SystemEvent::STATUS_ERROR);
        $this->assertPattern('/Could not rename FRS repository/i', $evt->getLog());
    }

    public function testRenameFTPRepositoryFailure()
    {
        $evt = new SystemEvent_PROJECT_RENAME_TestVersion($this);
        $evt->__construct('1', SystemEvent::TYPE_PROJECT_RENAME, SystemEvent::OWNER_ROOT, '142'.SystemEvent::PARAMETER_SEPARATOR.'FooBar', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');

        // The project
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProj', array(false));
        $project->setReturnValue('getUnixName', 'testproj', array(true));
        $evt->setReturnValue('getProject', $project, array('142'));

        // SVN
        $backendSVN = new MockBackendSVN($this);
        $backendSVN->setReturnValue('repositoryExists', false);
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        // CVS
        $backendCVS = new MockBackendCVS($this);
        $backendCVS->setReturnValue('repositoryExists', false);
        $evt->setReturnValue('getBackend', $backendCVS, array('CVS'));

        // System
        $backendSystem = new MockBackendSystem($this);
        $backendSystem->setReturnValue('projectHomeExists', false);

        //FRS
        $backendSystem->setReturnValue('renameFileReleasedDirectory', true);

        //FTP
        $backendSystem->expectOnce('renameAnonFtpDirectory', array($project, 'FooBar'));
        $backendSystem->setReturnValue('renameAnonFtpDirectory', false);

        $evt->setReturnValue('getBackend', $backendSystem, array('System'));

        // DB
        $evt->setReturnValue('updateDB', true);

        // Event
        $em = \Mockery::mock(EventManager::class);
        $em->shouldReceive('processEvent')->with('SystemEvent_PROJECT_RENAME', array('project' => $project, 'new_name' => 'FooBar'));
        $evt->setReturnValue('getEventManager', $em);

        $evt->expectOnce('addProjectHistory', array('rename_with_error', 'TestProj :: FooBar (event n°1)', $project->getId()));

        // There is an error, the rename in not "done"
        $evt->expectNever('done');

        $this->assertFalse($evt->process());

        // Check errors
        $this->assertEqual($evt->getStatus(), SystemEvent::STATUS_ERROR);
        $this->assertPattern('/Could not rename FTP repository/i', $evt->getLog());
    }

    public function testRenameDBUpdateFailure()
    {
        $evt = new SystemEvent_PROJECT_RENAME_TestVersion($this);
        $evt->__construct('1', SystemEvent::TYPE_PROJECT_RENAME, SystemEvent::OWNER_ROOT, '142'.SystemEvent::PARAMETER_SEPARATOR.'FooBar', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');

        // The project
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProj', array(false));
        $project->setReturnValue('getUnixName', 'testproj', array(true));
        $evt->setReturnValue('getProject', $project, array('142'));

        // SVN
        $backendSVN = new MockBackendSVN($this);
        $backendSVN->setReturnValue('repositoryExists', false);
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        // CVS
        $backendCVS = new MockBackendCVS($this);
        $backendCVS->setReturnValue('repositoryExists', false);
        $evt->setReturnValue('getBackend', $backendCVS, array('CVS'));

        // System
        $backendSystem = new MockBackendSystem($this);
        $backendSystem->setReturnValue('projectHomeExists', false);

        //FRS
        $backendSystem->setReturnValue('renameFileReleasedDirectory', true);

        // FTP
        $backendSystem->setReturnValue('renameAnonFtpDirectory', true);
        $backendSystem->expectOnce('renameAnonFtpDirectory', array($project, 'FooBar'));

        $evt->setReturnValue('getBackend', $backendSystem, array('System'));

        // DB
        $evt->setReturnValue('updateDB', false);

        // Event
        $em = \Mockery::mock(EventManager::class);
        $em->shouldReceive('processEvent')->with('SystemEvent_PROJECT_RENAME', array('project' => $project, 'new_name' => 'FooBar'));
        $evt->setReturnValue('getEventManager', $em);

        $evt->expectOnce('addProjectHistory', array('rename_with_error', 'TestProj :: FooBar (event n°1)', $project->getId()));

        // There is an error, the rename in not "done"
        $evt->expectNever('done');

        $this->assertFalse($evt->process());
    }

    function testMultipleErrorLogs()
    {
        $evt = new SystemEvent_PROJECT_RENAME_TestVersion($this);
        $evt->__construct('1', SystemEvent::TYPE_PROJECT_RENAME, SystemEvent::OWNER_ROOT, '142'.SystemEvent::PARAMETER_SEPARATOR.'FooBar', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');

        // The project
        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', 'TestProj', array(false));
        $project->setReturnValue('getUnixName', 'testproj', array(true));
        $evt->setReturnValue('getProject', $project, array('142'));

        // Error in SVN
        $backendSVN = new MockBackendSVN($this);
        $backendSVN->setReturnValue('repositoryExists', true);
        $backendSVN->setReturnValue('isNameAvailable', false);
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        // Error in CVS
        $backendCVS = new MockBackendCVS($this);
        $backendCVS->setReturnValue('repositoryExists', true);
        $backendCVS->setReturnValue('isNameAvailable', false);
        $evt->setReturnValue('getBackend', $backendCVS, array('CVS'));

        // System
        $backendSystem = new MockBackendSystem($this);
        $backendSystem->setReturnValue('projectHomeExists', false);

        //FRS
        $backendSystem->setReturnValue('renameFileReleasedDirectory', true);

        // FTP
        $backendSystem->setReturnValue('renameAnonFtpDirectory', true);

        $evt->setReturnValue('getBackend', $backendSystem, array('System'));

        // DB
        $evt->setReturnValue('updateDB', true);

        // Event
        $evt->setReturnValue('getEventManager', \Mockery::spy(EventManager::class));

        $evt->process();

        $this->assertEqual($evt->getStatus(), SystemEvent::STATUS_ERROR);
        $this->assertPattern('/.*SVN repository.*not available/', $evt->getLog());
        $this->assertPattern('/.*CVS repository.*not available/', $evt->getLog());
    }
}
