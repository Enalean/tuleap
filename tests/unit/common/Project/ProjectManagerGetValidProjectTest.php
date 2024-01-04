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

declare(strict_types=1);

namespace Tuleap\Project;

use PHPUnit\Framework\MockObject\MockObject;
use ProjectHistoryDao;
use ProjectManager;

final class ProjectManagerGetValidProjectTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \ProjectDao&MockObject $dao;
    private ProjectManager $project_manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao             = $this->createMock(\ProjectDao::class);
        $this->project_manager = ProjectManager::testInstance(
            $this->createMock(ProjectAccessChecker::class),
            $this->createMock(ProjectHistoryDao::class),
            $this->dao
        );
    }

    public function testItFindsTheProjectWithItsID(): void
    {
        $this->dao->method('searchById')->with(112)->willReturn(\TestHelper::arrayToDar(['group_id' => 112, 'status' => 'A']));
        $this->dao->method('searchByCaseInsensitiveUnixGroupName');
        $project = $this->project_manager->getValidProjectByShortNameOrId(112);
        self::assertEquals(112, $project->getID());
    }

    public function testItFindsTheProjectWithItsUnixName(): void
    {
        $this->dao->method('searchByCaseInsensitiveUnixGroupName')->with('1gpig')->willReturn(\TestHelper::arrayToDar(['group_id' => 112, 'status' => 'A']));
        $project = $this->project_manager->getValidProjectByShortNameOrId('1gpig');
        self::assertEquals(112, $project->getID());
    }

    public function testItThrowsAnExceptionWhenNoProjectMatches(): void
    {
        $this->dao->method('searchById')->willReturn(\TestHelper::emptyDar());
        $this->dao->method('searchByCaseInsensitiveUnixGroupName')->willReturn(\TestHelper::emptyDar());

        self::expectException(\Project_NotFoundException::class);
        $this->project_manager->getValidProjectByShortNameOrId('doesnt exist');
    }

    public function testItThrowsAnExceptionWhenProjectIsDeleted(): void
    {
        self::expectException(\Project_NotFoundException::class);
        $this->dao->method('searchById')->willReturn(\TestHelper::emptyDar());
        $this->dao->method('searchByCaseInsensitiveUnixGroupName')->with('1gpig')->willReturn(\TestHelper::arrayToDar(['group_id' => 112, 'status' => 'D']));
        $this->project_manager->getValidProjectByShortNameOrId('1gpig');
    }
}
