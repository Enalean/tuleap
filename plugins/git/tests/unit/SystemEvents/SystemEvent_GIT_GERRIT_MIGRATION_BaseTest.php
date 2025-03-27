<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Git\SystemEvents;

use ColinODell\PsrTestLogger\TestLogger;
use Exception;
use ForgeConfig;
use Git_Backend_Gitolite;
use Git_Driver_Gerrit_Exception;
use Git_Driver_Gerrit_ProjectCreator;
use Git_GitRepositoryUrlManager;
use Git_RemoteServer_GerritServer;
use Git_RemoteServer_GerritServerFactory;
use GitDao;
use GitRepository;
use GitRepositoryFactory;
use MailBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use SystemEvent_GIT_GERRIT_MIGRATION;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SystemEvent_GIT_GERRIT_MIGRATION_BaseTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    use ForgeConfigSandbox;
    use TemporaryTestDirectory;

    private SystemEvent_GIT_GERRIT_MIGRATION&MockObject $event;
    private int $repository_id         = 123;
    private int $deleted_repository_id = 124;
    private int $remote_server_id      = 12;
    private GitRepository $repository;
    private Git_RemoteServer_GerritServer&MockObject $gerrit_server;
    private Git_RemoteServer_GerritServerFactory&MockObject $server_factory;
    private Git_Backend_Gitolite&MockObject $gitolite_backend;
    private Git_Driver_Gerrit_ProjectCreator&MockObject $project_creator;
    private TestLogger $logger;
    private GitDao&MockObject $dao;

    protected function setUp(): void
    {
        ForgeConfig::set('sys_supported_languages', 'en_US,fr_FR');
        ForgeConfig::set('sys_lang', 'en_US');
        ForgeConfig::set('codendi_cache_dir', $this->getTmpDir());
        ForgeConfig::set('sys_incdir', __DIR__ . '/../../../../../../../../site-content');
        $this->dao              = $this->createMock(GitDao::class);
        $this->gerrit_server    = $this->createMock(Git_RemoteServer_GerritServer::class);
        $this->server_factory   = $this->createMock(Git_RemoteServer_GerritServerFactory::class);
        $this->project_creator  = $this->createMock(Git_Driver_Gerrit_ProjectCreator::class);
        $this->gitolite_backend = $this->createMock(Git_Backend_Gitolite::class);
        $this->repository       = GitRepositoryTestBuilder::aProjectRepository()->withBackend($this->gitolite_backend)->build();
        $user_manager           = $this->createMock(UserManager::class);
        $user_manager->method('getUserById')->willReturn(UserTestBuilder::buildWithDefaults());

        $factory = $this->createMock(GitRepositoryFactory::class);
        $factory->method('getRepositoryById')->willReturnCallback(fn(int $id) => match ($id) {
            $this->repository_id => $this->repository,
            default              => null,
        });
        $this->gerrit_server->method('getBaseUrl')->willReturn('https://gerrit.example.com:8888/');

        $this->event = $this->createPartialMock(SystemEvent_GIT_GERRIT_MIGRATION::class, ['done', 'error']);
        $this->event->setParameters("$this->repository_id::$this->remote_server_id::true");
        $this->logger = new TestLogger();
        $mail_builder = $this->createMock(MailBuilder::class);
        $this->event->injectDependencies(
            $this->dao,
            $factory,
            $this->server_factory,
            $this->logger,
            $this->project_creator,
            $this->createMock(Git_GitRepositoryUrlManager::class),
            $user_manager,
            $mail_builder,
        );
        $mail_builder->method('buildAndSendEmail');
        $this->dao->method('switchToGerrit');
        $this->dao->method('setGerritMigrationError');
        $this->dao->method('setGerritMigrationSuccess');
        $this->project_creator->method('finalizeGerritProjectCreation');
        $this->project_creator->method('removeTemporaryDirectory');
        $this->gitolite_backend->method('updateRepoConf');
    }

    public function testItSwitchesTheBackendToGerrit(): void
    {
        $this->server_factory->method('getServer')->with($this->repository)->willReturn($this->gerrit_server);
        $this->dao->expects($this->once())->method('switchToGerrit')->with($this->repository_id, $this->remote_server_id);
        $this->event->method('error');
        $this->event->process();
    }

    public function testItCallsDoneAndReturnsTrue(): void
    {
        $this->server_factory->method('getServer')->with($this->repository)->willReturn($this->gerrit_server);
        $this->event->expects($this->once())->method('done');
        $this->project_creator->method('createGerritProject');
        self::assertTrue($this->event->process());
    }

    public function testItUpdatesGitolitePermissionsToForbidPushesByAnyoneButGerrit(): void
    {
        $this->server_factory->method('getServer')->with($this->repository)->willReturn($this->gerrit_server);
        $this->gitolite_backend->expects($this->once())->method('updateRepoConf');
        $this->event->method('done');
        $this->project_creator->method('createGerritProject');
        self::assertTrue($this->event->process());
    }

    public function testItInformsAboutMigrationSuccess(): void
    {
        $this->server_factory->method('getServer')->with($this->repository)->willReturn($this->gerrit_server);
        $remote_project = 'tuleap.net-Firefox/mobile';
        $this->project_creator->method('createGerritProject')->willReturn($remote_project);
        $this->event->expects($this->once())->method('done')->with("Created project $remote_project on https://gerrit.example.com:8888/");
        $this->event->process();
    }

    public function testItInformsAboutAnyGenericFailure(): void
    {
        $this->server_factory->method('getServer')->with($this->repository)->willReturn($this->gerrit_server);
        $this->project_creator->method('createGerritProject')->willThrowException(new Exception('failure detail'));
        $this->event->expects($this->once())->method('error')->with('failure detail');
        $this->event->process();
        self::assertTrue($this->logger->hasError('An error occured while processing event: ' . $this->event->verbalizeParameters(null)));
    }

    public function testItInformsAboutAnyGerritRelatedFailureByAddingAPrefix(): void
    {
        $this->server_factory->method('getServer')->with($this->repository)->willReturn($this->gerrit_server);
        $this->project_creator->method('createGerritProject')->willThrowException(new Git_Driver_Gerrit_Exception('failure detail'));
        $this->event->expects($this->once())->method('error')->with('gerrit: failure detail');
        $this->event->process();
        self::assertTrue($this->logger->hasError('Gerrit failure: ' . $this->event->verbalizeParameters(null)));
    }

    public function testItInformsAboutAnyServerFactoryFailure(): void
    {
        $e = new Exception('failure detail');
        $this->server_factory->method('getServer')->willThrowException($e);
        $this->event->expects($this->once())->method('error')->with('failure detail');
        $this->event->process();
        self::assertTrue($this->logger->hasError('An error occured while processing event: ' . $this->event->verbalizeParameters(null)));
    }

    public function testItMarksTheEventAsWarningWhenTheRepoDoesNotExist(): void
    {
        $this->event->setParameters("$this->deleted_repository_id::$this->remote_server_id");
        $this->event->expects($this->once())->method('error')->with('Unable to find repository, perhaps it was deleted in the mean time?');
        $this->event->process();
    }

    public function testItCreatesAProject(): void
    {
        $this->server_factory->method('getServer')->with($this->repository)->willReturn($this->gerrit_server);
        //ssh gerrit gerrit create tuleap.net-Firefox/all/mobile
        $this->project_creator->expects($this->once())->method('createGerritProject')->with($this->gerrit_server, $this->repository, 'true');
        $this->event->method('error');
        $this->event->process();
    }
}
