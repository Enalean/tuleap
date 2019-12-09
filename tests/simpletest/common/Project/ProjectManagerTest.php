<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) Xerox, 2008. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2008. Xerox Codendi Team.
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

use Tuleap\Project\ProjectAccessChecker;

Mock::generatePartial('ProjectManager', 'ProjectManagerTestVersion', array('createProjectInstance', '_getDao', '_getUserManager'));
Mock::generatePartial('ProjectManager', 'ProjectManagerTestVersion2', array('getProject', 'getProjectByUnixName', 'checkRestrictedAccess'));
Mock::generate('Project');
Mock::generate('ProjectDao');
Mock::generate('UserManager');
Mock::generate('PFUser');
Mock::generate('BaseLanguage');

class ProjectManagerTest extends TuleapTestCase
{

    /** @var UserManager */
    private $user_manager;

    /** @var ProjectManager */
    private $project_manager_test_version;

    function setUp()
    {
        parent::setUp();
        ForgeConfig::store();

        $this->user_manager = mock('UserManager');

        $this->project_manager_test_version = new ProjectManagerTestVersion();
        stub($this->project_manager_test_version)->_getUserManager()->returns($this->user_manager);
    }

    function tearDown()
    {
        ForgeConfig::restore();
        parent::tearDown();
    }

    function testGetProject()
    {
        $p1 = new MockProject($this);
        $p1->setReturnValue('getId', '1');
        $p1->setReturnValue('getUnixName', 'one');
        $p2 = new MockProject($this);
        $p2->setReturnValue('getId', '2');
        $p2->setReturnValue('getUnixName', 'two');

        $p = new ProjectManagerTestVersion($this);
        $p->setReturnValueAt(0, 'createProjectInstance', $p1);
        $p->setReturnValueAt(1, 'createProjectInstance', $p2);

        $o1 = $p->getProject(1);
        $o2 = $p->getProject(1);
        $o3 = $p->getProject(2);
        $this->assertReference($o1, $o2);
        $this->assertNotEqual($o1, $o3);
    }
    function testClear()
    {
        $p1 = new MockProject($this);
        $p1->setReturnValue('getId', '1');
        $p1->setReturnValue('getUnixName', 'one');
        $p2 = new MockProject($this);
        $p2->setReturnValue('getId', '2');
        $p2->setReturnValue('getUnixName', 'two');

        $p = new ProjectManagerTestVersion($this);
        $p->setReturnValueAt(0, 'createProjectInstance', $p1);
        $p->setReturnValueAt(1, 'createProjectInstance', $p2);
        $p->setReturnValueAt(2, 'createProjectInstance', $p1);
        $p->expectAt(0, 'createProjectInstance', array(1));
        $p->expectAt(1, 'createProjectInstance', array(2));
        $p->expectAt(2, 'createProjectInstance', array(1));

        $p->getProject(1);
        $p->getProject(1);
        $p->getProject(2);
        $p->getProject(1);
        $p->clear(1);
        $p->getProject(1);
    }

    function testRename()
    {
        $p1 = new MockProject($this);
        $p1->setReturnValue('getId', '1');
        $p1->setReturnValue('getUnixName', 'one');

        $pm = new ProjectManagerTestVersion($this);
        $pm->setReturnValue('createProjectInstance', $p1);
        $pm->getProject(1);

        $dao = new MockProjectDao($this);
        $dao->setReturnValue('renameProject', true, array($p1, 'TestProj'));
        $pm->setReturnValue('_getDao', $dao);

        $this->assertTrue($pm->renameProject($p1, 'TestProj'));
        $this->assertFalse($pm->isCached($p1->getId()));
    }

    function testGetGroupByIdForSoapNoProject()
    {
        if (!defined('GET_GROUP_FAULT')) {
            define('GET_GROUP_FAULT', '3000');
        }
        $pm = new ProjectManagerTestVersion2();
        $pm->setReturnValue('getProject', null);
        $this->expectException('SoapFault');
        $pm->getGroupByIdForSoap(1, '');
    }

    function testGetGroupByIdForSoapProjectError()
    {
        if (!defined('GET_GROUP_FAULT')) {
            define('GET_GROUP_FAULT', '3000');
        }
        $pm = new ProjectManagerTestVersion2();
        $project = new MockProject();
        $project->setReturnValue('isError', true);
        $pm->setReturnValue('getProject', $project);
        $this->expectException('SoapFault');
        $pm->getGroupByIdForSoap(1, '');
    }

    function testGetGroupByIdForSoapProjectNotActive()
    {
        if (!defined('GET_GROUP_FAULT')) {
            define('GET_GROUP_FAULT', '3000');
        }
        $pm = new ProjectManagerTestVersion2();
        $project = new MockProject();
        $project->setReturnValue('isError', false);
        $project->setReturnValue('isActive', false);
        $pm->setReturnValue('getProject', $project);
        $this->expectException('SoapFault');
        $pm->getGroupByIdForSoap(1, '');
    }

    function testGetGroupByIdForSoapRestricted()
    {
        if (!defined('GET_GROUP_FAULT')) {
            define('GET_GROUP_FAULT', '3000');
        }
        $pm = new ProjectManagerTestVersion2();
        $project = new MockProject();
        $project->setReturnValue('isError', false);
        $project->setReturnValue('isActive', true);
        $pm->setReturnValue('getProject', $project);
        $pm->setReturnValue('checkRestrictedAccess', false);
        $this->expectException('SoapFault');
        $pm->getGroupByIdForSoap(1, '');
    }

    function testGetGroupByIdForSoapPass()
    {
        if (!defined('GET_GROUP_FAULT')) {
            define('GET_GROUP_FAULT', '3000');
        }
        $pm = new ProjectManagerTestVersion2();
        $project = new MockProject();
        $project->setReturnValue('isError', false);
        $project->setReturnValue('isActive', true);
        $pm->setReturnValue('getProject', $project);
        $pm->setReturnValue('checkRestrictedAccess', true);

        $pm->getGroupByIdForSoap(1, '');
    }

    function testCheckRestrictedAccessNoRestricted()
    {
        $this->assertTrue($this->project_manager_test_version->checkRestrictedAccess(null, null));
    }

    function testCheckRestrictedAccessRestrictedNotAllowed()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);

        $this->assertTrue($this->project_manager_test_version->checkRestrictedAccess(null, null));
    }

    function testCheckRestrictedAccessNoGroup()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $this->assertFalse($this->project_manager_test_version->checkRestrictedAccess(null, null));
    }

    function testCheckRestrictedAccessNoUser()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = \Mockery::mock(\Project::class);

        $this->assertFalse($this->project_manager_test_version->checkRestrictedAccess($project, null));
    }

    function testCheckRestrictedAccessUserNotRestricted()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isRestricted')->andReturn(false);
        $project = \Mockery::mock(\Project::class);

        $this->assertTrue($this->project_manager_test_version->checkRestrictedAccess($project, $user));
    }

    function testCheckRestrictedAccessUserNotMember()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isRestricted')->andReturn(true);
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getAccess')->andReturn(Project::ACCESS_PUBLIC);
        $project->shouldReceive('userIsMember')->andReturn(false);

        $this->assertFalse($this->project_manager_test_version->checkRestrictedAccess($project, $user));
    }

    function testCheckRestrictedAccessUserIsMember()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isRestricted')->andReturn(true);
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getAccess')->andReturn(Project::ACCESS_PUBLIC);
        $project->shouldReceive('userIsMember')->andReturn(true);

        $this->assertTrue($this->project_manager_test_version->checkRestrictedAccess($project, $user));
    }

    public function testGetActiveProjectsForUserExcludesProjectsARestrictedUserDontHaveAccessTo()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive([
            'getId' => 69,
            'isRestricted' => true
        ]);

        $dao = new MockProjectDao($this);
        stub($dao)->searchActiveProjectsForUser(69)->returnsDar(
            ['group_id' => 101, 'access' => Project::ACCESS_PRIVATE_WO_RESTRICTED],
            ['group_id' => 102, 'access' => Project::ACCESS_PRIVATE],
            ['group_id' => 103, 'access' => Project::ACCESS_PUBLIC],
            ['group_id' => 104, 'access' => Project::ACCESS_PUBLIC_UNRESTRICTED]
        );
        stub($this->project_manager_test_version)->_getDao()->returns($dao);

        stub($this->project_manager_test_version)->createProjectInstance(['group_id' => 102, 'access' => Project::ACCESS_PRIVATE])->at(0);
        stub($this->project_manager_test_version)->createProjectInstance(['group_id' => 103, 'access' => Project::ACCESS_PUBLIC])->at(1);
        stub($this->project_manager_test_version)->createProjectInstance(['group_id' => 104, 'access' => Project::ACCESS_PUBLIC_UNRESTRICTED])->at(2);
        stub($this->project_manager_test_version)->createProjectInstance()->count(3);

        $projects = $this->project_manager_test_version->getActiveProjectsForUser($user);

        $this->assertCount($projects, 3);
    }
}

class ProjectManager_GetValidProjectTest extends TuleapTestCase
{
    private $dao;
    /** @var ProjectManager */
    private $project_manager;

    public function setUp()
    {
        parent::setUp();
        $this->dao = mock('ProjectDao');
        $this->project_manager = ProjectManager::testInstance(
            Mockery::mock(ProjectAccessChecker::class),
            Mockery::mock(ProjectHistoryDao::class),
            $this->dao
        );
    }

    public function itFindsTheProjectWithItsID()
    {
        stub($this->dao)->searchById(112)->returnsDar(array('group_id' => 112, 'status' => 'A'));
        $project = $this->project_manager->getValidProjectByShortNameOrId(112);
        $this->assertEqual($project->getID(), 112);
    }

    public function itFindsTheProjectWithItsUnixName()
    {
        stub($this->dao)->searchByCaseInsensitiveUnixGroupName('1gpig')->returnsDar(array('group_id' => 112, 'status' => 'A'));
        $project = $this->project_manager->getValidProjectByShortNameOrId('1gpig');
        $this->assertEqual($project->getID(), 112);
    }

    public function itThrowsAnExceptionWhenNoProjectMatches()
    {
        stub($this->dao)->searchById()->returnsEmptyDar();
        stub($this->dao)->searchByCaseInsensitiveUnixGroupName()->returnsEmptyDar();

        $this->expectException('Project_NotFoundException');
        $this->project_manager->getValidProjectByShortNameOrId('doesnt exist');
    }

    public function itThrowsAnExceptionWhenProjectIsDeleted()
    {
        $this->expectException('Project_NotFoundException');
        stub($this->dao)->searchById()->returnsEmptyDar();
        stub($this->dao)->searchByCaseInsensitiveUnixGroupName('1gpig')->returnsDar(array('group_id' => 112, 'status' => 'D'));
        $this->project_manager->getValidProjectByShortNameOrId('1gpig');
    }
}
