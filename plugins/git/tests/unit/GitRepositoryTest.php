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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\TemporaryTestDirectory;

require_once 'bootstrap.php';

class GitRepositoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use TemporaryTestDirectory;

    public function testDeletionPathShouldBeInProjectPath(): void
    {
        $tmp_folder = $this->getTmpDir() . '/perms';
        symlink(__DIR__ . '/_fixtures/perms', $tmp_folder);

        $repo = new GitRepository();
        $this->assertTrue($repo->isSubPath(__DIR__ . '/_fixtures/perms/', __DIR__ . '/_fixtures/perms/default.conf'));
        $this->assertTrue($repo->isSubPath(__DIR__ . '/_fixtures/perms/', $tmp_folder . '/default.conf'));
        $this->assertTrue($repo->isSubPath(__DIR__ . '/_fixtures/perms/', $tmp_folder . '/coincoin.git.git'));

        $this->assertFalse($repo->isSubPath(__DIR__ . '/_fixtures/perms/', __DIR__ . '/_fixtures/perms/../../default.conf'));
        $this->assertFalse($repo->isSubPath('_fixtures/perms/', 'coincoin'));

        unlink($tmp_folder);
    }

    public function testDeletionShouldAffectDotGit(): void
    {
        $repo = new GitRepository();
        $this->assertTrue($repo->isDotGit('default.git'));
        $this->assertTrue($repo->isDotGit('default.git.git'));

        $this->assertFalse($repo->isDotGit('default.conf'));
        $this->assertFalse($repo->isDotGit('d'));
        $this->assertFalse($repo->isDotGit('defaultgit'));
        $this->assertFalse($repo->isDotGit('default.git.old'));
    }

    public function testGetRepositoryIDByNameSuccess(): void
    {
        $repo = \Mockery::mock(\GitRepository::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $pm = \Mockery::spy(\ProjectManager::class);
        $project = \Mockery::spy(\Project::class);
        $pm->shouldReceive('getProjectByUnixName')->andReturns($project);
        $dao = \Mockery::mock(GitDao::class);
        $repo->shouldReceive('getDao')->andReturns($dao);
        $dao->shouldReceive('getProjectRepositoryByName')->andReturn(['repository_id' => 48])->once();

        $repo->shouldReceive('_getProjectManager')->once()->andReturns($pm);
        $project->shouldReceive('getID')->once();

        $this->assertEquals(48, $repo->getRepositoryIDByName('repo', 'prj'));
    }

    public function testGetRepositoryIDByNameNoRepository(): void
    {
        $repo = \Mockery::mock(\GitRepository::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $pm = \Mockery::spy(\ProjectManager::class);
        $project = \Mockery::spy(\Project::class);
        $pm->shouldReceive('getProjectByUnixName')->andReturns($project);
        $dao = \Mockery::mock(GitDao::class);
        $repo->shouldReceive('getDao')->andReturns($dao);
        $dao->shouldReceive('getProjectRepositoryByName')->andReturnFalse()->once();

        $repo->shouldReceive('_getProjectManager')->once()->andReturns($pm);
        $project->shouldReceive('getID')->once();

        $this->assertEquals(0, $repo->getRepositoryIDByName('repo', 'prj'));
    }

    public function testGetRepositoryIDByNameNoProjectID(): void
    {
        $repo = \Mockery::mock(\GitRepository::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $pm = \Mockery::spy(\ProjectManager::class);
        $project = \Mockery::spy(\Project::class);
        $pm->shouldReceive('getProjectByUnixName')->andReturns(false);

        $repo->shouldReceive('_getProjectManager')->once()->andReturns($pm);
        $project->shouldReceive('getID')->never();

        $this->assertSame(0, $repo->getRepositoryIDByName('repo', 'prj'));
    }

    public function testGetFullNameAppendsNameSpaceToName(): void
    {
        $repo = $this->givenARepositoryWithNameAndNamespace('tulip', null);
        $this->assertEquals('tulip', $repo->getFullName());

        $repo = $this->givenARepositoryWithNameAndNamespace('tulip', 'u/johan');
        $this->assertEquals('u/johan/tulip', $repo->getFullName());
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
        $user = new PFUser(array('language_id' => 1));
        $user->setUserName('sandra');

        $repo = new GitRepository();
        $repo->setCreator($user);
        $repo->setScope(GitRepository::REPO_SCOPE_PROJECT);

        $this->assertFalse($repo->belongsTo($user));
    }

    public function testUserRepositoryBelongsToUser(): void
    {
        $user = new PFUser(array('language_id' => 1));
        $user->setUserName('sandra');

        $repo = new GitRepository();
        $repo->setCreator($user);
        $repo->setScope(GitRepository::REPO_SCOPE_INDIVIDUAL);

        $this->assertTrue($repo->belongsTo($user));
    }
    public function testUserRepositoryDoesNotBelongToAnotherUser(): void
    {
        $creator = new PFUser(array('language_id' => 1));
        $creator->setId(123);

        $user = new PFUser(array('language_id' => 1));
        $user->setId(456);

        $repo = new GitRepository();
        $repo->setCreator($creator);
        $repo->setScope(GitRepository::REPO_SCOPE_INDIVIDUAL);

        $this->assertFalse($repo->belongsTo($user));
    }

    public function testItIsMigratableIfItIsAGitoliteRepo(): void
    {
        $repo = new GitRepository();
        $repo->setBackendType(GitDao::BACKEND_GITOLITE);
        $this->assertTrue($repo->canMigrateToGerrit());
    }

    public function testItIsNotMigratableIfItIsAGitshellRepo(): void
    {
        $repo = new GitRepository();
        $repo->setBackendType(GitDao::BACKEND_GITSHELL);
        $this->assertFalse($repo->canMigrateToGerrit());
    }

    public function testItIsNotMigratableIfAlreadyAGerritRepo(): void
    {
        $repo = new GitRepository();
        $repo->setBackendType(GitDao::BACKEND_GITOLITE);
        $repo->setRemoteServerId(34);
        $this->assertFalse($repo->canMigrateToGerrit());
    }

    public function testItIsMigratableIfItHasAlreadyBeenAGerritRepoInThePastAndRemoteProjectIsNotDeleted(): void
    {
        $repo = new GitRepository();
        $repo->setBackendType(GitDao::BACKEND_GITOLITE);
        $repo->setRemoteServerDisconnectDate(12345677890);
        $repo->setRemoteProjectDeletionDate(null);
        $repo->setRemoteServerId(4154);
        $this->assertTrue($repo->canMigrateToGerrit());
    }

    public function testItIsMigratableIfItHasAlreadyBeenAGerritRepoInThePastAndRemoteProjectIsDeleted(): void
    {
        $repo = new GitRepository();
        $repo->setBackendType(GitDao::BACKEND_GITOLITE);
        $repo->setRemoteServerDisconnectDate(12345677890);
        $repo->setRemoteProjectDeletionDate(12345677890);
        $repo->setRemoteServerId(4154);
        $this->assertTrue($repo->canMigrateToGerrit());
    }

    public function testItIsNotMigratedIfItWasDisconnected(): void
    {
        $repository = new GitRepository();
        $repository->setDeletionDate(null);
        $repository->setRemoteServerDisconnectDate(12345677890);
        $repository->setRemoteServerId(1);

        $this->assertFalse($repository->isMigratedToGerrit());
    }
}
