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

declare(strict_types=1);

namespace Tuleap\Dashboard\Project;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Dashboard\NameDashboardAlreadyExistsException;
use Tuleap\Dashboard\NameDashboardDoesNotExistException;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

final class ProjectDashboardSaverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROJECT_ID   = 145;
    private const DASHBOARD_ID = 1;
    private \PFUser $regular_user;
    private \PFUser $admin_user;
    private MockObject & ProjectDashboardDao $dao;
    private \Project $project;
    private DeleteVisitByDashboardId $delete_visit_by_dashboard_id;

    protected function setUp(): void
    {
        $this->dao     = $this->createMock(ProjectDashboardDao::class);
        $this->project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();

        $this->admin_user   = UserTestBuilder::aUser()
            ->withId(199)
            ->withoutSiteAdministrator()
            ->withAdministratorOf($this->project)
            ->build();
        $this->regular_user = UserTestBuilder::aUser()
            ->withId(297)
            ->withoutSiteAdministrator()
            ->withMemberOf($this->project)
            ->build();

        $this->delete_visit_by_dashboard_id = new class implements DeleteVisitByDashboardId {
            public bool $called = false;

            public function deleteVisitByDashboardId(int $dashboard_id): void
            {
                $this->called = true;
            }
        };
    }

    private function getSaver(): ProjectDashboardSaver
    {
        return new ProjectDashboardSaver(
            $this->dao,
            $this->delete_visit_by_dashboard_id,
            new DBTransactionExecutorPassthrough()
        );
    }

    public function testItSavesDashboard(): void
    {
        $this->dao->method('searchByProjectIdAndName')->with(self::PROJECT_ID, 'new_dashboard')->willReturn([]);
        $this->dao->expects(self::once())->method('save')->with(self::PROJECT_ID, 'new_dashboard');

        $this->getSaver()->save($this->admin_user, $this->project, 'new_dashboard');
    }

    public function testItThrowsExceptionWhenDashboardAlreadyExists(): void
    {
        $this->dao->method('searchByProjectIdAndName')->with(self::PROJECT_ID, 'existing_dashboard')->willReturn([
            'id'         => self::DASHBOARD_ID,
            'project_id' => self::PROJECT_ID,
            'name'       => 'existing_dashboard',
        ]);
        $this->dao->expects(self::never())->method('save');

        $this->expectException(NameDashboardAlreadyExistsException::class);
        $this->getSaver()->save($this->admin_user, $this->project, 'existing_dashboard');
    }

    public function testItThrowsExceptionWhenNameDoesNotExist(): void
    {
        $this->dao->expects(self::never())->method('save');

        $this->expectException(NameDashboardDoesNotExistException::class);
        $this->getSaver()->save($this->admin_user, $this->project, '');
    }

    public function testItThrowsExceptionWhenUserCanNotCreateDashboard(): void
    {
        $this->dao->expects(self::never())->method('save');

        $this->expectException(UserCanNotUpdateProjectDashboardException::class);
        $this->getSaver()->save($this->regular_user, $this->project, 'new_dashboard');
    }

    public function testDelete(): void
    {
        $this->dao->method('searchById')->with(self::DASHBOARD_ID)->willReturn([
            'id'         => self::DASHBOARD_ID,
            'project_id' => self::PROJECT_ID,
            'name'       => 'existing_dashboard',
        ]);
        $this->dao->expects(self::once())->method('delete');

        $this->getSaver()->delete($this->admin_user, $this->project, self::DASHBOARD_ID);

        self::assertTrue($this->delete_visit_by_dashboard_id->called);
    }

    public function testDeleteByNonAdmin(): void
    {
        $this->dao->expects(self::never())->method('delete');

        $this->expectException(UserCanNotUpdateProjectDashboardException::class);
        try {
            $this->getSaver()->delete($this->regular_user, $this->project, self::DASHBOARD_ID);
        } finally {
            self::assertFalse($this->delete_visit_by_dashboard_id->called);
        }
    }
}
