<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\TemporaryTestDirectory;

require_once 'bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class GitBackendTest extends TestCase
{
    use MockeryPHPUnitIntegration, TemporaryTestDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->http_request = \Mockery::spy(\HTTPRequest::class);
        HTTPRequest::setInstance($this->http_request);

        $this->fixturesPath = __DIR__ . '/_fixtures';

        $git_plugin = \Mockery::mock(GitPlugin::class);
        $git_plugin->shouldReceive('areFriendlyUrlsActivated')->andReturns();
        $this->url_manager = new Git_GitRepositoryUrlManager($git_plugin, new \Tuleap\InstanceBaseURLBuilder());
    }

    protected function tearDown(): void
    {
        @unlink($this->fixturesPath . '/tmp/hooks/post-receive');
        HTTPRequest::clearInstance();

        parent::tearDown();
    }

    public function testAddMailingShowRev(): void
    {
        $this->http_request->shouldReceive('getServerUrl')->andReturns('https://localhost');

        $prj = \Mockery::spy(\Project::class);
        $prj->shouldReceive('getId')->andReturns(1750);
        $prj->shouldReceive('getUnixName')->andReturns('prj');

        $repo = new GitRepository();
        $repo->setPath('prj/repo.git');
        $repo->setName('repo');
        $repo->setProject($prj);
        $repo->setId(290);

        $driver = \Mockery::spy(\GitDriver::class);
        $driver->shouldReceive('setConfig')->with('/var/lib/codendi/gitroot/prj/repo.git', 'hooks.showrev', "t=%s; git show --name-status --pretty='format:URL:    https://localhost/plugins/git/prj/repo?a=commitdiff&h=%%H%%nAuthor: %%an <%%ae>%%nDate:   %%aD%%n%%n%%s%%n%%b' \$t")->once();

        $backend = \Mockery::mock(\GitBackend::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $backend->setUp($this->url_manager);
        $backend->setGitRootPath(Git_Backend_Interface::GIT_ROOT_PATH);
        $backend->shouldReceive('getDriver')->andReturns($driver);

        $backend->setUpMailingHook($repo);
    }

    public function testArchiveCreatesATarGz(): void
    {
        $this->givenThereIsARepositorySetUp();

        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->andReturns('zorblub');

        $repo = \Mockery::spy(\GitRepository::class);
        $repo->shouldReceive('getPath')->andReturns('gitolite-admin-ref');
        $repo->shouldReceive('getName')->andReturns('gitolite-admin-ref');
        $repo->shouldReceive('getDeletionDate')->andReturns('2012-01-26');
        $repo->shouldReceive('getProject')->andReturns($project);
        $repo->shouldReceive('getBackupPath')->andReturns('gitolite-admin-ref-backup');

        $backend = \Mockery::mock(\GitBackend::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $backend->setGitRootPath($this->_tmpDir);
        $backend->setGitBackupDir($this->backupDir);
        $backend->archive($repo);

        $this->thenCleanTheWorkspace();
    }

    private function givenThereIsARepositorySetUp(): void
    {
        // Copy the reference to save time & create symlink because
        // git is very sensitive to path you are using. Just symlinking
        // spots bugs
        $this->cwd           = getcwd();
        $this->_tmpDir       = $this->getTmpDir();
        $this->_fixDir       = __DIR__ . '/_fixtures';
        $this->_glAdmDirRef  = $this->_tmpDir . '/gitolite-admin-ref';
        $this->backupDir     = $this->_tmpDir . '/backup';
        system('tar -xf ' . $this->_fixDir . '/gitolite-admin-ref' . '.tar --directory ' . $this->_tmpDir);
        mkdir($this->backupDir);
    }

    private function thenCleanTheWorkspace(): void
    {
        system('rm -rf ' . $this->_glAdmDirRef);
        system('rm -rf ' . $this->backupDir);
        chdir($this->cwd);
    }
}
