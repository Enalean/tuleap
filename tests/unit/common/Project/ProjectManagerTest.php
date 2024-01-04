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
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Project_AccessException;
use ProjectDao;
use ProjectHistoryDao;
use ProjectManager;
use TestHelper;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Project\Status\SwitchingBackToPendingException;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use UserManager;

final class ProjectManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;
    use GlobalLanguageMock;
    use GlobalResponseMock;

    private UserManager&MockObject $user_manager;
    private ProjectManager&MockObject $project_manager_test_version;
    private ProjectDao&MockObject $project_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user_manager = $this->createMock(UserManager::class);

        $this->project_dao = $this->createMock(ProjectDao::class);

        $this->project_manager_test_version = $this->createPartialMock(\ProjectManager::class, [
            '_getUserManager',
            '_getDao',
            'createProjectInstance',
            'removeProjectFromCache',
        ]);
        $this->project_manager_test_version->method('_getUserManager')->willReturn($this->user_manager);
    }

    protected function tearDown(): void
    {
        ProjectManager::clearInstance();
        unset($GLOBALS['Language']);
        unset($GLOBALS['Response']);
    }

    public function testOnlyProjectsTheUserCanAccessAreReturnedForTheRESTAPI(): void
    {
        $project_access_checker = $this->createMock(ProjectAccessChecker::class);
        $project_manager        = ProjectManager::testInstance(
            $project_access_checker,
            $this->createMock(ProjectHistoryDao::class),
            $this->project_dao
        );

        $this->project_dao->method('getMyAndPublicProjectsForREST')->willReturn(
            TestHelper::argListToDar([['group_id' => 102], ['group_id' => 103]])
        );
        $this->project_dao->method('foundRows')->willReturn(2);

        $project_access_checker->method('checkUserCanAccessProject')
            ->withConsecutive(
                [
                    self::anything(),
                    self::callback(static function (Project $project): bool {
                        return $project->getID() === 102;
                    }),
                ],
                [
                    self::anything(),
                    self::callback(static function (Project $project): bool {
                        return $project->getID() === 103;
                    }),
                ]
            )
            ->willReturnOnConsecutiveCalls(
                self::anything(),
                self::throwException($this->createMock(Project_AccessException::class))
            );

        $paginated_projects = $project_manager->getMyAndPublicProjectsForREST(UserTestBuilder::buildWithDefaults(), 0, 100);

        $projects = $paginated_projects->getProjects();

        self::assertCount(1, $projects);
        self::assertEquals(102, $projects[0]->getID());
    }

    public function testGetProject(): void
    {
        $p1 = ProjectTestBuilder::aProject()
            ->withId(1)
            ->withUnixName('one')
            ->build();
        $p2 = ProjectTestBuilder::aProject()
            ->withId(2)
            ->withUnixName('two')
            ->build();

        $p = $this->createPartialMock(\ProjectManager::class, [
            'createProjectInstance',
        ]);
        $p->expects(self::exactly(2))->method('createProjectInstance')->willReturnOnConsecutiveCalls($p1, $p2);

        $o1 = $p->getProject(1);
        $o2 = $p->getProject(1);
        $o3 = $p->getProject(2);

        self::assertSame($o1, $o2);
        self::assertNotEquals($o1, $o3);
    }

    public function testClear(): void
    {
        $p1 = ProjectTestBuilder::aProject()
            ->withId(1)
            ->withUnixName('one')
            ->build();
        $p2 = ProjectTestBuilder::aProject()
            ->withId(2)
            ->withUnixName('two')
            ->build();

        $p = $this->createPartialMock(\ProjectManager::class, [
            'createProjectInstance',
        ]);
        $p
            ->expects(self::exactly(3))
            ->method('createProjectInstance')
            ->withConsecutive([1], [2], [1])
            ->willReturnOnConsecutiveCalls($p1, $p2, $p1);

        $p->getProject(1);
        $p->getProject(1);
        $p->getProject(2);
        $p->getProject(1);
        $p->clear(1);
        $p->getProject(1);
    }

    public function testRename(): void
    {
        $p1 = ProjectTestBuilder::aProject()
            ->withId(1)
            ->withUnixName('one')
            ->build();

        $pm = $this->createPartialMock(\ProjectManager::class, [
            'createProjectInstance',
            '_getDao',
        ]);
        $pm->method('createProjectInstance')->willReturn($p1);
        $pm->getProject(1);

        $this->project_dao->method('renameProject')->with($p1, 'TestProj')->willReturn(true);
        $pm->method('_getDao')->willReturn($this->project_dao);

        self::assertTrue($pm->renameProject($p1, 'TestProj'));
        self::assertFalse($pm->isCached($p1->getId()));
    }

    public function testGetActiveProjectsForUserExcludesProjectsARestrictedUserDontHaveAccessTo(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $user = UserTestBuilder::aRestrictedUser()
            ->withId(69)
            ->build();

        $this->project_dao->method('searchActiveProjectsForUser')->with(69)->willReturn(\TestHelper::arrayToDar(['group_id' => 101, 'access' => Project::ACCESS_PRIVATE_WO_RESTRICTED], ['group_id' => 102, 'access' => Project::ACCESS_PRIVATE], ['group_id' => 103, 'access' => Project::ACCESS_PUBLIC], ['group_id' => 104, 'access' => Project::ACCESS_PUBLIC_UNRESTRICTED]));
        $this->project_manager_test_version->method('_getDao')->willReturn($this->project_dao);

        $this->project_manager_test_version->expects(self::exactly(3))->method('createProjectInstance')
            ->withConsecutive(
                [['group_id' => 102, 'access' => Project::ACCESS_PRIVATE]],
                [['group_id' => 103, 'access' => Project::ACCESS_PUBLIC]],
                [['group_id' => 104, 'access' => Project::ACCESS_PUBLIC_UNRESTRICTED]],
            );

        $projects = $this->project_manager_test_version->getActiveProjectsForUser($user);

        self::assertCount(3, $projects);
    }

    public function testUpdateStatus(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(111)->withStatusSuspended()->build();

        $this->project_manager_test_version->method('_getDao')->willReturn($this->project_dao);
        $this->project_dao->expects(self::once())->method('updateStatus')->willReturn(true);
        $this->project_manager_test_version->method('removeProjectFromCache')->with($project);

        $this->project_manager_test_version->updateStatus($project, 'A');
    }

    public function testUpdateStatusReturnFeedbackWhenUpdateDidntWork(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(111)->withStatusSuspended()->build();

        $this->project_manager_test_version->method('_getDao')->willReturn($this->project_dao);
        $this->project_dao->expects(self::once())->method('updateStatus')->willReturn(false);
        $this->project_manager_test_version->expects(self::once())->method('removeProjectFromCache')->with($project);

        $GLOBALS['Response']->method('addFeedback');

        $this->project_manager_test_version->updateStatus($project, 'A');
    }

    public function testUpdateStatusThrowsExceptionWhenProjectIsDeleted(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(111)->withStatusDeleted()->build();

        $this->project_manager_test_version->method('_getDao')->willReturn($this->project_dao);
        $this->project_dao->expects(self::never())->method('updateStatus');

        self::expectException(DeletedProjectStatusChangeException::class);
        $this->project_manager_test_version->updateStatus($project, 'A');
    }

    public function testUpdateStatusThrowsExceptionWhenProjectSwitchesBackToPending(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(111)->withStatusActive()->build();

        $this->project_manager_test_version->method('_getDao')->willReturn($this->project_dao);
        $this->project_dao->expects(self::never())->method('updateStatus');

        self::expectException(SwitchingBackToPendingException::class);
        $this->project_manager_test_version->updateStatus($project, 'P');
    }
}
