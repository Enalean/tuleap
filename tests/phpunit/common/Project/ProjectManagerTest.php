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

use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Project_AccessException;
use ProjectDao;
use ProjectHistoryDao;
use ProjectManager;
use TestHelper;

final class ProjectManagerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function tearDown() : void
    {
        ProjectManager::clearInstance();
        unset($GLOBALS['Language']);
    }

    public function testOnlyProjectsTheUserCanAccessAreReturnedForTheRESTAPI() : void
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
                    static function (Project $project) : bool {
                        return $project->getID() === 102;
                    }
                )
            );
        $project_access_checker->shouldReceive('checkUserCanAccessProject')
            ->with(
                Mockery::any(),
                Mockery::on(
                    static function (Project $project) : bool {
                        return $project->getID() === 103;
                    }
                )
            )->andThrow(Mockery::mock(Project_AccessException::class));

        $paginated_projects = $project_manager->getMyAndPublicProjectsForREST(Mockery::mock(PFUser::class), 0, 100);

        $projects = $paginated_projects->getProjects();

        $this->assertCount(1, $projects);
        $this->assertEquals(102, $projects[0]->getID());
    }
}
