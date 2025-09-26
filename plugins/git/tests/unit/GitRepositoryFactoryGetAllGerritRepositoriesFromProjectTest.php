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

use Git;
use GitDao;
use GitRepository;
use GitRepositoryFactory;
use GitRepositoryWithPermissions;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectManager;
use ProjectUGroup;
use TestHelper;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitRepositoryFactoryGetAllGerritRepositoriesFromProjectTest extends TestCase
{
    private GitDao&MockObject $dao;
    private GitRepositoryFactory&MockObject $factory;
    private GitRepository $repository;
    private int $project_id;
    private PFUser $user;
    private Project $project;

    #[\Override]
    protected function setUp(): void
    {
        $this->dao       = $this->createMock(GitDao::class);
        $project_manager = $this->createMock(ProjectManager::class);

        $this->factory = $this->getMockBuilder(GitRepositoryFactory::class)
            ->setConstructorArgs([$this->dao, $project_manager])
            ->onlyMethods(['instanciateFromRow', 'getGerritRepositoriesWithPermissionsForUGroupAndProject'])
            ->getMock();

        $this->repository = GitRepositoryTestBuilder::aProjectRepository()->build();

        $this->project_id = 320;

        $this->project = ProjectTestBuilder::aProject()->withId($this->project_id)->build();
        $this->user    = UserTestBuilder::aUser()
            ->withUserGroupMembership($this->project, 404, true)
            ->withUserGroupMembership($this->project, 416, true)
            ->build();
    }

    public function testItFetchAllGerritRepositoriesFromDao(): void
    {
        $this->dao->expects($this->once())->method('searchAllGerritRepositoriesOfProject')->with($this->project_id)->willReturn([]);
        $this->factory->getAllGerritRepositoriesFromProject($this->project, $this->user);
    }

    public function testItInstanciateGitRepositoriesObjects(): void
    {
        $this->dao->method('searchAllGerritRepositoriesOfProject')->willReturn(TestHelper::arrayToDar(['repository_id' => 12], ['repository_id' => 23]));
        $this->factory->method('instanciateFromRow')->willReturn(GitRepositoryTestBuilder::aProjectRepository()->build());

        $this->factory->method('getGerritRepositoriesWithPermissionsForUGroupAndProject')->willReturn([]);

        self::assertCount(2, $this->factory->getAllGerritRepositoriesFromProject($this->project, $this->user));
    }

    public function testItMergesPermissions(): void
    {
        $this->dao->method('searchAllGerritRepositoriesOfProject')->willReturn(TestHelper::arrayToDar(['repository_id' => 12]));
        $this->factory->method('instanciateFromRow')->willReturn($this->repository);

        $this->factory->method('getGerritRepositoriesWithPermissionsForUGroupAndProject')->willReturn([
            12 => new GitRepositoryWithPermissions(
                $this->repository,
                [
                    Git::PERM_READ          => [],
                    Git::PERM_WRITE         => [ProjectUGroup::PROJECT_ADMIN, 404],
                    Git::PERM_WPLUS         => [],
                    Git::SPECIAL_PERM_ADMIN => [],
                ]
            ),
        ]);

        $repositories_with_permissions = $this->factory->getAllGerritRepositoriesFromProject($this->project, $this->user);

        self::assertEquals([
            12 => new GitRepositoryWithPermissions(
                $this->repository,
                [
                    Git::PERM_READ          => [],
                    Git::PERM_WRITE         => [ProjectUGroup::PROJECT_ADMIN, 404],
                    Git::PERM_WPLUS         => [],
                    Git::SPECIAL_PERM_ADMIN => [ProjectUGroup::PROJECT_ADMIN],
                ]
            ),
        ], $repositories_with_permissions);
    }
}
