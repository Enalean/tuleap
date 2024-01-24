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

use EventManager;
use Git_Exec;
use Git_Gitolite_ConfigPermissionsSerializer;
use Git_Gitolite_SSHKeyDumper;
use Git_GitoliteDriver;
use Git_GitRepositoryUrlManager;
use Git_SystemEventManager;
use GitDao;
use GitPlugin;
use GitRepositoryFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use UserManager;

abstract class GitoliteTestCase extends TestIntegrationTestCase
{
    use MockeryPHPUnitIntegration;
    use TemporaryTestDirectory;

    /** @var Git_GitoliteDriver */
    protected $driver;
    /** @var UserManager&MockInterface */
    protected $user_manager;
    /** @var Git_Exec&MockInterface */
    protected $git_exec;
    /** @var Git_Gitolite_SSHKeyDumper */
    protected $dumper;
    /** @var GitRepositoryFactory */
    protected $repository_factory;
    /** @var Git_Gitolite_ConfigPermissionsSerializer */
    protected $gitolite_permissions_serializer;

    /** @var Git_GitRepositoryUrlManager */
    protected $url_manager;

    /** @var Git_SystemEventManager */
    protected $git_system_event_manager;

    /** @var \Psr\Log\LoggerInterface&MockInterface */
    protected $logger;
    /**
     * @var false|string
     */
    private $cwd;
    /**
     * @var string
     */
    protected $fixtures_dir;
    /**
     * @var string
     */
    private $gitolite_admin_ref;
    /**
     * @var string
     */
    protected $sys_data_dir;
    /**
     * @var string
     */
    protected $gitolite_admin_dir;
    /**
     * @var string
     */
    private $repo_dir;
    private $backup_sys_data_dir;

    protected function setUp(): void
    {
        $this->cwd                = getcwd();
        $this->fixtures_dir       = __DIR__ . '/_fixtures';
        $tmpDir                   = $this->getTmpDir();
        $this->gitolite_admin_ref = $tmpDir . '/gitolite-admin-ref';
        $this->sys_data_dir       = $tmpDir;
        $this->gitolite_admin_dir = $tmpDir . '/gitolite/admin';
        $this->repo_dir           = $tmpDir . '/repositories';

        // Copy the reference to save time & create symlink because
        // git is very sensitive to path you are using. Just symlinking
        // spots bugs
        mkdir($tmpDir . '/gitolite');
        system('tar -xf ' . $this->fixtures_dir . '/gitolite-admin-ref' . '.tar --directory ' . $tmpDir);
        symlink($this->gitolite_admin_ref, $this->gitolite_admin_dir);

        mkdir($this->repo_dir);

        $this->backup_sys_data_dir = \ForgeConfig::get('sys_data_dir');
        \ForgeConfig::set('sys_data_dir', $this->sys_data_dir);
        $this->git_exec = \Mockery::mock(\Git_Exec::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->git_exec->__construct($this->gitolite_admin_dir);
        $this->git_exec->setLocalCommiter('TestName', 'test@example.com');

        $this->user_manager = \Mockery::spy(\UserManager::class);
        $this->dumper       = new Git_Gitolite_SSHKeyDumper($this->gitolite_admin_dir, $this->git_exec);

        $this->repository_factory = \Mockery::spy(\GitRepositoryFactory::class);

        $git_plugin = \Mockery::mock(GitPlugin::class);
        $git_plugin->shouldReceive('areFriendlyUrlsActivated')->andReturns(false);
        $this->url_manager = new Git_GitRepositoryUrlManager($git_plugin);

        $this->gitolite_permissions_serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            \Mockery::spy(\Git_Driver_Gerrit_ProjectCreatorStatus::class),
            'whatever',
            \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedRetriever::class),
            \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPermissionFactory::class),
            \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedRetriever::class),
            \Mockery::spy(EventManager::class)
        );

        $this->git_system_event_manager = \Mockery::spy(\Git_SystemEventManager::class);
        $this->logger                   = \Mockery::spy(\Psr\Log\LoggerInterface::class);

        $this->driver = new Git_GitoliteDriver(
            $this->logger,
            $this->url_manager,
            \Mockery::spy(GitDao::class),
            \Mockery::mock(GitPlugin::class),
            \Mockery::spy(\Tuleap\Git\BigObjectAuthorization\BigObjectAuthorizationManager::class),
            $this->git_exec,
            $this->repository_factory,
            $this->gitolite_permissions_serializer,
            null,
            null,
        );
    }

    protected function tearDown(): void
    {
        chdir($this->cwd);
        \ForgeConfig::set('sys_data_dir', $this->backup_sys_data_dir);
    }

    public function assertEmptyGitStatus(): void
    {
        $cwd = getcwd();
        chdir($this->gitolite_admin_dir);
        exec(\Git_Exec::getGitCommand() . ' status --porcelain', $output, $ret_val);
        chdir($cwd);
        $this->assertEmpty($output);
        $this->assertEquals(0, $ret_val);
    }
}
