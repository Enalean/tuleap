<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ProjectDashboardSaverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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

    protected function setUp(): void
    {
        $this->dao     = \Mockery::spy(\Tuleap\Dashboard\Project\ProjectDashboardDao::class);
        $this->project = \Mockery::spy(\Project::class, ['getID' => 1, 'getUnixName' => false, 'isPublic' => false]);

        $this->dao->shouldReceive('searchByProjectIdAndName')->with(1, 'new_dashboard')->andReturns(\TestHelper::emptyDar());
        $this->dao->shouldReceive('searchByProjectIdAndName')->with(1, 'existing_dashboard')->andReturns(\TestHelper::arrayToDar([
            'id'         => 1,
            'project_id' => 1,
            'name'       => 'existing_dashboard'
        ]));

        $this->admin_user = \Mockery::spy(\PFUser::class);
        $this->admin_user->shouldReceive('isAdmin')->andReturns(true);

        $this->regular_user = \Mockery::spy(\PFUser::class);
        $this->regular_user->shouldReceive('isAdmin')->andReturns(false);

        $this->project_saver = new ProjectDashboardSaver($this->dao);
    }

    public function testItSavesDashboard()
    {
        $this->dao->shouldReceive('save')->with(1, 'new_dashboard')->once();

        $this->project_saver->save($this->admin_user, $this->project, 'new_dashboard');
    }

    public function testItThrowsExceptionWhenDashboardAlreadyExists()
    {
        $this->dao->shouldReceive('save')->never();
        $this->expectException('Tuleap\Dashboard\NameDashboardAlreadyExistsException');

        $this->project_saver->save($this->admin_user, $this->project, 'existing_dashboard');
    }

    public function testItThrowsExceptionWhenNameDoesNotExist()
    {
        $this->dao->shouldReceive('save')->never();
        $this->expectException('Tuleap\Dashboard\NameDashboardDoesNotExistException');

        $this->project_saver->save($this->admin_user, $this->project, '');
    }

    public function testItThrowsExceptionWhenUserCanNotCreateDashboard()
    {
        $this->dao->shouldReceive('save')->never();
        $this->expectException('Tuleap\Dashboard\Project\UserCanNotUpdateProjectDashboardException');

        $this->project_saver->save($this->regular_user, $this->project, 'new_dashboard');
    }
}
