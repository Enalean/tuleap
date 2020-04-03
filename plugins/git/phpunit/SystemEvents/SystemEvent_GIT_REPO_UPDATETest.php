<?php
/**
 * Copyright Enalean (c) 2011 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
require_once __DIR__ . '/../bootstrap.php';

class SystemEvent_GIT_REPO_UPDATETest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $repository_id = 115;
    private $system_event_manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backend    = \Mockery::spy(\Git_Backend_Gitolite::class);

        $this->repository = \Mockery::spy(\GitRepository::class);
        $this->repository->shouldReceive('getBackend')->andReturns($this->backend);

        $this->repository_factory   = \Mockery::spy(\GitRepositoryFactory::class);
        $this->system_event_manager = \Mockery::spy(\Git_SystemEventManager::class);

        $this->event = \Mockery::mock(\SystemEvent_GIT_REPO_UPDATE::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->event->setParameters("$this->repository_id");
        $this->event->injectDependencies(
            $this->repository_factory,
            $this->system_event_manager
        );
    }

    public function testItGetsTheRepositoryFromTheFactory(): void
    {
        $this->repository_factory->shouldReceive('getRepositoryById')
            ->with($this->repository_id)
            ->once()
            ->andReturns($this->repository);

        $this->event->process();
    }

    public function testItDelegatesToBackendRepositoryCreation(): void
    {
        $this->repository_factory->shouldReceive('getRepositoryById')->andReturns($this->repository);
        $this->backend->shouldReceive('updateRepoConf')->once();
        $this->event->process();
    }

    public function testItMarksTheEventAsDone(): void
    {
        $this->repository_factory->shouldReceive('getRepositoryById')->andReturns($this->repository);
        $this->backend->shouldReceive('updateRepoConf')->once()->andReturns(true);
        $this->event->shouldReceive('done')->once();
        $this->event->process();
    }

    public function testItMarksTheEventAsWarningWhenTheRepoDoesNotExist(): void
    {
        $this->repository_factory->shouldReceive('getRepositoryById')->andReturns(null);
        $this->event->shouldReceive('warning')->with('Unable to find repository, perhaps it was deleted in the mean time?')->once();
        $this->event->process();
    }

    public function testItMarksTheEventAsDoneWhenTheRepoIsFlaggedAsDeleted(): void
    {
        $this->repository_factory->shouldReceive('getRepositoryById')->andReturns(null);
        $this->repository_factory->shouldReceive('getDeletedRepository')->andReturns($this->repository);

        $this->event->shouldReceive('done')->with('Unable to update a repository marked as deleted')->once();

        $this->event->process();
    }

    public function testItAskToUpdateGrokmirrorManifestFiles(): void
    {
        $this->repository_factory->shouldReceive('getRepositoryById')->andReturns($this->repository);

        $this->backend->shouldReceive('updateRepoConf')->once()->andReturns(true);
        $this->system_event_manager->shouldReceive('queueGrokMirrorManifest')->with($this->repository)->once();

        $this->event->process();
    }
}
