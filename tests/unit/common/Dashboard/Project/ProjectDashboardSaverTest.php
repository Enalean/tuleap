<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Dashboard\Project;

class ProjectDashboardSaverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /** @var PFUser */
    private $regular_user;

    /** @var PFUser */
    private $admin_user;

    /** @var ProjectDashboardDao */
    private $dao;

    /** @var ProjectDashboardSaver */
    private $project_saver;

    /** @var Project */
    private $project;
    private DeleteVisitByDashboardId $delete_visit_by_dashboard_id;

    protected function setUp(): void
    {
        $this->dao     = $this->createMock(\Tuleap\Dashboard\Project\ProjectDashboardDao::class);
        $this->project = $this->createMock(\Project::class);
        $this->project->method('getID')->willReturn(1);

        $this->admin_user = $this->createMock(\PFUser::class);
        $this->admin_user->method('isAdmin')->willReturn(true);

        $this->regular_user = $this->createMock(\PFUser::class);
        $this->regular_user->method('isAdmin')->willReturn(false);

        $this->delete_visit_by_dashboard_id = new class implements DeleteVisitByDashboardId {
            public bool $called = false;

            public function deleteVisitByDashboardId(int $dashboard_id): void
            {
                $this->called = true;
            }
        };
    }

    public function testItSavesDashboard(): void
    {
        $this->dao->method('searchByProjectIdAndName')->with(1, 'new_dashboard')->willReturn([]);
        $this->dao->expects(self::once())->method('save')->with(1, 'new_dashboard');

        $this->project_saver = new ProjectDashboardSaver($this->dao, $this->delete_visit_by_dashboard_id);
        $this->project_saver->save($this->admin_user, $this->project, 'new_dashboard');
    }

    public function testItThrowsExceptionWhenDashboardAlreadyExists(): void
    {
        $this->dao->method('searchByProjectIdAndName')->with(1, 'existing_dashboard')->willReturn([
            'id' => 1,
            'project_id' => 1,
            'name' => 'existing_dashboard',
        ]);
        $this->dao->expects(self::never())->method('save');
        self::expectException('Tuleap\Dashboard\NameDashboardAlreadyExistsException');

        $this->project_saver = new ProjectDashboardSaver($this->dao, $this->delete_visit_by_dashboard_id);
        $this->project_saver->save($this->admin_user, $this->project, 'existing_dashboard');
    }

    public function testItThrowsExceptionWhenNameDoesNotExist(): void
    {
        $this->dao->expects(self::never())->method('save');
        self::expectException('Tuleap\Dashboard\NameDashboardDoesNotExistException');

        $this->project_saver = new ProjectDashboardSaver($this->dao, $this->delete_visit_by_dashboard_id);
        $this->project_saver->save($this->admin_user, $this->project, '');
    }

    public function testItThrowsExceptionWhenUserCanNotCreateDashboard(): void
    {
        $this->dao->expects(self::never())->method('save');
        self::expectException('Tuleap\Dashboard\Project\UserCanNotUpdateProjectDashboardException');

        $this->project_saver = new ProjectDashboardSaver($this->dao, $this->delete_visit_by_dashboard_id);
        $this->project_saver->save($this->regular_user, $this->project, 'new_dashboard');
    }

    public function testDelete(): void
    {
        $this->dao->method('searchById')->with(1)->willReturn([
            'id' => 1,
            'project_id' => 1,
            'name' => 'existing_dashboard',
        ]);
        $this->dao->expects(self::once())->method('delete');

        $this->project_saver = new ProjectDashboardSaver($this->dao, $this->delete_visit_by_dashboard_id);
        $this->project_saver->delete($this->admin_user, $this->project, 1);

        self::assertTrue($this->delete_visit_by_dashboard_id->called);
    }

    public function testDeleteByNonAdmin(): void
    {
        $this->dao->expects(self::never())->method('delete');
        self::expectException('Tuleap\Dashboard\Project\UserCanNotUpdateProjectDashboardException');

        $this->project_saver = new ProjectDashboardSaver($this->dao, $this->delete_visit_by_dashboard_id);
        $this->project_saver->delete($this->regular_user, $this->project, 1);

        self::assertFalse($this->delete_visit_by_dashboard_id->called);
    }
}
