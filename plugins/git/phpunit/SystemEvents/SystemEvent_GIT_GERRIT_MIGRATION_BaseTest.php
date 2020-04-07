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

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps,PSR1.Classes.ClassDeclaration.MultipleClasses
class SystemEvent_GIT_GERRIT_MIGRATION_BackendTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var SystemEvent_GIT_GERRIT_MIGRATION
     */
    protected $event;
    protected $repository_id         = 123;
    protected $deleted_repository_id = 124;
    protected $remote_server_id      = 12;
    protected $repository;
    protected $gerrit_server;
    protected $server_factory;
    protected $gitolite_backend;
    protected $user_manager;
    /**
     * @var Git_Driver_Gerrit_ProjectCreator|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $project_creator;

    /**
     * @var Logger|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $logger;

    /**
     * @var GitDao|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao              = \Mockery::spy(GitDao::class);
        $this->gerrit_server    = \Mockery::spy(\Git_RemoteServer_GerritServer::class);
        $this->server_factory   = \Mockery::spy(\Git_RemoteServer_GerritServerFactory::class);
        $this->project_creator  = \Mockery::spy(\Git_Driver_Gerrit_ProjectCreator::class);
        $this->gitolite_backend = \Mockery::spy(\Git_Backend_Gitolite::class);
        $this->repository       = \Mockery::spy(\GitRepository::class);
        $this->user_manager     = \Mockery::spy(\UserManager::class);
        $this->repository->shouldReceive('getBackend')->andReturns($this->gitolite_backend);

        $factory = \Mockery::spy(\GitRepositoryFactory::class);
        $factory->shouldReceive('getRepositoryById')->with($this->repository_id)->andReturns($this->repository);

        $id = $type = $parameters = $priority = $status = $create_date = $process_date = $end_date = $log = 0;
        $this->event = \Mockery::mock(\SystemEvent_GIT_GERRIT_MIGRATION::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->event->setParameters("$this->repository_id::$this->remote_server_id::true");
        $this->logger = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $this->event->injectDependencies($this->dao, $factory, $this->server_factory, $this->logger, $this->project_creator, \Mockery::spy(\Git_GitRepositoryUrlManager::class), $this->user_manager, \Mockery::spy(\MailBuilder::class));
    }

    public function testItSwitchesTheBackendToGerrit(): void
    {
        $this->server_factory->shouldReceive('getServer')->with($this->repository)->andReturns($this->gerrit_server);
        $this->dao->shouldReceive('switchToGerrit')->with($this->repository_id, $this->remote_server_id)->once();
        $this->event->process();
    }

    public function testItCallsDoneAndReturnsTrue(): void
    {
        $this->server_factory->shouldReceive('getServer')->with($this->repository)->andReturns($this->gerrit_server);
        $this->event->shouldReceive('done')->once();
        $this->assertTrue($this->event->process());
    }

    public function testItUpdatesGitolitePermissionsToForbidPushesByAnyoneButGerrit(): void
    {
        $this->server_factory->shouldReceive('getServer')->with($this->repository)->andReturns($this->gerrit_server);
        $this->gitolite_backend->shouldReceive('updateRepoConf')->once();
        $this->assertTrue($this->event->process());
    }

    public function testItInformsAboutMigrationSuccess(): void
    {
        $this->server_factory->shouldReceive('getServer')->with($this->repository)->andReturns($this->gerrit_server);
        $remote_project = 'tuleap.net-Firefox/mobile';
        $gerrit_url     = 'https://gerrit.example.com:8888/';
        $this->project_creator->shouldReceive('createGerritProject')->andReturns($remote_project);
        $this->gerrit_server->shouldReceive('getBaseUrl')->andReturns($gerrit_url);
        $this->event->shouldReceive('done')->with("Created project $remote_project on $gerrit_url")->once();
        $this->event->process();
    }

    public function testItInformsAboutAnyGenericFailure(): void
    {
        $this->user_manager->shouldReceive('getUserById')->andReturns(
            new PFUser([
                'language_id' => 'en',
                'user_id' => 0
            ])
        );
        $this->server_factory->shouldReceive('getServer')->with($this->repository)->andReturns($this->gerrit_server);
        $e = new Exception("failure detail");
        $this->project_creator->shouldReceive('createGerritProject')->andThrows($e);
        $this->event->shouldReceive('error')->with("failure detail")->once();
        $this->logger->shouldReceive('error')->with("An error occured while processing event: " . $this->event->verbalizeParameters(null), ['exception' => $e])->once();
        $this->event->process();
    }

    public function testItInformsAboutAnyGerritRelatedFailureByAddingAPrefix(): void
    {
        $this->user_manager->shouldReceive('getUserById')->andReturns(
            new PFUser([
                'language_id' => 'en',
                'user_id' => 0
            ])
        );
        $this->server_factory->shouldReceive('getServer')->with($this->repository)->andReturns($this->gerrit_server);
        $e = new Git_Driver_Gerrit_Exception("failure detail");
        $this->project_creator->shouldReceive('createGerritProject')->andThrows($e);
        $this->event->shouldReceive('error')->with("gerrit: failure detail")->once();
        $this->logger->shouldReceive('error')->with("Gerrit failure: " . $this->event->verbalizeParameters(null), ['exception' => $e])->once();
        $this->event->process();
    }

    public function testItInformsAboutAnyServerFactoryFailure(): void
    {
        $this->user_manager->shouldReceive('getUserById')->andReturns(
            new PFUser([
                'language_id' => 'en',
                'user_id' => 0
            ])
        );
        $e = new Exception("failure detail");
        $this->server_factory->shouldReceive('getServer')->andThrows($e);
        $this->event->shouldReceive('error')->with("failure detail")->once();
        $this->logger->shouldReceive('error')->with("An error occured while processing event: " . $this->event->verbalizeParameters(null), ['exception' => $e])->once();
        $this->event->process();
    }

    public function testItMarksTheEventAsWarningWhenTheRepoDoesNotExist(): void
    {
        $this->event->setParameters("$this->deleted_repository_id::$this->remote_server_id");
        $this->event->shouldReceive('error')->with('Unable to find repository, perhaps it was deleted in the mean time?')->once();
        $this->event->process();
    }

    public function testItCreatesAProject(): void
    {
        $this->server_factory->shouldReceive('getServer')->with($this->repository)->andReturns($this->gerrit_server);
        //ssh gerrit gerrit create tuleap.net-Firefox/all/mobile
        $this->project_creator->shouldReceive('createGerritProject')->with($this->gerrit_server, $this->repository, "true")->once();
        $this->event->process();
    }
}
