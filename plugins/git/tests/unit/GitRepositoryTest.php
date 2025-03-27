<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Git;

use GitDao;
use GitRepository;
use PFUser;
use ProjectManager;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitRepositoryTest extends TestCase
{
    use TemporaryTestDirectory;

    public function testDeletionPathShouldBeInProjectPath(): void
    {
        $tmp_folder = $this->getTmpDir() . '/perms';
        symlink(__DIR__ . '/_fixtures/perms', $tmp_folder);

        $repo = new GitRepository();
        self::assertTrue($repo->isSubPath(__DIR__ . '/_fixtures/perms/', __DIR__ . '/_fixtures/perms/default.conf'));
        self::assertTrue($repo->isSubPath(__DIR__ . '/_fixtures/perms/', $tmp_folder . '/default.conf'));
        self::assertTrue($repo->isSubPath(__DIR__ . '/_fixtures/perms/', $tmp_folder . '/coincoin.git.git'));

        self::assertFalse($repo->isSubPath(__DIR__ . '/_fixtures/perms/', __DIR__ . '/_fixtures/perms/../../default.conf'));
        self::assertFalse($repo->isSubPath('_fixtures/perms/', 'coincoin'));

        unlink($tmp_folder);
    }

    public function testDeletionShouldAffectDotGit(): void
    {
        $repo = new GitRepository();
        self::assertTrue($repo->isDotGit('default.git'));
        self::assertTrue($repo->isDotGit('default.git.git'));

        self::assertFalse($repo->isDotGit('default.conf'));
        self::assertFalse($repo->isDotGit('d'));
        self::assertFalse($repo->isDotGit('defaultgit'));
        self::assertFalse($repo->isDotGit('default.git.old'));
    }

    public function testGetRepositoryIDByNameSuccess(): void
    {
        $repo    = $this->createPartialMock(GitRepository::class, ['getDao', '_getProjectManager']);
        $pm      = $this->createMock(ProjectManager::class);
        $project = ProjectTestBuilder::aProject()->build();
        $pm->method('getProjectByUnixName')->willReturn($project);
        $dao = $this->createMock(GitDao::class);
        $repo->method('getDao')->willReturn($dao);
        $dao->expects($this->once())->method('getProjectRepositoryByName')->willReturn(['repository_id' => 48]);

        $repo->expects($this->once())->method('_getProjectManager')->willReturn($pm);

        self::assertEquals(48, $repo->getRepositoryIDByName('repo', 'prj'));
    }

    public function testGetRepositoryIDByNameNoRepository(): void
    {
        $repo    = $this->createPartialMock(GitRepository::class, ['getDao', '_getProjectManager']);
        $pm      = $this->createMock(ProjectManager::class);
        $project = ProjectTestBuilder::aProject()->build();
        $pm->method('getProjectByUnixName')->willReturn($project);
        $dao = $this->createMock(GitDao::class);
        $repo->method('getDao')->willReturn($dao);
        $dao->expects($this->once())->method('getProjectRepositoryByName')->willReturn(false);

        $repo->expects($this->once())->method('_getProjectManager')->willReturn($pm);

        self::assertEquals(0, $repo->getRepositoryIDByName('repo', 'prj'));
    }

    public function testGetRepositoryIDByNameNoProjectID(): void
    {
        $repo = $this->createPartialMock(GitRepository::class, ['_getProjectManager']);
        $pm   = $this->createMock(ProjectManager::class);
        $pm->method('getProjectByUnixName')->willReturn(false);

        $repo->expects($this->once())->method('_getProjectManager')->willReturn($pm);

        self::assertSame(0, $repo->getRepositoryIDByName('repo', 'prj'));
    }

    public function testGetFullNameAppendsNameSpaceToName(): void
    {
        $repo = $this->givenARepositoryWithNameAndNamespace('tulip', null);
        self::assertEquals('tulip', $repo->getFullName());

        $repo = $this->givenARepositoryWithNameAndNamespace('tulip', 'u/johan');
        self::assertEquals('u/johan/tulip', $repo->getFullName());
    }

    private function givenARepositoryWithNameAndNamespace($name, $namespace): GitRepository
    {
        $repo = new GitRepository();
        $repo->setName($name);
        $repo->setNamespace($namespace);
        return $repo;
    }

    public function testProjectRepositoryDosNotBelongToUser(): void
    {
        $user = new PFUser(['language_id' => 1]);
        $user->setUserName('sandra');

        $repo = new GitRepository();
        $repo->setCreator($user);
        $repo->setScope(GitRepository::REPO_SCOPE_PROJECT);

        self::assertFalse($repo->belongsTo($user));
    }

    public function testUserRepositoryBelongsToUser(): void
    {
        $user = new PFUser(['language_id' => 1]);
        $user->setUserName('sandra');

        $repo = new GitRepository();
        $repo->setCreator($user);
        $repo->setScope(GitRepository::REPO_SCOPE_INDIVIDUAL);

        self::assertTrue($repo->belongsTo($user));
    }

    public function testUserRepositoryDoesNotBelongToAnotherUser(): void
    {
        $creator = new PFUser(['language_id' => 1]);
        $creator->setId(123);

        $user = new PFUser(['language_id' => 1]);
        $user->setId(456);

        $repo = new GitRepository();
        $repo->setCreator($creator);
        $repo->setScope(GitRepository::REPO_SCOPE_INDIVIDUAL);

        self::assertFalse($repo->belongsTo($user));
    }

    public function testItIsMigratableIfItIsAGitoliteRepo(): void
    {
        $repo = new GitRepository();
        $repo->setBackendType(GitDao::BACKEND_GITOLITE);
        self::assertTrue($repo->canMigrateToGerrit());
    }

    public function testItIsNotMigratableIfAlreadyAGerritRepo(): void
    {
        $repo = new GitRepository();
        $repo->setBackendType(GitDao::BACKEND_GITOLITE);
        $repo->setRemoteServerId(34);
        self::assertFalse($repo->canMigrateToGerrit());
    }

    public function testItIsMigratableIfItHasAlreadyBeenAGerritRepoInThePastAndRemoteProjectIsNotDeleted(): void
    {
        $repo = new GitRepository();
        $repo->setBackendType(GitDao::BACKEND_GITOLITE);
        $repo->setRemoteServerDisconnectDate(12345677890);
        $repo->setRemoteProjectDeletionDate(null);
        $repo->setRemoteServerId(4154);
        self::assertTrue($repo->canMigrateToGerrit());
    }

    public function testItIsMigratableIfItHasAlreadyBeenAGerritRepoInThePastAndRemoteProjectIsDeleted(): void
    {
        $repo = new GitRepository();
        $repo->setBackendType(GitDao::BACKEND_GITOLITE);
        $repo->setRemoteServerDisconnectDate(12345677890);
        $repo->setRemoteProjectDeletionDate(12345677890);
        $repo->setRemoteServerId(4154);
        self::assertTrue($repo->canMigrateToGerrit());
    }

    public function testItIsNotMigratedIfItWasDisconnected(): void
    {
        $repository = new GitRepository();
        $repository->setDeletionDate(null);
        $repository->setRemoteServerDisconnectDate(12345677890);
        $repository->setRemoteServerId(1);

        self::assertFalse($repository->isMigratedToGerrit());
    }
}
