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

namespace Tuleap\Git\Gitolite;

use EventManager;
use Git;
use Git_Gitolite_ConfigPermissionsSerializer;
use Git_Gitolite_GitoliteConfWriter;
use Git_Gitolite_GitoliteRCReader;
use Git_Gitolite_ProjectSerializer;
use Git_GitoliteDriver;
use GitDao;
use GitPlugin;
use Mockery;
use Project;
use ProjectManager;

final class GitoliteDriverTest extends GitoliteTestCase
{
    /** @var Git_Gitolite_GitoliteRCReader */
    private $gitoliterc_reader;

    /** @var Git_Gitolite_ConfigPermissionsSerializer */
    private $another_gitolite_permissions_serializer;

    /** @var Git_GitoliteDriver */
    private $a_gitolite_driver;

    /** @var Git_GitoliteDriver */
    private $another_gitolite_driver;

    /** @var Git_Gitolite_GitoliteConfWriter */
    private $gitolite_conf_writer;

    /** @var Git */
    private $another_git_exec;

    /** @var Git_Gitolite_ProjectSerializer */
    private $a_gitolite_project_serializer;

    /** @var ProjectManager */
    private $project_manager;


    protected function setUp(): void
    {
        parent::setUp();

        \ForgeConfig::set('codendi_cache_dir', $this->getTmpDir() . '/cache');

        $this->project_manager   = \Mockery::spy(\ProjectManager::class);
        $this->gitoliterc_reader = \Mockery::spy(\Git_Gitolite_GitoliteRCReader::class);

        $this->another_gitolite_permissions_serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            \Mockery::spy(\Git_Driver_Gerrit_ProjectCreatorStatus::class),
            'whatever',
            \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedRetriever::class),
            \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPermissionFactory::class),
            \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedRetriever::class),
            \Mockery::spy(EventManager::class)
        );

        $this->a_gitolite_project_serializer = new Git_Gitolite_ProjectSerializer(
            $this->logger,
            $this->repository_factory,
            $this->another_gitolite_permissions_serializer,
            $this->url_manager,
            \Mockery::spy(\Tuleap\Git\BigObjectAuthorization\BigObjectAuthorizationManager::class),
        );

        $this->gitolite_conf_writer = new Git_Gitolite_GitoliteConfWriter(
            $this->another_gitolite_permissions_serializer,
            $this->a_gitolite_project_serializer,
            $this->gitoliterc_reader,
            \Mockery::spy(\Psr\Log\LoggerInterface::class),
            $this->project_manager,
            $this->sys_data_dir . '/gitolite/admin'
        );

        $this->a_gitolite_driver = new Git_GitoliteDriver(
            $this->logger,
            $this->url_manager,
            \Mockery::mock(GitDao::class),
            \Mockery::mock(GitPlugin::class),
            \Mockery::spy(\Tuleap\Git\BigObjectAuthorization\BigObjectAuthorizationManager::class),
            $this->git_exec,
            $this->repository_factory,
            $this->another_gitolite_permissions_serializer,
            $this->gitolite_conf_writer,
            $this->project_manager,
        );

        $this->another_git_exec = \Mockery::spy(\Git_Exec::class);

        $this->another_gitolite_driver = new Git_GitoliteDriver(
            $this->logger,
            $this->url_manager,
            \Mockery::mock(GitDao::class),
            \Mockery::mock(GitPlugin::class),
            \Mockery::spy(\Tuleap\Git\BigObjectAuthorization\BigObjectAuthorizationManager::class),
            $this->another_git_exec,
            $this->repository_factory,
            $this->another_gitolite_permissions_serializer,
            $this->gitolite_conf_writer,
            $this->project_manager,
        );
    }

    public function testGitoliteConfUpdate(): void
    {
        $this->gitoliterc_reader->shouldReceive('getHostname')->andReturns(null);

        touch($this->gitolite_admin_dir . '/conf/projects/project1.conf');

        $this->another_gitolite_driver->updateMainConfIncludes();

        $gitoliteConf = $this->getGitoliteConf();

        $this->assertMatchesRegularExpression('#^include "projects/project1.conf"$#m', $gitoliteConf);
    }

    protected function getGitoliteConf()
    {
        return file_get_contents($this->gitolite_admin_dir . '/conf/gitolite.conf');
    }

    protected function getFileConf($filename)
    {
        return file_get_contents($this->gitolite_admin_dir . '/conf/' . $filename . '.conf');
    }

    public function testItCanRenameProject(): void
    {
        $new_name = 'newone';
        $this->project_manager->shouldReceive('getProjectByUnixName')->with($new_name)->andReturns(Mockery::mock(Project::class)->shouldReceive('getUnixName')->andReturn($new_name)->getMock());
        $this->git_exec->shouldReceive('push')->andReturn(true)->once();

        $this->assertTrue(is_file($this->gitolite_admin_dir . '/conf/projects/legacy.conf'));
        $this->assertFalse(is_file($this->gitolite_admin_dir . '/conf/projects/newone.conf'));

        $this->assertTrue($this->a_gitolite_driver->renameProject('legacy', $new_name));

        clearstatcache(true, $this->gitolite_admin_dir . '/conf/projects/legacy.conf');
        $this->assertFalse(is_file($this->gitolite_admin_dir . '/conf/projects/legacy.conf'));
        $this->assertTrue(is_file($this->gitolite_admin_dir . '/conf/projects/newone.conf'));
        $this->assertFileEquals(
            $this->fixtures_dir . '/perms/newone.conf',
            $this->gitolite_admin_dir . '/conf/projects/newone.conf'
        );
        $this->assertDoesNotMatchRegularExpression('`\ninclude "projects/legacy.conf"\n`', $this->getGitoliteConf());
        $this->assertMatchesRegularExpression('`\ninclude "projects/newone.conf"\n`', $this->getGitoliteConf());
        $this->assertEmptyGitStatus();
    }

    public function testItLogsEverytimeItPushes(): void
    {
        $this->git_exec->shouldReceive('push')->andReturn(true);
        $this->logger->shouldReceive('debug')->times(2);

        $this->driver->push();
    }

    public function testItOnlyIncludeHOSTNAMERelatedConfFileIfHOSTNAMEVariableIsSetInGitoliteRcFile(): void
    {
        $this->gitoliterc_reader->shouldReceive('getHostname')->andReturns("master");

        touch($this->gitolite_admin_dir . '/conf/projects/project1.conf');

        $this->another_gitolite_driver->updateMainConfIncludes();

        $gitoliteConf = $this->getGitoliteConf();

        $this->assertMatchesRegularExpression('#^include "%HOSTNAME.conf"$#m', $gitoliteConf);
        $this->assertDoesNotMatchRegularExpression('#^include "projects/project1.conf"$#m', $gitoliteConf);
    }

    public function testItWritesTheGitoliteConfFileInTheHOSTNAMEDotConfFileIfHostnameVariableIsSet(): void
    {
        $hostname = "master";
        $this->gitoliterc_reader->shouldReceive('getHostname')->andReturns($hostname);

        touch($this->gitolite_admin_dir . '/conf/projects/project1.conf');

        $this->another_gitolite_driver->updateMainConfIncludes();

        $gitoliteConf = $this->getFileConf($hostname);
        $this->assertMatchesRegularExpression('#^include "projects/project1.conf"$#m', $gitoliteConf);
    }

    public function testItAddsAllTheRequiredFilesForPush(): void
    {
        $hostname = "master";

        $this->gitoliterc_reader->shouldReceive('getHostname')->andReturns($hostname);

        touch($this->gitolite_admin_dir . '/conf/projects/project1.conf');

        $this->another_git_exec->shouldReceive('add')->with('conf/gitolite.conf')->andReturn(true)->once();
        $this->another_git_exec->shouldReceive('add')->with('conf/master.conf')->andReturn(true)->once();

        $this->another_gitolite_driver->updateMainConfIncludes();
    }

    public function testItIsInitializedEvenIfThereIsNoMaster(): void
    {
        $this->assertTrue($this->driver->isInitialized($this->fixtures_dir . '/headless.git'));
    }

    public function testItIsNotInitializedIfThereIsNoValidDirectory(): void
    {
        $this->assertFalse($this->driver->isInitialized($this->fixtures_dir));
    }
}
