<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Git;

use Git_Backend_Interface;
use GitDao;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitProjectRenamerTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\Stub $git_dao;
    private GitProjectRenamer $git_project_renamer;
    private Git_Backend_Interface|\PHPUnit\Framework\MockObject\Stub $git_backend;
    private \Project $project;

    protected function setUp(): void
    {
        $this->project             = ProjectTestBuilder::aProject()->build();
        $this->git_backend         = $this->createStub(Git_Backend_Interface::class);
        $this->git_dao             = $this->createMock(GitDao::class);
        $this->git_project_renamer = new GitProjectRenamer($this->git_backend, $this->git_dao);
    }

    public function testItCallDaoRenameWhenBackendRenameWork(): void
    {
        $this->git_backend->method('renameProject')->with($this->project, 'aname')->willReturn(true);
        $this->git_dao->expects(self::once())->method('renameProject')->with($this->project, 'aname');
        $this->git_project_renamer->renameProject($this->project, 'aName');
    }

    public function testItNotCallDaoRenameWhenBackendRenameWork(): void
    {
        $this->git_backend->method('renameProject')->with($this->project, 'aname')->willReturn(false);
        $this->git_dao->expects(self::never())->method('renameProject');
        $this->git_project_renamer->renameProject($this->project, 'aName');
    }
}
