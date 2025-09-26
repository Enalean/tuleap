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

declare(strict_types=1);

namespace Tuleap\Git\Driver\Gerrit;

use Git_Driver_Gerrit_ProjectCreatorStatus;
use Git_Driver_Gerrit_ProjectCreatorStatusDao;
use GitRepository;
use PHPUnit\Framework\MockObject\MockObject;
use SystemEvent;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectCreatorStatusTest extends TestCase
{
    private Git_Driver_Gerrit_ProjectCreatorStatusDao&MockObject $dao;
    private GitRepository&MockObject $repository;
    private Git_Driver_Gerrit_ProjectCreatorStatus $gerrit_status;

    #[\Override]
    protected function setUp(): void
    {
        $this->dao        = $this->createMock(Git_Driver_Gerrit_ProjectCreatorStatusDao::class);
        $this->repository = $this->createMock(GitRepository::class);
        $this->repository->method('getId');

        $this->gerrit_status = new Git_Driver_Gerrit_ProjectCreatorStatus($this->dao);
    }

    public function testItIsStatusErrorWhenGitRepositoryIsError(): void
    {
        $this->repository->method('isMigratedToGerrit')->willReturn(true);
        $this->repository->method('getMigrationStatus')->willReturn(Git_Driver_Gerrit_ProjectCreatorStatus::ERROR);
        $this->dao->method('getSystemEventForRepository');

        self::assertEquals(Git_Driver_Gerrit_ProjectCreatorStatus::ERROR, $this->gerrit_status->getStatus($this->repository));
    }

    // We don't know what project did with their repo until this change
    // So we assume everything is OK.
    public function testItHasStatusDoneWhenOnlySystemEventIsErrorForLegacy(): void
    {
        $this->repository->method('isMigratedToGerrit')->willReturn(true);
        $this->repository->method('getMigrationStatus')->willReturn(null);

        $this->dao->method('getSystemEventForRepository')->willReturn([
            'status'      => SystemEvent::STATUS_ERROR,
            'create_date' => '1453886645',
            'log'         => 'stuff',
        ]);

        self::assertEquals(Git_Driver_Gerrit_ProjectCreatorStatus::DONE, $this->gerrit_status->getStatus($this->repository));
    }

    // We don't know what project did with their repo until this change
    // So we assume everything is OK.
    public function testItHasStatusDoneWhenSystemEventIsNoLongerPartOfTheDB(): void
    {
        $this->repository->method('isMigratedToGerrit')->willReturn(true);
        $this->repository->method('getMigrationStatus')->willReturn(null);

        $this->dao->method('getSystemEventForRepository')->willReturn(null);

        self::assertEquals(Git_Driver_Gerrit_ProjectCreatorStatus::DONE, $this->gerrit_status->getStatus($this->repository));
    }

    // We don't know what project did with their repo until this change
    // So we assume everything is OK.
    public function testItHasStatusDoneWhenOnlySystemEventIsWarningForLegacy(): void
    {
        $this->repository->method('isMigratedToGerrit')->willReturn(true);
        $this->repository->method('getMigrationStatus')->willReturn(null);

        $this->dao->method('getSystemEventForRepository')->willReturn([
            'status'      => SystemEvent::STATUS_WARNING,
            'create_date' => '1453886645',
            'log'         => 'stuff',
        ]);

        self::assertEquals(Git_Driver_Gerrit_ProjectCreatorStatus::DONE, $this->gerrit_status->getStatus($this->repository));
    }

    public function testItHasStatusDoneWhenSystemEventIsDone(): void
    {
        $this->repository->method('isMigratedToGerrit')->willReturn(true);
        $this->repository->method('getMigrationStatus')->willReturn(null);

        $this->dao->method('getSystemEventForRepository')->willReturn([
            'status'      => SystemEvent::STATUS_DONE,
            'create_date' => '1453886645',
            'log'         => '',
        ]);

        self::assertEquals(Git_Driver_Gerrit_ProjectCreatorStatus::DONE, $this->gerrit_status->getStatus($this->repository));
    }

    public function testItHasStatusDoneWhenRepositoryIsExplicitlyMarkedAsDone(): void
    {
        $this->repository->method('isMigratedToGerrit')->willReturn(true);
        $this->repository->method('getMigrationStatus')->willReturn(Git_Driver_Gerrit_ProjectCreatorStatus::DONE);

        $this->dao->method('getSystemEventForRepository')->willReturn(null);

        self::assertEquals(Git_Driver_Gerrit_ProjectCreatorStatus::DONE, $this->gerrit_status->getStatus($this->repository));
    }

    public function testItHasStatusQueueWhenSystemEventIsNew(): void
    {
        $this->repository->method('isMigratedToGerrit')->willReturn(false);
        $this->repository->method('getMigrationStatus')->willReturn(null);

        $this->dao->method('getSystemEventForRepository')->willReturn([
            'status'      => SystemEvent::STATUS_NEW,
            'create_date' => '1453886645',
            'log'         => '',
        ]);

        self::assertEquals(Git_Driver_Gerrit_ProjectCreatorStatus::QUEUE, $this->gerrit_status->getStatus($this->repository));
    }

    public function testItHasStatusQueueWhenSystemEventIsRunning(): void
    {
        $this->repository->method('isMigratedToGerrit')->willReturn(true);
        $this->repository->method('getMigrationStatus')->willReturn(null);

        $this->dao->method('getSystemEventForRepository')->willReturn([
            'status'      => SystemEvent::STATUS_RUNNING,
            'create_date' => '1453886645',
            'log'         => '',
        ]);

        self::assertEquals(Git_Driver_Gerrit_ProjectCreatorStatus::QUEUE, $this->gerrit_status->getStatus($this->repository));
    }

    public function testItHasStatusNullWhenRepositoryIsNotMigrated(): void
    {
        $this->repository->method('isMigratedToGerrit')->willReturn(false);
        $this->repository->method('getMigrationStatus')->willReturn(Git_Driver_Gerrit_ProjectCreatorStatus::DONE);
        $this->dao->method('getSystemEventForRepository');

        self::assertNull($this->gerrit_status->getStatus($this->repository));
    }
}
