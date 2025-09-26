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
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitRepositoryFactoryGetGerritRepositoriesWithPermissionsForUGroupTest extends TestCase
{
    private GitDao&MockObject $dao;
    private GitRepositoryFactory&MockObject $factory;
    private int $project_id;
    private PFUser&MockObject $user;
    private Project $project;
    private ProjectUGroup $ugroup;

    #[\Override]
    protected function setUp(): void
    {
        $this->dao       = $this->createMock(GitDao::class);
        $project_manager = $this->createMock(ProjectManager::class);

        $this->factory = $this->getMockBuilder(GitRepositoryFactory::class)
            ->setConstructorArgs([$this->dao, $project_manager])
            ->onlyMethods(['instanciateFromRow'])
            ->getMock();

        $this->project_id = 320;
        $ugroup_id        = 115;

        $user_ugroups  = [404, 416];
        $this->project = ProjectTestBuilder::aProject()->withId($this->project_id)->build();
        $this->user    = $this->createMock(PFUser::class);
        $this->user->method('getUgroups')->with($this->project_id, null)->willReturn($user_ugroups);

        $this->ugroup = ProjectUGroupTestBuilder::aCustomUserGroup($ugroup_id)->build();
    }

    public function testItCallsDaoWithArguments(): void
    {
        $ugroups = [404, 416, 115];
        $this->dao->expects($this->once())->method('searchGerritRepositoriesWithPermissionsForUGroupAndProject')
            ->with($this->project_id, $ugroups)
            ->willReturn([]);

        $this->factory->getGerritRepositoriesWithPermissionsForUGroupAndProject($this->project, $this->ugroup, $this->user);
    }

    public function testItHydratesTheRepositoriesWithFactory(): void
    {
        $db_row_for_repo_12 = ['repository_id' => 12, 'permission_type' => Git::PERM_READ, 'ugroup_id' => 115];
        $db_row_for_repo_23 = ['repository_id' => 23, 'permission_type' => Git::PERM_READ, 'ugroup_id' => 115];

        $this->dao->method('searchGerritRepositoriesWithPermissionsForUGroupAndProject')->willReturn(TestHelper::arrayToDar($db_row_for_repo_12, $db_row_for_repo_23));
        $this->factory->method('instanciateFromRow')->willReturnCallback(static fn(array $row) => match ($row) {
            $db_row_for_repo_12, $db_row_for_repo_23 => GitRepositoryTestBuilder::aProjectRepository()->build(),
        });

        self::assertCount(2, $this->factory->getGerritRepositoriesWithPermissionsForUGroupAndProject($this->project, $this->ugroup, $this->user));
    }

    public function testItReturnsOneRepositoryWithOnePermission(): void
    {
        $this->dao->method('searchGerritRepositoriesWithPermissionsForUGroupAndProject')->willReturn(TestHelper::arrayToDar([
            'repository_id'   => 12,
            'permission_type' => Git::PERM_READ,
            'ugroup_id'       => 115,
        ]));

        $repository = GitRepositoryTestBuilder::aProjectRepository()->build();

        $this->factory->method('instanciateFromRow')->willReturn($repository);

        $git_with_permission = $this->factory->getGerritRepositoriesWithPermissionsForUGroupAndProject($this->project, $this->ugroup, $this->user);

        self::assertEquals([
            12 => new GitRepositoryWithPermissions(
                $repository,
                [
                    Git::PERM_READ          => [115],
                    Git::PERM_WRITE         => [],
                    Git::PERM_WPLUS         => [],
                    Git::SPECIAL_PERM_ADMIN => [],
                ]
            ),
        ], $git_with_permission);
    }

    public function testItReturnsOneRepositoryWithTwoPermissions(): void
    {
        $this->dao->method('searchGerritRepositoriesWithPermissionsForUGroupAndProject')->willReturn(TestHelper::arrayToDar([
            'repository_id'   => 12,
            'permission_type' => Git::PERM_READ,
            'ugroup_id'       => 115,
        ], [
            'repository_id'   => 12,
            'permission_type' => Git::PERM_WRITE,
            'ugroup_id'       => 115,
        ]));

        $repository = GitRepositoryTestBuilder::aProjectRepository()->build();

        $this->factory->method('instanciateFromRow')->willReturn($repository);

        $git_with_permission = $this->factory->getGerritRepositoriesWithPermissionsForUGroupAndProject($this->project, $this->ugroup, $this->user);

        self::assertEquals([
            12 => new GitRepositoryWithPermissions(
                $repository,
                [
                    Git::PERM_READ          => [115],
                    Git::PERM_WRITE         => [115],
                    Git::PERM_WPLUS         => [],
                    Git::SPECIAL_PERM_ADMIN => [],
                ]
            ),
        ], $git_with_permission);
    }

    public function testItReturnsOneRepositoryWithTwoGroupsForOnePermissionType(): void
    {
        $this->dao->method('searchGerritRepositoriesWithPermissionsForUGroupAndProject')->willReturn(TestHelper::arrayToDar([
            'repository_id'   => 12,
            'permission_type' => Git::PERM_READ,
            'ugroup_id'       => 115,
        ], [
            'repository_id'   => 12,
            'permission_type' => Git::PERM_READ,
            'ugroup_id'       => 120,
        ]));

        $repository = GitRepositoryTestBuilder::aProjectRepository()->build();

        $this->factory->method('instanciateFromRow')->willReturn($repository);

        $git_with_permission = $this->factory->getGerritRepositoriesWithPermissionsForUGroupAndProject($this->project, $this->ugroup, $this->user);

        self::assertEquals([
            12 => new GitRepositoryWithPermissions(
                $repository,
                [
                    Git::PERM_READ          => [115, 120],
                    Git::PERM_WRITE         => [],
                    Git::PERM_WPLUS         => [],
                    Git::SPECIAL_PERM_ADMIN => [],
                ]
            ),
        ], $git_with_permission);

        self::assertEquals([
            Git::PERM_READ          => [115, 120],
            Git::PERM_WRITE         => [],
            Git::PERM_WPLUS         => [],
            Git::SPECIAL_PERM_ADMIN => [],
        ], $git_with_permission[12]->getPermissions());
    }
}
