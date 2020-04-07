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

namespace Tuleap\Project\Hierarchy;

use Project_HierarchyManager;

final class HierarchyManagerTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\ProjectHierarchyDao
     */
    private $dao;

    /** @var Project_HierarchyManager */
    private $hierarchy_manager;

    protected function setUp(): void
    {
        $this->dao               = \Mockery::spy(\ProjectHierarchyDao::class);
        $this->hierarchy_manager = \Mockery::mock(\Project_HierarchyManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->hierarchy_manager->__construct(
            \Mockery::spy(\ProjectManager::class),
            $this->dao
        );
    }

    public function testSetParentProjectReturnsTrueIfItAddsParent(): void
    {
        $this->dao->shouldReceive('addParentProject')->andReturns(true)->once();

        $this->hierarchy_manager->shouldReceive('getParentProject')->andReturns(null);

        $this->dao->shouldReceive('removeParentProject')->never();
        $this->dao->shouldReceive('updateParentProject')->never();

        $set = $this->hierarchy_manager->setParentProject(185, 52);
        $this->assertTrue($set);
    }

    public function testSetParentProjectReturnsTrueIfItUpdatesParent(): void
    {
        $this->hierarchy_manager->shouldReceive('getAllParents')->andReturns(array());
        $this->dao->shouldReceive('updateParentProject')->andReturns(true)->once();

        $parent_project_already_saved = \Mockery::spy(\Project::class)->shouldReceive('getId')->andReturns(52)->getMock();

        $this->hierarchy_manager->shouldReceive('getParentProject')->andReturns($parent_project_already_saved);

        $this->dao->shouldReceive('removeParentProject')->never();
        $this->dao->shouldReceive('addParentProject')->never();

        $set = $this->hierarchy_manager->setParentProject(185, 59);
        $this->assertTrue($set);
    }

    public function testSetParentProjectReturnsTrueIfItDeletesParent(): void
    {
        $this->hierarchy_manager->shouldReceive('getAllParents')->andReturns(array());
        $this->dao->shouldReceive('removeParentProject')->andReturns(true)->once();

        $parent_project_already_saved = \Mockery::spy(\Project::class)->shouldReceive('getId')->andReturns(52)->getMock();

        $this->hierarchy_manager->shouldReceive('getParentProject')->andReturns($parent_project_already_saved);

        $this->dao->shouldReceive('addParentProject')->never();
        $this->dao->shouldReceive('updateParentProject')->never();

        $set = $this->hierarchy_manager->setParentProject(185, null);
        $this->assertTrue($set);
    }

    public function testSetParentProjectThrowsExceptionIfProjectIsAncestorOfParent(): void
    {
        $hierarchy_manager = \Mockery::mock(\Project_HierarchyManager::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $this->dao->shouldReceive('addParentProject')->andReturns(true);
        $hierarchy_manager->shouldReceive('getAllParents')->with(185)->andReturns(array(135));
        $hierarchy_manager->shouldReceive('getParentProject')->with(135)->andReturns(null);

        $this->expectException(\Project_HierarchyManagerAlreadyAncestorException::class);

        $hierarchy_manager->setParentProject(135, 185);
    }

    public function testSetParentProjectReturnsFalseIfProjectAddsItselfAsParent(): void
    {
        $this->dao->shouldReceive('getParentProject')->andReturns(\TestHelper::emptyDar());


        $this->expectException(\Project_HierarchyManagerAncestorIsSelfException::class);

        $this->hierarchy_manager->setParentProject(135, 135);
    }

    public function testGetAllParentsReturnsAnEmptyArrayIfTheProjectIsOrphan(): void
    {
        $project_id = 145;
        $this->hierarchy_manager->shouldReceive('getParentProject')->with(145)->andReturns(false);

        $result = $this->hierarchy_manager->getAllParents($project_id);
        $this->assertEmpty($result);
    }

    public function testGetAllParentsReturnsOneElementInArrayIfTheProjectHasOneParentWhichIsOrphan(): void
    {
        $father_project = \Mockery::spy(\Project::class)->shouldReceive('getId')->andReturns(247)->getMock();
        $project_id     = 145;

        $this->hierarchy_manager->shouldReceive('getParentProject')->with(145)->andReturns($father_project);
        $this->hierarchy_manager->shouldReceive('getParentProject')->with(247)->andReturns(false);

        $result   = $this->hierarchy_manager->getAllParents($project_id);
        $expected = array(247);

        $this->assertNotEmpty($result);
        $this->assertEquals($expected, $result);
    }

    public function testGetAllParentsReturnsAsManyElementsInArrayAsTheProjectHasAncestors(): void
    {
        $great_grand_mother_project = \Mockery::spy(\Project::class)->shouldReceive('getId')->andReturns(444)->getMock();
        $grand_mother_project       = \Mockery::spy(\Project::class)->shouldReceive('getId')->andReturns(333)->getMock();
        $mother_project             = \Mockery::spy(\Project::class)->shouldReceive('getId')->andReturns(222)->getMock();
        $project_id                 = 111;

        $this->hierarchy_manager->shouldReceive('getParentProject')->with(111)->andReturns($mother_project);
        $this->hierarchy_manager->shouldReceive('getParentProject')->with(222)->andReturns($grand_mother_project);
        $this->hierarchy_manager->shouldReceive('getParentProject')->with(333)->andReturns($great_grand_mother_project);
        $this->hierarchy_manager->shouldReceive('getParentProject')->with(444)->andReturns(false);

        $result   = $this->hierarchy_manager->getAllParents($project_id);
        $expected = array(
            222,
            333,
            444,
        );

        $this->assertNotEmpty($result);
        $this->assertEquals($expected, $result);
    }
}
