<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) Xerox, 2008. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2008. Xerox Codendi Team.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Project\ProjectAccessChecker;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class ProjectManagerGetValidProjectTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $dao;
    /** @var ProjectManager */
    private $project_manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao = \Mockery::spy(\ProjectDao::class);
        $this->project_manager = ProjectManager::testInstance(
            Mockery::mock(ProjectAccessChecker::class),
            Mockery::mock(ProjectHistoryDao::class),
            $this->dao
        );
    }

    public function testItFindsTheProjectWithItsID(): void
    {
        $this->dao->shouldReceive('searchById')->with(112)->andReturns(\TestHelper::arrayToDar(['group_id' => 112, 'status' => 'A']));
        $project = $this->project_manager->getValidProjectByShortNameOrId(112);
        $this->assertEquals(112, $project->getID());
    }

    public function testItFindsTheProjectWithItsUnixName(): void
    {
        $this->dao->shouldReceive('searchByCaseInsensitiveUnixGroupName')->with('1gpig')->andReturns(\TestHelper::arrayToDar(['group_id' => 112, 'status' => 'A']));
        $project = $this->project_manager->getValidProjectByShortNameOrId('1gpig');
        $this->assertEquals(112, $project->getID());
    }

    public function testItThrowsAnExceptionWhenNoProjectMatches(): void
    {
        $this->dao->shouldReceive('searchById')->andReturns(\TestHelper::emptyDar());
        $this->dao->shouldReceive('searchByCaseInsensitiveUnixGroupName')->andReturns(\TestHelper::emptyDar());

        $this->expectException(\Project_NotFoundException::class);
        $this->project_manager->getValidProjectByShortNameOrId('doesnt exist');
    }

    public function testItThrowsAnExceptionWhenProjectIsDeleted(): void
    {
        $this->expectException(\Project_NotFoundException::class);
        $this->dao->shouldReceive('searchById')->andReturns(\TestHelper::emptyDar());
        $this->dao->shouldReceive('searchByCaseInsensitiveUnixGroupName')->with('1gpig')->andReturns(\TestHelper::arrayToDar(['group_id' => 112, 'status' => 'D']));
        $this->project_manager->getValidProjectByShortNameOrId('1gpig');
    }
}
