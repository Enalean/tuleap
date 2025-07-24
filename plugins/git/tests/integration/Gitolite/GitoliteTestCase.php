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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Git\Gitolite;

use ColinODell\PsrTestLogger\TestLogger;
use EventManager;
use ForgeConfig;
use Git_Driver_Gerrit_ProjectCreatorStatus;
use Git_Exec;
use Git_Gitolite_ConfigPermissionsSerializer;
use Git_Gitolite_SSHKeyDumper;
use Git_GitoliteDriver;
use Git_GitRepositoryUrlManager;
use Git_SystemEventManager;
use GitDao;
use GitPlugin;
use GitRepositoryFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Git\BigObjectAuthorization\BigObjectAuthorizationManager;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use UserManager;

abstract class GitoliteTestCase extends TestIntegrationTestCase
{
    use TemporaryTestDirectory;

    protected Git_GitoliteDriver $driver;
    protected UserManager&MockObject $user_manager;
    protected Git_Exec&MockObject $git_exec;
    protected Git_Gitolite_SSHKeyDumper $dumper;
    protected GitRepositoryFactory&MockObject $repository_factory;
    protected Git_Gitolite_ConfigPermissionsSerializer $gitolite_permissions_serializer;
    protected Git_GitRepositoryUrlManager $url_manager;
    protected Git_SystemEventManager&MockObject $git_system_event_manager;
    protected TestLogger $logger;
    private string $cwd;
    protected string $fixtures_dir;
    protected string $sys_data_dir;
    protected string $gitolite_admin_dir;

    #[\Override]
    protected function setUp(): void
    {
        $this->cwd                = getcwd();
        $this->fixtures_dir       = __DIR__ . '/_fixtures';
        $tmpDir                   = $this->getTmpDir();
        $gitolite_admin_ref       = $tmpDir . '/gitolite-admin-ref';
        $this->sys_data_dir       = $tmpDir;
        $this->gitolite_admin_dir = $tmpDir . '/gitolite/admin';
        $repo_dir                 = $tmpDir . '/repositories';

        // Copy the reference to save time & create symlink because
        // git is very sensitive to path you are using. Just symlinking
        // spots bugs
        mkdir($tmpDir . '/gitolite');
        system('tar -xf ' . $this->fixtures_dir . '/gitolite-admin-ref' . '.tar --directory ' . $tmpDir);
        symlink($gitolite_admin_ref, $this->gitolite_admin_dir);

        mkdir($repo_dir);

        ForgeConfig::set('sys_data_dir', $this->sys_data_dir);
        $this->git_exec = $this->getMockBuilder(Git_Exec::class)
            ->setConstructorArgs([$this->gitolite_admin_dir])
            ->onlyMethods(['push'])
            ->getMock();
        $this->git_exec->setLocalCommiter('TestName', 'test@example.com');

        $this->user_manager = $this->createMock(UserManager::class);
        $this->dumper       = new Git_Gitolite_SSHKeyDumper($this->gitolite_admin_dir, $this->git_exec);

        $this->repository_factory = $this->createMock(GitRepositoryFactory::class);

        $git_plugin = $this->createMock(GitPlugin::class);
        $git_plugin->method('areFriendlyUrlsActivated')->willReturn(false);
        $this->url_manager = new Git_GitRepositoryUrlManager($git_plugin);

        $this->gitolite_permissions_serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            $this->createMock(Git_Driver_Gerrit_ProjectCreatorStatus::class),
            'whatever',
            $this->createMock(FineGrainedRetriever::class),
            $this->createMock(FineGrainedPermissionFactory::class),
            $this->createMock(RegexpFineGrainedRetriever::class),
            $this->createMock(EventManager::class)
        );

        $this->git_system_event_manager = $this->createMock(Git_SystemEventManager::class);
        $this->logger                   = new TestLogger();

        $this->driver = new Git_GitoliteDriver(
            $this->logger,
            $this->url_manager,
            $this->createMock(GitDao::class),
            $git_plugin,
            $this->createMock(BigObjectAuthorizationManager::class),
            $this->git_exec,
            $this->repository_factory,
            $this->gitolite_permissions_serializer,
            null,
            null,
        );
    }

    #[\Override]
    protected function tearDown(): void
    {
        chdir($this->cwd);
    }

    public function assertEmptyGitStatus(): void
    {
        $cwd = getcwd();
        chdir($this->gitolite_admin_dir);
        exec(Git_Exec::getGitCommand() . ' status --porcelain', $output, $ret_val);
        chdir($cwd);
        self::assertEmpty($output);
        self::assertEquals(0, $ret_val);
    }
}
