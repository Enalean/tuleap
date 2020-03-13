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

class SystemEvent_GIT_REPO_FORKTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    private $old_repository;
    private $new_repository;
    private $old_repository_id = 115;
    private $new_repository_id = 123;

    protected function setUp() : void
    {
        parent::setUp();

        $this->backend    = \Mockery::spy(\Git_Backend_Gitolite::class);

        $this->old_repository = \Mockery::spy(\GitRepository::class);
        $this->old_repository->shouldReceive('getBackend')->andReturns($this->backend);

        $this->new_repository     = \Mockery::spy(\GitRepository::class);
        $this->repository_factory = \Mockery::spy(\GitRepositoryFactory::class);

        $this->event = \Mockery::mock(\SystemEvent_GIT_REPO_FORK::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->event->setParameters($this->old_repository_id . SystemEvent::PARAMETER_SEPARATOR . $this->new_repository_id);
        $this->event->injectDependencies($this->repository_factory);
    }

    public function testItGetsTheRepositoryIdsFromTheFactory() : void
    {
        $this->repository_factory->shouldReceive('getRepositoryById')->with($this->old_repository_id)->andReturns($this->old_repository);
        $this->repository_factory->shouldReceive('getRepositoryById')->with($this->new_repository_id)->andReturns($this->new_repository);
        $this->event->process();
    }

    public function testItDelegatesToBackendRepositoryCreation() : void
    {
        $this->repository_factory->shouldReceive('getRepositoryById')->with($this->old_repository_id)->andReturns($this->old_repository);
        $this->repository_factory->shouldReceive('getRepositoryById')->with($this->new_repository_id)->andReturns($this->new_repository);
        $this->backend->shouldReceive('forkOnFilesystem')->with(\Mockery::any(), $this->new_repository)->once();
        $this->event->process();
    }

    public function testItMarksTheEventAsDone() : void
    {
        $this->repository_factory->shouldReceive('getRepositoryById')->with($this->old_repository_id)->andReturns($this->old_repository);
        $this->repository_factory->shouldReceive('getRepositoryById')->with($this->new_repository_id)->andReturns($this->new_repository);
        $this->event->shouldReceive('done')->once();
        $this->event->process();
    }

    public function testItMarksTheEventAsWarningWhenTheRepoDoesNotExist() : void
    {
        $this->repository_factory->shouldReceive('getRepositoryById')->andReturns(null);
        $this->event->shouldReceive('warning')->with('Unable to find repository, perhaps it was deleted in the mean time?')->once();
        $this->event->process();
    }
}
