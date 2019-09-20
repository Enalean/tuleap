<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

class Project_HierarchyManagerTest extends TuleapTestCase
{
    private $dao;

    /** @var Project_HierarchyManager */
    private $hierarchy_manager;

    public function setUp()
    {
        parent::setUp();

        $this->dao = mock('ProjectHierarchyDao');
        $project_manager = mock('ProjectManager');
        $this->hierarchy_manager = partial_mock('Project_HierarchyManager', array('getParentProject', 'getAllParents'), array($project_manager, $this->dao));

        stub($this->hierarchy_manager)->getAllParents()->returns(array());
    }

    public function testSetParentProjectReturnsTrueIfItAddsParent()
    {
        stub($this->dao)->addParentProject()->returns(true);

        stub($this->hierarchy_manager)->getParentProject()->returns(null);

        expect($this->dao)->removeParentProject()->never();
        expect($this->dao)->addParentProject()->once();
        expect($this->dao)->updateParentProject()->never();

        $set = $this->hierarchy_manager->setParentProject(185, 52);
        $this->assertTrue($set);
    }

    public function testSetParentProjectReturnsTrueIfItUpdatesParent()
    {
        stub($this->dao)->updateParentProject()->returns(true);

        $parent_project_already_saved = stub('Project')->getId()->returns(52);

        stub($this->hierarchy_manager)->getParentProject()->returns($parent_project_already_saved);

        expect($this->dao)->removeParentProject()->never();
        expect($this->dao)->addParentProject()->never();
        expect($this->dao)->updateParentProject()->once();

        $set = $this->hierarchy_manager->setParentProject(185, 59);
        $this->assertTrue($set);
    }

    public function testSetParentProjectReturnsTrueIfItDeletesParent()
    {
        stub($this->dao)->removeParentProject()->returns(true);

        $parent_project_already_saved = stub('Project')->getId()->returns(52);

        stub($this->hierarchy_manager)->getParentProject()->returns($parent_project_already_saved);

        expect($this->dao)->removeParentProject()->once();
        expect($this->dao)->addParentProject()->never();
        expect($this->dao)->updateParentProject()->never();

        $set = $this->hierarchy_manager->setParentProject(185, null);
        $this->assertTrue($set);
    }

    public function testSetParentProjectThrowsExceptionIfProjectIsAncestorOfParent()
    {
        $project_manager   = mock('ProjectManager');
        $hierarchy_manager = partial_mock('Project_HierarchyManager', array('getParentProject', 'getAllParents'), array($project_manager, $this->dao));

        stub($this->dao)->addParentProject()->returns(true);
        stub($hierarchy_manager)->getAllParents(185)->returns(array(135));
        stub($hierarchy_manager)->getParentProject(135)->returns(null);

        $this->expectException('Project_HierarchyManagerAlreadyAncestorException');

        $hierarchy_manager->setParentProject(135, 185);
    }

    public function testSetParentProjectReturnsFalseIfProjectAddsItselfAsParent()
    {
        stub($this->dao)->addParentProject()->returns(true);

        $this->expectException('Project_HierarchyManagerAncestorIsSelfException');

        $this->hierarchy_manager->setParentProject(135, 135);
    }
}

class Project_HierarchyManagerAllParentsTest extends TuleapTestCase
{

    private $dao;

    /** @var Project_HierarchyManager */
    private $hierarchy_manager;

    public function setUp()
    {
        parent::setUp();

        $this->dao = mock('ProjectHierarchyDao');
        $project_manager = mock('ProjectManager');
        $this->hierarchy_manager = partial_mock('Project_HierarchyManager', array('getParentProject'), array($project_manager, $this->dao));
    }


    public function testGetAllParentsReturnsAnEmptyArrayIfTheProjectIsOrphan()
    {
        $project_id = 145;
        stub($this->hierarchy_manager)->getParentProject(145)->returns(false);

        $result = $this->hierarchy_manager->getAllParents($project_id);
        $this->assertArrayEmpty($result);
    }

    public function testGetAllParentsReturnsOneElementInArrayIfTheProjectHasOneParentWhichIsOrphan()
    {
        $father_project = stub('Project')->getId()->returns(247);
        $project_id     = 145;

        stub($this->hierarchy_manager)->getParentProject(145)->returns($father_project);
        stub($this->hierarchy_manager)->getParentProject(247)->returns(false);

        $result   = $this->hierarchy_manager->getAllParents($project_id);
        $expected = array(247);

        $this->assertArrayNotEmpty($result);
        $this->assertEqual($expected, $result);
    }

    public function testGetAllParentsReturnsAsManyElmementsInArrayAsTheProjectHasAncestors()
    {
        $great_grand_mother_project = stub('Project')->getId()->returns(444);
        $grand_mother_project       = stub('Project')->getId()->returns(333);
        $mother_project             = stub('Project')->getId()->returns(222);
        $project_id                 = 111;

        stub($this->hierarchy_manager)->getParentProject(111)->returns($mother_project);
        stub($this->hierarchy_manager)->getParentProject(222)->returns($grand_mother_project);
        stub($this->hierarchy_manager)->getParentProject(333)->returns($great_grand_mother_project);
        stub($this->hierarchy_manager)->getParentProject(444)->returns(false);

        $result   = $this->hierarchy_manager->getAllParents($project_id);
        $expected = array(
            222,
            333,
            444,
        );

        $this->assertArrayNotEmpty($result);
        $this->assertEqual($expected, $result);
    }
}
