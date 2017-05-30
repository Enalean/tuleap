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

class ProjectDashboardSaverTest extends \TuleapTestCase
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

    public function setUp()
    {
        parent::setUp();

        $this->dao     = mock('Tuleap\Dashboard\Project\ProjectDashboardDao');
        $this->project = aMockProject()->withId(1)->build();

        stub($this->dao)->searchByProjectIdAndName(1, 'new_dashboard')->returnsEmptyDar();
        stub($this->dao)->searchByProjectIdAndName(1, 'existing_dashboard')->returnsDar(
            array(
                'id'         => 1,
                'project_id' => 1,
                'name'       => 'existing_dashboard'
            )
        );

        $this->admin_user = mock('PFUser');
        stub($this->admin_user)->isAdmin()->returns(true);

        $this->regular_user = mock('PFUser');
        stub($this->regular_user)->isAdmin()->returns(false);

        $this->project_saver = new ProjectDashboardSaver($this->dao);
    }

    public function itSavesDashboard()
    {
        expect($this->dao)->save(1, 'new_dashboard')->once();

        $this->project_saver->save($this->admin_user, $this->project, 'new_dashboard');
    }

    public function itThrowsExceptionWhenDashboardAlreadyExists()
    {
        expect($this->dao)->save()->never();
        $this->expectException('Tuleap\Dashboard\NameDashboardAlreadyExistsException');

        $this->project_saver->save($this->admin_user, $this->project, 'existing_dashboard');
    }

    public function itThrowsExceptionWhenNameDoesNotExist()
    {
        expect($this->dao)->save()->never();
        $this->expectException('Tuleap\Dashboard\NameDashboardDoesNotExistException');

        $this->project_saver->save($this->admin_user, $this->project, '');
    }

    public function itThrowsExceptionWhenUserCanNotCreateDashboard()
    {
        expect($this->dao)->save()->never();
        $this->expectException('Tuleap\Dashboard\Project\UserCanNotUpdateProjectDashboardException');

        $this->project_saver->save($this->regular_user, $this->project, 'new_dashboard');
    }
}
