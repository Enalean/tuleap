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
use Project;
use Project_AccessException;
use ProjectDao;
use ProjectHistoryDao;
use ProjectManager;
use TestHelper;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use UserManager;

final class ProjectManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;
    use GlobalLanguageMock;
    use GlobalResponseMock;

    /** @var UserManager */
    private $user_manager;

    /** @var ProjectManager */
    private $project_manager_test_version;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectDao
     */
    private $project_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user_manager = \Mockery::spy(UserManager::class);

        $this->project_dao = Mockery::mock(ProjectDao::class);

        $this->project_manager_test_version = \Mockery::mock(\ProjectManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->project_manager_test_version->shouldReceive('_getUserManager')->andReturns($this->user_manager);
    }

    protected function tearDown(): void
    {
        ProjectManager::clearInstance();
        unset($GLOBALS['Language']);
        unset($GLOBALS['Response']);
    }

    public function testOnlyProjectsTheUserCanAccessAreReturnedForTheRESTAPI(): void
    {
        $project_access_checker = Mockery::mock(ProjectAccessChecker::class);
        $project_manager        = ProjectManager::testInstance(
            $project_access_checker,
            Mockery::mock(ProjectHistoryDao::class),
            $this->project_dao
        );

        $this->project_dao->shouldReceive('getMyAndPublicProjectsForREST')->andReturn(
            TestHelper::argListToDar([['group_id' => 102], ['group_id' => 103]])
        );
        $this->project_dao->shouldReceive('foundRows')->andReturn(2);

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
        $p->shouldReceive('createProjectInstance')->andReturns($p1, $p2, $p1)->atLeast()->once();
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

        $this->project_dao->shouldReceive('renameProject')->with($p1, 'TestProj')->andReturns(true);
        $pm->shouldReceive('_getDao')->andReturns($this->project_dao);

        $this->assertTrue($pm->renameProject($p1, 'TestProj'));
        $this->assertFalse($pm->isCached($p1->getId()));
    }

    public function testGetActiveProjectsForUserExcludesProjectsARestrictedUserDontHaveAccessTo(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive([
            'getId' => 69,
            'isRestricted' => true,
        ]);

        $this->project_dao->shouldReceive('searchActiveProjectsForUser')->with(69)->andReturns(\TestHelper::arrayToDar(['group_id' => 101, 'access' => Project::ACCESS_PRIVATE_WO_RESTRICTED], ['group_id' => 102, 'access' => Project::ACCESS_PRIVATE], ['group_id' => 103, 'access' => Project::ACCESS_PUBLIC], ['group_id' => 104, 'access' => Project::ACCESS_PUBLIC_UNRESTRICTED]));
        $this->project_manager_test_version->shouldReceive('_getDao')->andReturns($this->project_dao);

        $this->project_manager_test_version->shouldReceive('createProjectInstance')->times(3);
        $this->project_manager_test_version->shouldReceive('createProjectInstance')->with(['group_id' => 102, 'access' => Project::ACCESS_PRIVATE])->ordered();
        $this->project_manager_test_version->shouldReceive('createProjectInstance')->with(['group_id' => 103, 'access' => Project::ACCESS_PUBLIC])->ordered();
        $this->project_manager_test_version->shouldReceive('createProjectInstance')->with(['group_id' => 104, 'access' => Project::ACCESS_PUBLIC_UNRESTRICTED])->ordered();

        $projects = $this->project_manager_test_version->getActiveProjectsForUser($user);

        $this->assertCount(3, $projects);
    }

    public function testUpdateStatus(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getStatus')->andReturn('S');
        $project->shouldReceive('getID')->andReturn(111);

        $this->project_manager_test_version->shouldReceive('_getDao')->andReturns($this->project_dao);
        $this->project_dao->shouldReceive("updateStatus")->andReturn(true)->once();

        $this->project_manager_test_version->updateStatus($project, 'A');

        $this->project_manager_test_version->shouldReceive('removeProjectFromCache')->with($project);
    }

    public function testUpdateStatusReturnFeedbackWhenUpdateDidntWork(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getStatus')->andReturn('S');
        $project->shouldReceive('getID')->andReturn(111);

        $this->project_manager_test_version->shouldReceive('_getDao')->andReturns($this->project_dao);
        $this->project_dao->shouldReceive("updateStatus")->andReturn(false)->once();

        $GLOBALS['Response']->method("addFeedback");

        $this->project_manager_test_version->updateStatus($project, 'A');

        $this->project_manager_test_version->shouldReceive('removeProjectFromCache')->never();
    }

    public function testUpdateStatusTrowExceptionWhenProjectIsDeleted(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getStatus')->andReturn('D');
        $project->shouldReceive('getID')->andReturn(111);

        $this->project_manager_test_version->shouldReceive('_getDao')->andReturns($this->project_dao);
        $this->project_dao->shouldReceive("updateStatus")->never();

        $this->expectException(DeletedProjectStatusChangeException::class);
        $this->project_manager_test_version->updateStatus($project, 'A');
    }
}
