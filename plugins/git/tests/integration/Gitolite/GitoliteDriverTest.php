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

use ForgeConfig;
use Git_Driver_Gerrit_ProjectCreatorStatus;
use Git_Gitolite_ConfigPermissionsSerializer;
use Git_Gitolite_GitoliteConfWriter;
use Git_Gitolite_ProjectSerializer;
use Git_GitoliteDriver;
use Git_GitRepositoryUrlManager;
use GitDao;
use GitPlugin;
use GitRepositoryFactory;
use ProjectManager;
use Psr\Log\NullLogger;
use Tuleap\Git\BigObjectAuthorization\BigObjectAuthorizationManager;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Test\Stubs\Process\ProcessFactoryStub;
use Tuleap\Test\Stubs\Process\ProcessStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitoliteDriverTest extends TestIntegrationTestCase
{
    use TemporaryTestDirectory;

    private Git_GitoliteDriver $a_gitolite_driver;
    private ProjectManager&\PHPUnit\Framework\MockObject\Stub $project_manager;
    private GitRepositoryFactory&\PHPUnit\Framework\MockObject\Stub $repository_factory;
    private FineGrainedRetriever&\PHPUnit\Framework\MockObject\Stub $fine_grained_retriever;
    private BigObjectAuthorizationManager&\PHPUnit\Framework\MockObject\Stub $big_object_authorization_manager;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        ForgeConfig::set('codendi_cache_dir', $this->getTmpDir() . '/cache');

        $this->repository_factory = $this->createStub(GitRepositoryFactory::class);

        $git_plugin = $this->createStub(GitPlugin::class);
        $git_plugin->method('areFriendlyUrlsActivated')->willReturn(false);
        $url_manager = new Git_GitRepositoryUrlManager($git_plugin);

        $logger = new NullLogger();

        $this->fine_grained_retriever = $this->createStub(FineGrainedRetriever::class);
        $this->project_manager        = $this->createStub(ProjectManager::class);

        $another_gitolite_permissions_serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            $this->createStub(Git_Driver_Gerrit_ProjectCreatorStatus::class),
            'whatever',
            $this->fine_grained_retriever,
            $this->createStub(FineGrainedPermissionFactory::class),
            $this->createStub(RegexpFineGrainedRetriever::class),
            EventDispatcherStub::withIdentityCallback(),
        );

        $this->big_object_authorization_manager = $this->createStub(BigObjectAuthorizationManager::class);
        $a_gitolite_project_serializer          = new Git_Gitolite_ProjectSerializer(
            $logger,
            $this->repository_factory,
            $another_gitolite_permissions_serializer,
            $url_manager,
            $this->big_object_authorization_manager,
        );

        $gitolite_conf_writer = new Git_Gitolite_GitoliteConfWriter(
            $another_gitolite_permissions_serializer,
            $a_gitolite_project_serializer,
            $logger,
            $this->project_manager,
            $this->getGitoliteAdministrationPath()
        );

        $git_dao                 = $this->createStub(GitDao::class);
        $git_plugin              = $this->createStub(GitPlugin::class);
        $this->a_gitolite_driver = new Git_GitoliteDriver(
            $logger,
            $url_manager,
            $git_dao,
            $git_plugin,
            $this->big_object_authorization_manager,
            ProcessFactoryStub::withProcess(ProcessStub::successfulProcess()),
            $this->repository_factory,
            $another_gitolite_permissions_serializer,
            $gitolite_conf_writer,
            $this->project_manager,
        );
    }

    #[\Override]
    protected function tearDown(): void
    {
        \PermissionsManager::clearInstance();
    }

    private function getGitoliteAdministrationPath(): string
    {
        return $this->getTmpDir();
    }

    private function getGitoliteConf(): string
    {
        return \Psl\File\read($this->getGitoliteAdministrationPath() . '/conf/gitolite.conf');
    }

    public function testItCanRenameProject(): void
    {
        $new_name = 'newone';

        $project = ProjectTestBuilder::aProject()->withUnixName($new_name)->build();
        $this->project_manager->method('getProjectById')->with(101)->willReturn($project);

        $legacy_conf_file_path = $this->getGitoliteAdministrationPath() . '/conf/projects/legacy.conf';
        \Psl\Filesystem\create_directory_for_file($legacy_conf_file_path);
        \Psl\Filesystem\create_file($legacy_conf_file_path);
        self::assertFileDoesNotExist($this->getGitoliteAdministrationPath() . '/conf/projects/newone.conf');

        $this->fine_grained_retriever->method('doesRepositoryUseFineGrainedPermissions')->willReturn(false);
        $this->big_object_authorization_manager->method('getAuthorizedProjects')->willReturn([]);
        $repository_test_default = $this->buildRepository($project, 'test_default', 101);
        $repository_test_pimped  = $this->buildRepository($project, 'test_pimped', 102);
        $repository_test_pimped->setMailPrefix('[KOIN-legacy] ');
        $this->repository_factory->method('getAllRepositoriesOfProject')->willReturn([
            $repository_test_default,
            $repository_test_pimped,
        ]);

        $this->a_gitolite_driver->renameProject(101, 'legacy');

        clearstatcache(true, $legacy_conf_file_path);
        self::assertFileDoesNotExist($legacy_conf_file_path);
        self::assertFileExists($this->getGitoliteAdministrationPath() . '/conf/projects/newone.conf');
        self::assertFileEquals(
            __DIR__ . '/_fixtures/perms/newone.conf',
            $this->getGitoliteAdministrationPath() . '/conf/projects/newone.conf'
        );
        $gitolite_conf = $this->getGitoliteConf();
        self::assertStringNotContainsString('include "projects/legacy.conf"', $gitolite_conf);
        self::assertStringContainsString('include "projects/newone.conf"', $gitolite_conf);
    }

    private function buildRepository(\Project $project, string $name, int $id): \GitRepository
    {
        $repository = new \GitRepository();
        $repository->setName($name);
        $repository->setNamespace('');
        $repository->setMailPrefix('');
        $repository->setProject($project);
        $repository->setId($id);
        return $repository;
    }

    public function testItIsInitializedEvenIfThereIsNoMaster(): void
    {
        self::assertTrue($this->a_gitolite_driver->isInitialized(__DIR__ . '/_fixtures/headless.git'));
    }

    public function testItIsNotInitializedIfThereIsNoValidDirectory(): void
    {
        self::assertFalse($this->a_gitolite_driver->isInitialized(__DIR__ . '/_fixtures/'));
    }
}
