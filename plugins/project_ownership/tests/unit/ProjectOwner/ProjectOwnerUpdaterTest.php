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

use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectOwnerUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var ProjectOwnerDAO&\PHPUnit\Framework\MockObject\MockObject
     */
    private $dao;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(ProjectOwnerDAO::class);
    }

    public function testProjectOwnerCanBeUpdated(): void
    {
        $this->dao->expects($this->once())->method('save');

        $user = $this->createMock(\PFUser::class);
        $user->method('getId')->willReturn('102');
        $user->method('isAdmin')->with('101')->willReturn(true);
        $project = $this->createMock(\Project::class);
        $project->method('getID')->willReturn('101');

        $updater = new ProjectOwnerUpdater($this->dao, new DBTransactionExecutorPassthrough());
        $updater->updateProjectOwner($project, $user);
    }

    public function testProjectOwnerCannotBeSetIfNotAlreadyAProjectAdmin(): void
    {
        $this->dao->expects($this->never())->method('save');

        $user = $this->createMock(\PFUser::class);
        $user->method('getId')->willReturn('102');
        $user->method('isAdmin')->with('101')->willReturn(false);
        $project = $this->createMock(\Project::class);
        $project->method('getID')->willReturn('101');

        $updater = new ProjectOwnerUpdater($this->dao, new DBTransactionExecutorPassthrough());

        $this->expectException(OnlyProjectAdministratorCanBeSetAsProjectOwnerException::class);

        $updater->updateProjectOwner($project, $user);
    }
}
