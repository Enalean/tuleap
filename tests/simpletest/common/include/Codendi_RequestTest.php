<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class Codendi_RequestTest extends TuleapTestCase
{

    private $project_manager;
    private $project;

    public function setUp()
    {
        parent::setUp();
        $this->project         = mock('Project');
        $this->project_manager = stub('ProjectManager')->getProject(123)->returns($this->project);
    }

    public function itReturnsTheProject()
    {
        $request = new Codendi_Request(array('group_id' => '123'), $this->project_manager);
        $this->assertEqual($this->project, $request->getProject());
    }

    public function itReturnsNullIfInvalidRequestedGroupId()
    {
        $request = new Codendi_Request(array('group_id' => 'stuff'), $this->project_manager);
        $this->assertNull($request->getProject());
    }
}
