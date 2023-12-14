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

use PHPUnit\Framework\MockObject\MockObject;
use Project_HierarchyManager;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class HierarchyManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \ProjectHierarchyDao&MockObject $dao;

    private Project_HierarchyManager&MockObject $hierarchy_manager;

    protected function setUp(): void
    {
        $this->dao               = $this->createMock(\ProjectHierarchyDao::class);
        $this->hierarchy_manager = $this->getMockBuilder(Project_HierarchyManager::class)
            ->setConstructorArgs([
                $this->createMock(\ProjectManager::class),
                $this->dao,
            ])
            ->onlyMethods([
                'getParentProject',
                'getAllParents',
            ])
            ->getMock();
    }

    public function testSetParentProjectReturnsTrueIfItAddsParent(): void
    {
        $this->dao->expects(self::once())->method('addParentProject')->willReturn(true);

        $this->hierarchy_manager->method('getParentProject')->willReturn(null);
        $this->hierarchy_manager->method('getAllParents')->willReturn([]);

        $this->dao->expects(self::never())->method('removeParentProject');
        $this->dao->expects(self::never())->method('updateParentProject');

        $set = $this->hierarchy_manager->setParentProject(185, 52);
        self::assertTrue($set);
    }

    public function testSetParentProjectReturnsTrueIfItUpdatesParent(): void
    {
        $this->hierarchy_manager->method('getAllParents')->willReturn([]);
        $this->dao->expects(self::once())->method('updateParentProject')->willReturn(true);

        $parent_project_already_saved = ProjectTestBuilder::aProject()->withId(52)->build();

        $this->hierarchy_manager->method('getParentProject')->willReturn($parent_project_already_saved);

        $this->dao->expects(self::never())->method('removeParentProject');
        $this->dao->expects(self::never())->method('addParentProject');

        $set = $this->hierarchy_manager->setParentProject(185, 59);
        self::assertTrue($set);
    }

    public function testSetParentProjectReturnsTrueIfItDeletesParent(): void
    {
        $this->hierarchy_manager->method('getAllParents')->willReturn([]);
        $this->dao->expects(self::once())->method('removeParentProject')->willReturn(true);

        $parent_project_already_saved = ProjectTestBuilder::aProject()->withId(52)->build();

        $this->hierarchy_manager->method('getParentProject')->willReturn($parent_project_already_saved);

        $this->dao->expects(self::never())->method('addParentProject');
        $this->dao->expects(self::never())->method('updateParentProject');

        $set = $this->hierarchy_manager->setParentProject(185, null);
        self::assertTrue($set);
    }

    public function testSetParentProjectThrowsExceptionIfProjectIsAncestorOfParent(): void
    {
        $hierarchy_manager = $this->createPartialMock(\Project_HierarchyManager::class, [
            'getAllParents',
            'getParentProject',
        ]);

        $this->dao->method('addParentProject')->willReturn(true);
        $hierarchy_manager->method('getAllParents')->with(185)->willReturn([135]);
        $hierarchy_manager->method('getParentProject')->with(135)->willReturn(null);

        self::expectException(\Project_HierarchyManagerAlreadyAncestorException::class);

        $hierarchy_manager->setParentProject(135, 185);
    }

    public function testSetParentProjectReturnsFalseIfProjectAddsItselfAsParent(): void
    {
        $this->dao->method('getParentProject')->willReturn(\TestHelper::emptyDar());
        $this->hierarchy_manager->method('getParentProject');
        $this->hierarchy_manager->method('getAllParents')->willReturn([]);

        self::expectException(\Project_HierarchyManagerAncestorIsSelfException::class);

        $this->hierarchy_manager->setParentProject(135, 135);
    }

    public function testGetAllParentsReturnsAnEmptyArrayIfTheProjectIsOrphan(): void
    {
        $project_id        = 145;
        $hierarchy_manager = $this->createPartialMock(\Project_HierarchyManager::class, [
            'getParentProject',
        ]);
        $hierarchy_manager->method('getParentProject')->with(145)->willReturn(false);

        $result = $hierarchy_manager->getAllParents($project_id);
        self::assertEmpty($result);
    }

    public function testGetAllParentsReturnsOneElementInArrayIfTheProjectHasOneParentWhichIsOrphan(): void
    {
        $father_project = ProjectTestBuilder::aProject()->withId(247)->build();
        $project_id     = 145;

        $hierarchy_manager = $this->createPartialMock(\Project_HierarchyManager::class, [
            'getParentProject',
        ]);
        $hierarchy_manager->method('getParentProject')
            ->withConsecutive([145], [247])
            ->willReturnOnConsecutiveCalls($father_project, false);

        $result   = $hierarchy_manager->getAllParents($project_id);
        $expected = [247];

        self::assertNotEmpty($result);
        self::assertEquals($expected, $result);
    }

    public function testGetAllParentsReturnsAsManyElementsInArrayAsTheProjectHasAncestors(): void
    {
        $great_grand_mother_project = ProjectTestBuilder::aProject()->withId(444)->build();
        $grand_mother_project       = ProjectTestBuilder::aProject()->withId(333)->build();
        $mother_project             = ProjectTestBuilder::aProject()->withId(222)->build();
        $project_id                 = 111;

        $hierarchy_manager = $this->createPartialMock(\Project_HierarchyManager::class, [
            'getParentProject',
        ]);
        $hierarchy_manager->method('getParentProject')
            ->withConsecutive([111], [222], [333], [444])
            ->willReturnOnConsecutiveCalls($mother_project, $grand_mother_project, $great_grand_mother_project, false);

        $result   = $hierarchy_manager->getAllParents($project_id);
        $expected = [
            222,
            333,
            444,
        ];

        self::assertNotEmpty($result);
        self::assertEquals($expected, $result);
    }
}
