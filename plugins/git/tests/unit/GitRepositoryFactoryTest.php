<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Git;

use Git_Backend_Interface;
use GitDao;
use GitRepositoryFactory;
use GitRepositoryGitoliteAdmin;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectManager;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitRepositoryFactoryTest extends TestCase
{
    private GitDao&MockObject $dao;
    private GitRepositoryFactory $factory;

    protected function setUp(): void
    {
        $this->dao       = $this->createMock(GitDao::class);
        $project_manager = $this->createMock(ProjectManager::class);
        $project         = ProjectTestBuilder::aProject()->withId(101)->withUnixName('garden')->build();

        $project_manager->method('getProjectByUnixName')->with('garden')->willReturn($project);

        $this->factory = new GitRepositoryFactory($this->dao, $project_manager);
    }

    public function testGetRepositoryFromFullPath(): void
    {
        $this->dao->expects($this->once())->method('searchProjectRepositoryByPath')
            ->with(101, 'garden/u/manuel/grou/ping/diskinstaller.git')->willReturn([]);

        $this->factory->getFromFullPath('/data/tuleap/gitolite/repositories/garden/u/manuel/grou/ping/diskinstaller.git');
    }

    public function testGetRepositoryFromFullPathAndGitRoot(): void
    {
        $this->dao->expects($this->once())->method('searchProjectRepositoryByPath')
            ->with(101, 'garden/diskinstaller.git')->willReturn([]);

        $this->factory->getFromFullPath('/data/tuleap/gitroot/garden/diskinstaller.git');
    }

    public function testItReturnsSpecialRepositoryWhenIdMatches(): void
    {
        self::assertInstanceOf(
            GitRepositoryGitoliteAdmin::class,
            $this->factory->getRepositoryById((int) GitRepositoryGitoliteAdmin::ID)
        );
    }

    public function testItCanonicalizesRepositoryName(): void
    {
        $user    = UserTestBuilder::buildWithDefaults();
        $project = ProjectTestBuilder::aProject()->build();
        $backend = $this->createMock(Git_Backend_Interface::class);

        $repository = $this->factory->buildRepository($project, 'a', $user, $backend);
        self::assertEquals('a', $repository->getName());

        $repository = $this->factory->buildRepository($project, 'a/b', $user, $backend);
        self::assertEquals('a/b', $repository->getName());

        $repository = $this->factory->buildRepository($project, 'a//b', $user, $backend);
        self::assertEquals('a/b', $repository->getName());

        $repository = $this->factory->buildRepository($project, 'a///b', $user, $backend);
        self::assertEquals('a/b', $repository->getName());
    }
}
