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
use ForgeConfig;
use Git_Driver_Gerrit_ProjectCreatorStatus;
use Git_Exec;
use Git_Gitolite_ConfigPermissionsSerializer;
use Git_Gitolite_GitoliteConfWriter;
use Git_Gitolite_GitoliteRCReader;
use Git_Gitolite_ProjectSerializer;
use Git_GitoliteDriver;
use GitDao;
use GitPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectManager;
use Psr\Log\NullLogger;
use Tuleap\Git\BigObjectAuthorization\BigObjectAuthorizationManager;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class GitoliteDriverTest extends GitoliteTestCase
{
    private Git_Gitolite_GitoliteRCReader&MockObject $gitoliterc_reader;
    private Git_GitoliteDriver $a_gitolite_driver;
    private Git_GitoliteDriver $another_gitolite_driver;
    private Git_Exec&MockObject $another_git_exec;
    private ProjectManager&MockObject $project_manager;

    protected function setUp(): void
    {
        parent::setUp();

        ForgeConfig::set('codendi_cache_dir', $this->getTmpDir() . '/cache');

        $this->project_manager   = $this->createMock(ProjectManager::class);
        $this->gitoliterc_reader = $this->createMock(Git_Gitolite_GitoliteRCReader::class);

        $another_gitolite_permissions_serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            $this->createMock(Git_Driver_Gerrit_ProjectCreatorStatus::class),
            'whatever',
            $this->createMock(FineGrainedRetriever::class),
            $this->createMock(FineGrainedPermissionFactory::class),
            $this->createMock(RegexpFineGrainedRetriever::class),
            $this->createMock(EventManager::class)
        );

        $big_object_authorization_manager = $this->createMock(BigObjectAuthorizationManager::class);
        $a_gitolite_project_serializer    = new Git_Gitolite_ProjectSerializer(
            $this->logger,
            $this->repository_factory,
            $another_gitolite_permissions_serializer,
            $this->url_manager,
            $big_object_authorization_manager,
        );

        $gitolite_conf_writer = new Git_Gitolite_GitoliteConfWriter(
            $another_gitolite_permissions_serializer,
            $a_gitolite_project_serializer,
            $this->gitoliterc_reader,
            new NullLogger(),
            $this->project_manager,
            $this->sys_data_dir . '/gitolite/admin'
        );

        $git_dao                 = $this->createMock(GitDao::class);
        $git_plugin              = $this->createMock(GitPlugin::class);
        $this->a_gitolite_driver = new Git_GitoliteDriver(
            $this->logger,
            $this->url_manager,
            $git_dao,
            $git_plugin,
            $big_object_authorization_manager,
            $this->git_exec,
            $this->repository_factory,
            $another_gitolite_permissions_serializer,
            $gitolite_conf_writer,
            $this->project_manager,
        );

        $this->another_git_exec = $this->createMock(Git_Exec::class);

        $this->another_gitolite_driver = new Git_GitoliteDriver(
            $this->logger,
            $this->url_manager,
            $git_dao,
            $git_plugin,
            $big_object_authorization_manager,
            $this->another_git_exec,
            $this->repository_factory,
            $another_gitolite_permissions_serializer,
            $gitolite_conf_writer,
            $this->project_manager,
        );
    }

    public function testGitoliteConfUpdate(): void
    {
        $this->gitoliterc_reader->method('getHostname')->willReturn(null);
        $this->another_git_exec->method('add');

        touch($this->gitolite_admin_dir . '/conf/projects/project1.conf');

        $this->another_gitolite_driver->updateMainConfIncludes();

        $gitoliteConf = $this->getGitoliteConf();

        self::assertMatchesRegularExpression('#^include "projects/project1.conf"$#m', $gitoliteConf);
    }

    private function getGitoliteConf(): string
    {
        return file_get_contents($this->gitolite_admin_dir . '/conf/gitolite.conf');
    }

    private function getFileConf($filename): string
    {
        return file_get_contents($this->gitolite_admin_dir . '/conf/' . $filename . '.conf');
    }

    public function testItCanRenameProject(): void
    {
        $new_name = 'newone';
        $this->project_manager->method('getProjectByUnixName')->with($new_name)->willReturn(ProjectTestBuilder::aProject()->withUnixName($new_name)->build());
        $this->git_exec->expects(self::once())->method('push')->willReturn(true);
        $this->gitoliterc_reader->method('getHostname');

        self::assertTrue(is_file($this->gitolite_admin_dir . '/conf/projects/legacy.conf'));
        self::assertFalse(is_file($this->gitolite_admin_dir . '/conf/projects/newone.conf'));

        $this->assertTrue($this->a_gitolite_driver->renameProject('legacy', $new_name));

        clearstatcache(true, $this->gitolite_admin_dir . '/conf/projects/legacy.conf');
        self::assertFalse(is_file($this->gitolite_admin_dir . '/conf/projects/legacy.conf'));
        self::assertTrue(is_file($this->gitolite_admin_dir . '/conf/projects/newone.conf'));
        self::assertFileEquals(
            $this->fixtures_dir . '/perms/newone.conf',
            $this->gitolite_admin_dir . '/conf/projects/newone.conf'
        );
        self::assertDoesNotMatchRegularExpression('`\ninclude "projects/legacy.conf"\n`', $this->getGitoliteConf());
        self::assertMatchesRegularExpression('`\ninclude "projects/newone.conf"\n`', $this->getGitoliteConf());
        $this->assertEmptyGitStatus();
    }

    public function testItLogsEverytimeItPushes(): void
    {
        $this->git_exec->method('push')->willReturn(true);

        $this->driver->push();
        self::assertTrue($this->logger->hasDebugRecords());
    }

    public function testItOnlyIncludeHOSTNAMERelatedConfFileIfHOSTNAMEVariableIsSetInGitoliteRcFile(): void
    {
        $this->gitoliterc_reader->method('getHostname')->willReturn('master');
        $this->another_git_exec->method('add');

        touch($this->gitolite_admin_dir . '/conf/projects/project1.conf');

        $this->another_gitolite_driver->updateMainConfIncludes();

        $gitoliteConf = $this->getGitoliteConf();

        self::assertMatchesRegularExpression('#^include "%HOSTNAME.conf"$#m', $gitoliteConf);
        self::assertDoesNotMatchRegularExpression('#^include "projects/project1.conf"$#m', $gitoliteConf);
    }

    public function testItWritesTheGitoliteConfFileInTheHOSTNAMEDotConfFileIfHostnameVariableIsSet(): void
    {
        $hostname = 'master';
        $this->gitoliterc_reader->method('getHostname')->willReturn($hostname);
        $this->another_git_exec->method('add');

        touch($this->gitolite_admin_dir . '/conf/projects/project1.conf');

        $this->another_gitolite_driver->updateMainConfIncludes();

        $gitoliteConf = $this->getFileConf($hostname);
        self::assertMatchesRegularExpression('#^include "projects/project1.conf"$#m', $gitoliteConf);
    }

    public function testItAddsAllTheRequiredFilesForPush(): void
    {
        $hostname = 'master';

        $this->gitoliterc_reader->method('getHostname')->willReturn($hostname);

        touch($this->gitolite_admin_dir . '/conf/projects/project1.conf');
        $matcher = self::exactly(2);

        $this->another_git_exec->expects($matcher)->method('add')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('conf/gitolite.conf', $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('conf/master.conf', $parameters[0]);
            }
            return true;
        });

        $this->another_gitolite_driver->updateMainConfIncludes();
    }

    public function testItIsInitializedEvenIfThereIsNoMaster(): void
    {
        self::assertTrue($this->driver->isInitialized($this->fixtures_dir . '/headless.git'));
    }

    public function testItIsNotInitializedIfThereIsNoValidDirectory(): void
    {
        self::assertFalse($this->driver->isInitialized($this->fixtures_dir));
    }
}
