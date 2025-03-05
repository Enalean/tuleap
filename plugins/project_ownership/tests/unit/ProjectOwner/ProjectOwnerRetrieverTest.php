<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\ProjectOwnership\ProjectOwner;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectOwnerRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var ProjectOwnerDAO&\PHPUnit\Framework\MockObject\MockObject
     */
    private $dao;
    /**
     * @var \UserManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $user_manager;

    protected function setUp(): void
    {
        $this->dao          = $this->createMock(ProjectOwnerDAO::class);
        $this->user_manager = $this->createMock(\UserManager::class);
    }

    public function testProjectOwnerCanBeRetrieved(): void
    {
        $this->dao->method('searchByProjectID')->willReturn(['user_id' => 101, 'project_id' => 102]);
        $expected_user = $this->createMock(\PFUser::class);
        $this->user_manager->method('getUserById')->willReturn($expected_user);

        $project = $this->createMock(\Project::class);
        $project->method('getID')->willReturn(102);

        $retriever     = new ProjectOwnerRetriever($this->dao, $this->user_manager);
        $project_owner = $retriever->getProjectOwner($project);

        self::assertSame($expected_user, $project_owner);
    }

    public function testNoProjectOwner(): void
    {
        $this->dao->method('searchByProjectID')->willReturn([]);

        $project = $this->createMock(\Project::class);
        $project->method('getID')->willReturn(102);

        $retriever = new ProjectOwnerRetriever($this->dao, $this->user_manager);
        self::assertNull($retriever->getProjectOwner($project));
    }
}
