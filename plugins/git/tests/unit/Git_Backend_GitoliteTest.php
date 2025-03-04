<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

namespace Tuleap\Git;

use Backend;
use Git;
use Git_Backend_Gitolite;
use Git_Backend_Interface;
use Git_GitoliteDriver;
use GitDao;
use GitRepository;
use GitRepositoryAlreadyExistsException;
use PermissionsManager;
use Psr\Log\NullLogger;
use Tuleap\Git\Gitolite\GitoliteAccessURLGenerator;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Git\Tests\Stub\DefaultBranch\DefaultBranchUpdateExecutorStub;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Git_Backend_GitoliteTest extends TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    use TemporaryTestDirectory;

    private string $fixtureRenamePath;
    private array $forkPermissions;

    public function setUp(): void
    {
        $this->fixtureRenamePath = $this->getTmpDir() . '/rename';

        mkdir($this->fixtureRenamePath . '/legacy', 0770, true);

        $this->forkPermissions = [];
    }

    public function testRenameProjectOk(): void
    {
        $project = ProjectTestBuilder::aProject()->withUnixName('legacy')->build();

        $backend = $this->createPartialMock(Git_Backend_Gitolite::class, ['getBackend', 'glRenameProject']);

        $driver = $this->createMock(Git_GitoliteDriver::class);
        $driver->method('getRepositoriesPath')->willReturn($this->fixtureRenamePath);
        $backend->setDriver($driver);

        $bck = $this->createMock(Backend::class);
        $bck->expects(self::never())->method('log');
        $backend->method('getBackend')->willReturn($bck);

        self::assertTrue(is_dir($this->fixtureRenamePath . '/legacy'));
        self::assertFalse(is_dir($this->fixtureRenamePath . '/newone'));

        $backend->expects(self::once())->method('glRenameProject')->with('legacy', 'newone');
        self::assertTrue($backend->renameProject($project, 'newone'));

        clearstatcache(true, $this->fixtureRenamePath . '/legacy');
        self::assertFalse(is_dir($this->fixtureRenamePath . '/legacy'));
        self::assertTrue(is_dir($this->fixtureRenamePath . '/newone'));
    }

    public function testItSavesForkInfoIntoDB(): void
    {
        $name          = 'tuleap';
        $old_namespace = '';
        $new_namespace = 'u/johanm/ericsson';
        $new_repo_path = "gpig/$new_namespace/$name.git";

        $project = ProjectTestBuilder::aProject()->build();

        $new_repo = $this->givenAGitRepoWithNameAndNamespace($name, $new_namespace);
        $new_repo->setProject($project);
        $new_repo->setPath($new_repo_path);
        $old_repo = $this->givenAGitRepoWithNameAndNamespace($name, $old_namespace);
        $old_repo->setProject($project);

        $backend = $this->createPartialMock(Git_Backend_Gitolite::class, ['clonePermissions']);
        $dao     = $this->createMock(GitDao::class);
        $backend->setDao($dao);

        $backend->expects(self::once())->method('clonePermissions')->with($old_repo, $new_repo);
        $dao->expects(self::once())->method('save')->with($new_repo)->willReturn(667);
        $dao->method('isRepositoryExisting')->with(self::anything(), $new_repo_path)->willReturn(false);

        self::assertEquals(667, $backend->fork($old_repo, $new_repo, $this->forkPermissions));
    }

    public function testForkClonesRepository(): void
    {
        $name          = 'tuleap';
        $old_namespace = '';
        $new_namespace = 'u/johanm/ericsson';
        $new_repo_path = "gpig/$new_namespace/$name.git";

        $driver  = $this->createMock(Git_GitoliteDriver::class);
        $project = ProjectTestBuilder::aProject()->withUnixName('gpig')->build();

        $new_repo = $this->givenAGitRepoWithNameAndNamespace($name, $new_namespace);
        $new_repo->setProject($project);
        $new_repo->setPath($new_repo_path);
        $old_repo = $this->givenAGitRepoWithNameAndNamespace($name, $old_namespace);
        $old_repo->setProject($project);

        $backend = $this->getMockBuilder(Git_Backend_Gitolite::class)
            ->setConstructorArgs([
                $driver,
                $this->createMock(GitoliteAccessURLGenerator::class),
                new DefaultBranchUpdateExecutorStub(),
                new NullLogger(),
            ])
            ->onlyMethods([])
            ->getMock();

        $driver->expects(self::once())->method('fork')->with($name, 'gpig/' . $old_namespace, 'gpig/' . $new_namespace)->willReturn(true);
        $driver->expects(self::once())->method('dumpProjectRepoConf')->with($project);
        $driver->expects(self::never())->method('push');

        $backend->forkOnFilesystem($old_repo, $new_repo);
    }

    public function testForkClonesRepositoryFromOneProjectToAnotherSucceed(): void
    {
        $repo_name        = 'tuleap';
        $old_project_name = 'garden';
        $new_project_name = 'gpig';
        $namespace        = '';
        $new_repo_path    = "$new_project_name/$namespace/$repo_name.git";

        $driver = $this->createMock(Git_GitoliteDriver::class);

        $new_project = ProjectTestBuilder::aProject()->withUnixName($new_project_name)->build();
        $old_project = ProjectTestBuilder::aProject()->withUnixName('garden')->build();

        $new_repo = $this->givenAGitRepoWithNameAndNamespace($repo_name, $namespace);
        $new_repo->setProject($new_project);
        $new_repo->setPath($new_repo_path);
        $old_repo = $this->givenAGitRepoWithNameAndNamespace($repo_name, $namespace);
        $old_repo->setProject($old_project);

        $backend = $this->getMockBuilder(Git_Backend_Gitolite::class)
            ->setConstructorArgs([
                $driver,
                $this->createMock(GitoliteAccessURLGenerator::class),
                new DefaultBranchUpdateExecutorStub(),
                new NullLogger(),
            ])
            ->onlyMethods([])
            ->getMock();

        $driver->expects(self::once())->method('fork')->with($repo_name, $old_project_name . '/' . $namespace, $new_project_name . '/' . $namespace)->willReturn(true);
        $driver->expects(self::once())->method('dumpProjectRepoConf')->with($new_project);
        $driver->expects(self::never())->method('push');

        $backend->forkOnFilesystem($old_repo, $new_repo);
    }

    public function testForkWithTargetPathAlreadyExistingShouldNotFork(): void
    {
        $name          = 'tuleap';
        $old_namespace = '';
        $new_namespace = 'u/johanm/ericsson';
        $new_repo_path = "gpig/$new_namespace/$name.git";

        $driver = $this->createMock(Git_GitoliteDriver::class);
        $dao    = $this->createMock(GitDao::class);

        $new_repo = $this->givenAGitRepoWithNameAndNamespace($name, $new_namespace);
        $new_repo->setPath($new_repo_path);
        $project_id = $new_repo->getProject()->getId();

        $old_repo = $this->givenAGitRepoWithNameAndNamespace($name, $old_namespace);

        $backend = $this->getMockBuilder(Git_Backend_Gitolite::class)
            ->setConstructorArgs([
                $driver,
                $this->createMock(GitoliteAccessURLGenerator::class),
                new DefaultBranchUpdateExecutorStub(),
                new NullLogger(),
            ])
            ->onlyMethods(['clonePermissions'])
            ->getMock();

        $backend->setDao($dao);

        $this->expectException(GitRepositoryAlreadyExistsException::class);

        $backend->expects(self::never())->method('clonePermissions');
        $dao->method('save');
        $dao->method('isRepositoryExisting')->with($project_id, $new_repo_path)->willReturn(true);
        $driver->expects(self::never())->method('fork');
        $driver->expects(self::never())->method('dumpProjectRepoConf');
        $driver->expects(self::never())->method('push');

        $backend->fork($old_repo, $new_repo, $this->forkPermissions);
    }

    public function testClonePermsWithPersonalFork(): void
    {
        $old_repo_id = 110;
        $new_repo_id = 220;

        $project = ProjectTestBuilder::aProject()->build();

        $old = GitRepositoryTestBuilder::aProjectRepository()->withId($old_repo_id)->inProject($project)->build();
        $new = GitRepositoryTestBuilder::aProjectRepository()->withId($new_repo_id)->inProject($project)->build();

        $backend = $this->givenABackendGitolite();

        $permissions_manager = $backend->getPermissionsManager();
        $permissions_manager->expects(self::once())->method('duplicateWithStatic')->with($old_repo_id, $new_repo_id, Git::allPermissionTypes());

        $backend->clonePermissions($old, $new);
    }

    public function testIsNameValid(): void
    {
        self::assertTrue($this->givenABackendGitolite()->isNameValid('lerepo'));
        self::assertTrue($this->givenABackendGitolite()->isNameValid('le_repo/repo'));
        self::assertTrue($this->givenABackendGitolite()->isNameValid('le_repo65'));
        self::assertTrue($this->givenABackendGitolite()->isNameValid('le_repo_git'));
        self::assertTrue($this->givenABackendGitolite()->isNameValid('lerepo.gitea'));
    }

    public function testIsNameValidReturnFalseIfARepositoryNameEndWithPointGit(): void
    {
        self::assertFalse($this->givenABackendGitolite()->isNameValid('lerepo.git'));
        self::assertFalse($this->givenABackendGitolite()->isNameValid('le_repo.git/repo'));
        self::assertFalse($this->givenABackendGitolite()->isNameValid('le_repo65/repo.git'));
    }

    public function testClonePermsCrossProjectFork(): void
    {
        $old_repo_id = 110;
        $old_project = ProjectTestBuilder::aProject()->withId(1)->build();

        $new_repo_id = 220;
        $new_project = ProjectTestBuilder::aProject()->withId(2)->build();

        $old = GitRepositoryTestBuilder::aProjectRepository()->withId($old_repo_id)->inProject($old_project)->build();
        $new = GitRepositoryTestBuilder::aProjectRepository()->withId($new_repo_id)->inProject($new_project)->build();

        $backend = $this->givenABackendGitolite();

        $permissionsManager = $backend->getPermissionsManager();
        $permissionsManager->expects(self::once())->method('duplicateWithoutStatic')->with($old_repo_id, $new_repo_id, Git::allPermissionTypes());

        $backend->clonePermissions($old, $new);
    }

    private function givenAGitRepoWithNameAndNamespace($name, $namespace): GitRepository
    {
        $repository = new GitRepository();
        $repository->setName($name);
        $repository->setNamespace($namespace);
        $backend_interface = $this->createStub(Git_Backend_Interface::class);
        $backend_interface->method('getGitRootPath')->willReturn('/some_path');
        $repository->setBackend($backend_interface);

        $project = ProjectTestBuilder::aProject()->withUnixName('gpig')->withId(123)->build();
        $repository->setProject($project);

        return $repository;
    }

    private function givenABackendGitolite(): Git_Backend_Gitolite
    {
        $driver             = $this->createMock(Git_GitoliteDriver::class);
        $dao                = $this->createMock(GitDao::class);
        $permissionsManager = $this->createMock(PermissionsManager::class);
        $backend            = new Git_Backend_Gitolite($driver, $this->createMock(GitoliteAccessURLGenerator::class), new DefaultBranchUpdateExecutorStub(), new NullLogger());
        $backend->setDao($dao);
        $backend->setPermissionsManager($permissionsManager);
        return $backend;
    }
}
