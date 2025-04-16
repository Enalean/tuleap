<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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

use GitActions;
use GitDao;
use GitRepository;
use GitRepositoryFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitActionsProjectPrivacyTest extends TestCase
{
    private GitDao&MockObject $dao;
    private GitRepositoryFactory&MockObject $factory;

    protected function setUp(): void
    {
        $this->dao     = $this->createMock(GitDao::class);
        $this->factory = $this->createMock(GitRepositoryFactory::class);
    }

    public function testItDoesNothingWhenThereAreNoRepositories(): void
    {
        $project_id = 99;
        $this->dao->expects($this->atLeastOnce())->method('getProjectRepositoryList')->with($project_id)->willReturn([]);
        $this->changeProjectRepositoriesAccess($project_id, true);
        $this->changeProjectRepositoriesAccess($project_id, false);
    }

    public function testItDoesNothingWeAreMakingItTheProjectPublic(): void
    {
        $project_id = 99;
        $repo_id    = 333;
        $repo       = $this->createMock(GitRepository::class);
        $repo->expects($this->never())->method('setAccess');
        $this->dao->method('getProjectRepositoryList')->with($project_id)->willReturn([$repo_id => null]);
        $this->factory->method('getRepositoryById')->with($repo_id)->willReturn($repo);
        $this->changeProjectRepositoriesAccess($project_id, false);
    }

    public function testItMakesRepositoriesPrivateWhenProjectBecomesPrivate(): void
    {
        $project_id = 99;
        $repo_id    = 333;
        $repo       = $this->createMock(GitRepository::class);
        $repo->expects($this->once())->method('setAccess')->with(GitRepository::PRIVATE_ACCESS)->willReturn('whatever');

        $repo->method('getAccess');
        $repo->expects($this->once())->method('changeAccess');

        $this->dao->method('getProjectRepositoryList')->with($project_id)->willReturn([$repo_id => null]);
        $this->factory->method('getRepositoryById')->with($repo_id)->willReturn($repo);
        $this->changeProjectRepositoriesAccess($project_id, true);
    }

    public function testItDoesNothingIfThePermissionsAreAlreadyCorrect(): void
    {
        $project_id = 99;
        $repo_id    = 333;
        $repo       = $this->createMock(GitRepository::class);
        $repo->expects($this->never())->method('setAccess');
        $repo->method('getAccess')->willReturn(GitRepository::PRIVATE_ACCESS);
        $repo->method('changeAccess')->willReturn('whatever');
        $this->dao->method('getProjectRepositoryList')->with($project_id)->willReturn([$repo_id => null]);
        $this->factory->method('getRepositoryById')->with($repo_id)->willReturn($repo);
        $this->changeProjectRepositoriesAccess($project_id, true);
    }

    public function testItHandlesAllRepositoriesOfTheProject(): void
    {
        $project_id = 99;
        $repo_id1   = 333;
        $repo_id2   = 444;

        $repo1 = $this->createMock(GitRepository::class);
        $repo1->expects($this->once())->method('setAccess')->with(GitRepository::PRIVATE_ACCESS)->willReturn('whatever');

        $repo2 = $this->createMock(GitRepository::class);
        $repo2->expects($this->once())->method('setAccess')->with(GitRepository::PRIVATE_ACCESS)->willReturn('whatever');

        $repo1->method('getAccess');
        $repo2->method('getAccess');

        $repo1->expects($this->once())->method('changeAccess');
        $repo2->expects($this->once())->method('changeAccess');

        $this->dao->method('getProjectRepositoryList')->with($project_id)->willReturn([$repo_id1 => null, $repo_id2 => null]);
        $this->factory->method('getRepositoryById')->willReturnCallback(static fn(int $id) => match ($id) {
            $repo_id1 => $repo1,
            $repo_id2 => $repo2,
        });
        $this->changeProjectRepositoriesAccess($project_id, true);
    }

    private function changeProjectRepositoriesAccess(int $project_id, bool $is_private): void
    {
        GitActions::changeProjectRepositoriesAccess($project_id, $is_private, $this->dao, $this->factory);
    }
}
