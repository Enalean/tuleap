<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project;

use ForgeAccess;
use ForgeConfig;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Project_AccessException;
use ProjectDao;
use ProjectHistoryDao;
use ProjectManager;
use TestHelper;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use UserManager;

final class ProjectManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;
    use GlobalLanguageMock;

    /** @var UserManager */
    private $user_manager;

    /** @var ProjectManager */
    private $project_manager_test_version;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user_manager = \Mockery::spy(UserManager::class);

        $this->project_manager_test_version = \Mockery::mock(\ProjectManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->project_manager_test_version->shouldReceive('_getUserManager')->andReturns($this->user_manager);
    }

    protected function tearDown(): void
    {
        ProjectManager::clearInstance();
        unset($GLOBALS['Language']);
    }

    public function testOnlyProjectsTheUserCanAccessAreReturnedForTheRESTAPI(): void
    {
        $project_access_checker = Mockery::mock(ProjectAccessChecker::class);
        $project_dao            = Mockery::mock(ProjectDao::class);
        $project_manager        = ProjectManager::testInstance(
            $project_access_checker,
            Mockery::mock(ProjectHistoryDao::class),
            $project_dao
        );

        $project_dao->shouldReceive('getMyAndPublicProjectsForREST')->andReturn(
            TestHelper::argListToDar([['group_id' => 102], ['group_id' => 103]])
        );
        $project_dao->shouldReceive('foundRows')->andReturn(2);

        $project_access_checker->shouldReceive('checkUserCanAccessProject')
            ->with(
                Mockery::any(),
                Mockery::on(
                    static function (Project $project): bool {
                        return $project->getID() === 102;
                    }
                )
            );
        $project_access_checker->shouldReceive('checkUserCanAccessProject')
            ->with(
                Mockery::any(),
                Mockery::on(
                    static function (Project $project): bool {
                        return $project->getID() === 103;
                    }
                )
            )->andThrow(Mockery::mock(Project_AccessException::class));

        $paginated_projects = $project_manager->getMyAndPublicProjectsForREST(Mockery::mock(PFUser::class), 0, 100);

        $projects = $paginated_projects->getProjects();

        $this->assertCount(1, $projects);
        $this->assertEquals(102, $projects[0]->getID());
    }

    public function testGetProject(): void
    {
        $p1 = \Mockery::spy(\Project::class);
        $p1->shouldReceive('getId')->andReturns('1');
        $p1->shouldReceive('getUnixName')->andReturns('one');
        $p2 = \Mockery::spy(\Project::class);
        $p2->shouldReceive('getId')->andReturns('2');
        $p2->shouldReceive('getUnixName')->andReturns('two');

        $p = \Mockery::mock(\ProjectManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $p->shouldReceive('createProjectInstance')->once()->andReturns($p1);
        $p->shouldReceive('createProjectInstance')->once()->andReturns($p2);

        $o1 = $p->getProject(1);
        $o2 = $p->getProject(1);
        $o3 = $p->getProject(2);

        $this->assertSame($o1, $o2);
        $this->assertNotEquals($o1, $o3);
    }

    public function testClear(): void
    {
        $p1 = \Mockery::spy(\Project::class);
        $p1->shouldReceive('getId')->andReturns('1');
        $p1->shouldReceive('getUnixName')->andReturns('one');
        $p2 = \Mockery::spy(\Project::class);
        $p2->shouldReceive('getId')->andReturns('2');
        $p2->shouldReceive('getUnixName')->andReturns('two');

        $p = \Mockery::mock(\ProjectManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $p->shouldReceive('createProjectInstance')->andReturns($p1, $p2, $p1);
        $p->shouldReceive('createProjectInstance')->with(1)->ordered();
        $p->shouldReceive('createProjectInstance')->with(2)->ordered();
        $p->shouldReceive('createProjectInstance')->with(1)->ordered();

        $p->getProject(1);
        $p->getProject(1);
        $p->getProject(2);
        $p->getProject(1);
        $p->clear(1);
        $p->getProject(1);
    }

    public function testRename(): void
    {
        $p1 = \Mockery::spy(\Project::class);
        $p1->shouldReceive('getId')->andReturns('1');
        $p1->shouldReceive('getUnixName')->andReturns('one');

        $pm = \Mockery::mock(\ProjectManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $pm->shouldReceive('createProjectInstance')->andReturns($p1);
        $pm->getProject(1);

        $dao = \Mockery::spy(\ProjectDao::class);
        $dao->shouldReceive('renameProject')->with($p1, 'TestProj')->andReturns(true);
        $pm->shouldReceive('_getDao')->andReturns($dao);

        $this->assertTrue($pm->renameProject($p1, 'TestProj'));
        $this->assertFalse($pm->isCached($p1->getId()));
    }

    public function testGetGroupByIdForSoapNoProject(): void
    {
        if (!defined('GET_GROUP_FAULT')) {
            define('GET_GROUP_FAULT', '3000');
        }
        $pm = \Mockery::mock(\ProjectManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $pm->shouldReceive('getProject')->andReturns(null);
        $this->expectException(\SoapFault::class);
        $pm->getGroupByIdForSoap(1, '');
    }

    public function testGetGroupByIdForSoapProjectError(): void
    {
        if (!defined('GET_GROUP_FAULT')) {
            define('GET_GROUP_FAULT', '3000');
        }
        $pm = \Mockery::mock(\ProjectManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('isError')->andReturns(true);
        $pm->shouldReceive('getProject')->andReturns($project);
        $this->expectException(\SoapFault::class);
        $pm->getGroupByIdForSoap(1, '');
    }

    public function testGetGroupByIdForSoapProjectNotActive(): void
    {
        if (!defined('GET_GROUP_FAULT')) {
            define('GET_GROUP_FAULT', '3000');
        }
        $pm = \Mockery::mock(\ProjectManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('isError')->andReturns(false);
        $project->shouldReceive('isActive')->andReturns(false);
        $pm->shouldReceive('getProject')->andReturns($project);
        $this->expectException(\SoapFault::class);
        $pm->getGroupByIdForSoap(1, '');
    }

    public function testGetGroupByIdForSoapRestricted(): void
    {
        if (!defined('GET_GROUP_FAULT')) {
            define('GET_GROUP_FAULT', '3000');
        }
        $pm = \Mockery::mock(\ProjectManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('isError')->andReturns(false);
        $project->shouldReceive('isActive')->andReturns(true);
        $pm->shouldReceive('getProject')->andReturns($project);
        $pm->shouldReceive('checkRestrictedAccess')->andReturns(false);
        $this->expectException(\SoapFault::class);
        $pm->getGroupByIdForSoap(1, '');
    }

    public function testGetGroupByIdForSoapPass(): void
    {
        if (!defined('GET_GROUP_FAULT')) {
            define('GET_GROUP_FAULT', '3000');
        }
        $pm = \Mockery::mock(\ProjectManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('isError')->andReturns(false);
        $project->shouldReceive('isActive')->andReturns(true);
        $pm->shouldReceive('getProject')->andReturns($project);
        $pm->shouldReceive('checkRestrictedAccess')->andReturns(true);

        $pm->getGroupByIdForSoap(1, '');
    }

    public function testCheckRestrictedAccessNoRestricted(): void
    {
        $this->assertTrue($this->project_manager_test_version->checkRestrictedAccess(null, null));
    }

    public function testCheckRestrictedAccessRestrictedNotAllowed(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);

        $this->assertTrue($this->project_manager_test_version->checkRestrictedAccess(null, null));
    }

    public function testCheckRestrictedAccessNoGroup(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $this->assertFalse($this->project_manager_test_version->checkRestrictedAccess(null, null));
    }

    public function testCheckRestrictedAccessNoUser(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = \Mockery::mock(\Project::class);

        $this->assertFalse($this->project_manager_test_version->checkRestrictedAccess($project, null));
    }

    public function testCheckRestrictedAccessUserNotRestricted(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isRestricted')->andReturn(false);
        $project = \Mockery::mock(\Project::class);

        $this->assertTrue($this->project_manager_test_version->checkRestrictedAccess($project, $user));
    }

    public function testCheckRestrictedAccessUserNotMember(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isRestricted')->andReturn(true);
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getAccess')->andReturn(Project::ACCESS_PUBLIC);
        $project->shouldReceive('userIsMember')->andReturn(false);

        $this->assertFalse($this->project_manager_test_version->checkRestrictedAccess($project, $user));
    }

    public function testCheckRestrictedAccessUserIsMember(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isRestricted')->andReturn(true);
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getAccess')->andReturn(Project::ACCESS_PUBLIC);
        $project->shouldReceive('userIsMember')->andReturn(true);

        $this->assertTrue($this->project_manager_test_version->checkRestrictedAccess($project, $user));
    }

    public function testGetActiveProjectsForUserExcludesProjectsARestrictedUserDontHaveAccessTo(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive([
            'getId' => 69,
            'isRestricted' => true
        ]);

        $dao = \Mockery::spy(\ProjectDao::class);
        $dao->shouldReceive('searchActiveProjectsForUser')->with(69)->andReturns(\TestHelper::arrayToDar(['group_id' => 101, 'access' => Project::ACCESS_PRIVATE_WO_RESTRICTED], ['group_id' => 102, 'access' => Project::ACCESS_PRIVATE], ['group_id' => 103, 'access' => Project::ACCESS_PUBLIC], ['group_id' => 104, 'access' => Project::ACCESS_PUBLIC_UNRESTRICTED]));
        $this->project_manager_test_version->shouldReceive('_getDao')->andReturns($dao);

        $this->project_manager_test_version->shouldReceive('createProjectInstance')->times(3);
        $this->project_manager_test_version->shouldReceive('createProjectInstance')->with(['group_id' => 102, 'access' => Project::ACCESS_PRIVATE])->ordered();
        $this->project_manager_test_version->shouldReceive('createProjectInstance')->with(['group_id' => 103, 'access' => Project::ACCESS_PUBLIC])->ordered();
        $this->project_manager_test_version->shouldReceive('createProjectInstance')->with(['group_id' => 104, 'access' => Project::ACCESS_PUBLIC_UNRESTRICTED])->ordered();

        $projects = $this->project_manager_test_version->getActiveProjectsForUser($user);

        $this->assertCount(3, $projects);
    }
}
