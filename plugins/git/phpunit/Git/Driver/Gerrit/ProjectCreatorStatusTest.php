<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class ProjectCreatorStatusTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $dao;
    private $repository;
    private $gerrit_status;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao        = \Mockery::spy(Git_Driver_Gerrit_ProjectCreatorStatusDao::class);
        $this->repository = \Mockery::spy(\GitRepository::class);

        $this->gerrit_status = new Git_Driver_Gerrit_ProjectCreatorStatus($this->dao);
    }

    public function testItIsStatusErrorWhenGitRepositoryIsError(): void
    {
        $this->repository->shouldReceive('isMigratedToGerrit')->andReturns(true);
        $this->repository->shouldReceive('getMigrationStatus')->andReturns(Git_Driver_Gerrit_ProjectCreatorStatus::ERROR);

        $this->assertEquals(Git_Driver_Gerrit_ProjectCreatorStatus::ERROR, $this->gerrit_status->getStatus($this->repository));
    }

    // We don't know what project did with their repo until this change
    // So we assume everything is OK.
    public function testItHasStatusDoneWhenOnlySystemEventIsErrorForLegacy(): void
    {
        $this->repository->shouldReceive('isMigratedToGerrit')->andReturns(true);
        $this->repository->shouldReceive('getMigrationStatus')->andReturns(null);

        $this->dao->shouldReceive('getSystemEventForRepository')->andReturns(array(
            'status'      => SystemEvent::STATUS_ERROR,
            'create_date' => '1453886645',
            'log'         => 'stuff',
        ));

        $this->assertEquals(Git_Driver_Gerrit_ProjectCreatorStatus::DONE, $this->gerrit_status->getStatus($this->repository));
    }

    // We don't know what project did with their repo until this change
    // So we assume everything is OK.
    public function testItHasStatusDoneWhenSystemEventIsNoLongerPartOfTheDB(): void
    {
        $this->repository->shouldReceive('isMigratedToGerrit')->andReturns(true);
        $this->repository->shouldReceive('getMigrationStatus')->andReturns(null);

        $this->dao->shouldReceive('getSystemEventForRepository')->andReturns(null);

        $this->assertEquals(Git_Driver_Gerrit_ProjectCreatorStatus::DONE, $this->gerrit_status->getStatus($this->repository));
    }

    // We don't know what project did with their repo until this change
    // So we assume everything is OK.
    public function testItHasStatusDoneWhenOnlySystemEventIsWarningForLegacy(): void
    {
        $this->repository->shouldReceive('isMigratedToGerrit')->andReturns(true);
        $this->repository->shouldReceive('getMigrationStatus')->andReturns(null);

        $this->dao->shouldReceive('getSystemEventForRepository')->andReturns(array(
            'status'      => SystemEvent::STATUS_WARNING,
            'create_date' => '1453886645',
            'log'         => 'stuff',
        ));

        $this->assertEquals(Git_Driver_Gerrit_ProjectCreatorStatus::DONE, $this->gerrit_status->getStatus($this->repository));
    }

    public function testItHasStatusDoneWhenSystemEventIsDone(): void
    {
        $this->repository->shouldReceive('isMigratedToGerrit')->andReturns(true);
        $this->repository->shouldReceive('getMigrationStatus')->andReturns(null);

        $this->dao->shouldReceive('getSystemEventForRepository')->andReturns(array(
            'status'      => SystemEvent::STATUS_DONE,
            'create_date' => '1453886645',
            'log'         => '',
        ));

        $this->assertEquals(Git_Driver_Gerrit_ProjectCreatorStatus::DONE, $this->gerrit_status->getStatus($this->repository));
    }

    public function testItHasStatusDoneWhenRepositoryIsExplicitlyMarkedAsDone(): void
    {
        $this->repository->shouldReceive('isMigratedToGerrit')->andReturns(true);
        $this->repository->shouldReceive('getMigrationStatus')->andReturns(Git_Driver_Gerrit_ProjectCreatorStatus::DONE);

        $this->dao->shouldReceive('getSystemEventForRepository')->andReturns(null);

        $this->assertEquals(Git_Driver_Gerrit_ProjectCreatorStatus::DONE, $this->gerrit_status->getStatus($this->repository));
    }

    public function testItHasStatusQueueWhenSystemEventIsNew(): void
    {
        $this->repository->shouldReceive('isMigratedToGerrit')->andReturns(false);
        $this->repository->shouldReceive('getMigrationStatus')->andReturns(null);

        $this->dao->shouldReceive('getSystemEventForRepository')->andReturns(array(
            'status'      => SystemEvent::STATUS_NEW,
            'create_date' => '1453886645',
            'log'         => '',
        ));

        $this->assertEquals(Git_Driver_Gerrit_ProjectCreatorStatus::QUEUE, $this->gerrit_status->getStatus($this->repository));
    }

    public function testItHasStatusQueueWhenSystemEventIsRunning(): void
    {
        $this->repository->shouldReceive('isMigratedToGerrit')->andReturns(true);
        $this->repository->shouldReceive('getMigrationStatus')->andReturns(null);

        $this->dao->shouldReceive('getSystemEventForRepository')->andReturns(array(
            'status'      => SystemEvent::STATUS_RUNNING,
            'create_date' => '1453886645',
            'log'         => '',
        ));

        $this->assertEquals(Git_Driver_Gerrit_ProjectCreatorStatus::QUEUE, $this->gerrit_status->getStatus($this->repository));
    }

    public function testItHasStatusNullWhenRepositoryIsNotMigrated(): void
    {
        $this->repository->shouldReceive('isMigratedToGerrit')->andReturns(false);
        $this->repository->shouldReceive('getMigrationStatus')->andReturns(Git_Driver_Gerrit_ProjectCreatorStatus::DONE);

        $this->assertNull($this->gerrit_status->getStatus($this->repository));
    }
}
