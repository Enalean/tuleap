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
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Test\Stubs\Process\ProcessFactoryStub;
use Tuleap\Test\Stubs\Process\ProcessStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitoliteDriverTest extends TestIntegrationTestCase
{
    use TemporaryTestDirectory;

    private Git_GitoliteDriver $a_gitolite_driver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        ForgeConfig::set('codendi_cache_dir', $this->getTmpDir() . '/cache');

        $project_manager    = $this->createStub(ProjectManager::class);
        $repository_factory = $this->createStub(GitRepositoryFactory::class);

        $git_plugin = $this->createStub(GitPlugin::class);
        $git_plugin->method('areFriendlyUrlsActivated')->willReturn(false);
        $url_manager = new Git_GitRepositoryUrlManager($git_plugin);

        $logger = new NullLogger();

        $fine_grained_retriever = $this->createStub(FineGrainedRetriever::class);

        $another_gitolite_permissions_serializer = new Git_Gitolite_ConfigPermissionsSerializer(
            $this->createStub(Git_Driver_Gerrit_ProjectCreatorStatus::class),
            'whatever',
            $fine_grained_retriever,
            $this->createStub(FineGrainedPermissionFactory::class),
            $this->createStub(RegexpFineGrainedRetriever::class),
            EventDispatcherStub::withIdentityCallback(),
        );

        $big_object_authorization_manager = $this->createStub(BigObjectAuthorizationManager::class);
        $a_gitolite_project_serializer    = new Git_Gitolite_ProjectSerializer(
            $logger,
            $repository_factory,
            $another_gitolite_permissions_serializer,
            $url_manager,
            $big_object_authorization_manager,
        );

        $dao                  = $this->createStub(GitDao::class);
        $gitolite_conf_writer = new Git_Gitolite_GitoliteConfWriter(
            $another_gitolite_permissions_serializer,
            $a_gitolite_project_serializer,
            $dao,
            $logger,
            $project_manager,
            $this->getGitoliteAdministrationPath()
        );

        $git_dao                 = $this->createStub(GitDao::class);
        $git_plugin              = $this->createStub(GitPlugin::class);
        $this->a_gitolite_driver = new Git_GitoliteDriver(
            $logger,
            $url_manager,
            $git_dao,
            $git_plugin,
            $big_object_authorization_manager,
            ProcessFactoryStub::withProcess(ProcessStub::successfulProcess()),
            $repository_factory,
            $another_gitolite_permissions_serializer,
            $gitolite_conf_writer,
            $project_manager,
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

    public function testItIsInitializedEvenIfThereIsNoMaster(): void
    {
        self::assertTrue($this->a_gitolite_driver->isInitialized(__DIR__ . '/_fixtures/headless.git'));
    }

    public function testItIsNotInitializedIfThereIsNoValidDirectory(): void
    {
        self::assertFalse($this->a_gitolite_driver->isInitialized(__DIR__ . '/_fixtures/'));
    }
}
