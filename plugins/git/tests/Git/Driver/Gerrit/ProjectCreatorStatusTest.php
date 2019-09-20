<?php
/**
 * Copyright (c) Enalean, 2016-2019. All Rights Reserved.
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

require_once __DIR__ .'/../../../bootstrap.php';

class ProjectCreatorStatusTest extends TuleapTestCase
{

    private $dao;
    private $repository;
    private $gerrit_status;

    public function setUp()
    {
        parent::setUp();

        $this->dao        = \Mockery::spy(Git_Driver_Gerrit_ProjectCreatorStatusDao::class);
        $this->repository = mock('GitRepository');

        $this->gerrit_status = new Git_Driver_Gerrit_ProjectCreatorStatus($this->dao);
    }

    public function itIsStatusErrorWhenGitRepositoryIsError()
    {
        stub($this->repository)->isMigratedToGerrit()->returns(true);
        stub($this->repository)->getMigrationStatus()->returns(Git_Driver_Gerrit_ProjectCreatorStatus::ERROR);

        $this->assertEqual(Git_Driver_Gerrit_ProjectCreatorStatus::ERROR, $this->gerrit_status->getStatus($this->repository));
    }

    // We don't know what project did with their repo until this change
    // So we assume everything is OK.
    public function itHasStatusDoneWhenOnlySystemEventIsErrorForLegacy()
    {
        stub($this->repository)->isMigratedToGerrit()->returns(true);
        stub($this->repository)->getMigrationStatus()->returns(null);

        stub($this->dao)->getSystemEventForRepository()->returns(
            array(
                'status'      => SystemEvent::STATUS_ERROR,
                'create_date' => '1453886645',
                'log'         => 'stuff',
            )
        );

        $this->assertEqual(Git_Driver_Gerrit_ProjectCreatorStatus::DONE, $this->gerrit_status->getStatus($this->repository));
    }

    // We don't know what project did with their repo until this change
    // So we assume everything is OK.
    public function itHasStatusDoneWhenSystemEventIsNoLongerPartOfTheDB()
    {
        stub($this->repository)->isMigratedToGerrit()->returns(true);
        stub($this->repository)->getMigrationStatus()->returns(null);

        stub($this->dao)->getSystemEventForRepository()->returns(null);

        $this->assertEqual(Git_Driver_Gerrit_ProjectCreatorStatus::DONE, $this->gerrit_status->getStatus($this->repository));
    }

    // We don't know what project did with their repo until this change
    // So we assume everything is OK.
    public function itHasStatusDoneWhenOnlySystemEventIsWarningForLegacy()
    {
        stub($this->repository)->isMigratedToGerrit()->returns(true);
        stub($this->repository)->getMigrationStatus()->returns(null);

        stub($this->dao)->getSystemEventForRepository()->returns(
            array(
                'status'      => SystemEvent::STATUS_WARNING,
                'create_date' => '1453886645',
                'log'         => 'stuff',
            )
        );

        $this->assertEqual(Git_Driver_Gerrit_ProjectCreatorStatus::DONE, $this->gerrit_status->getStatus($this->repository));
    }

    public function itHasStatusDoneWhenSystemEventIsDone()
    {
        stub($this->repository)->isMigratedToGerrit()->returns(true);
        stub($this->repository)->getMigrationStatus()->returns(null);

        stub($this->dao)->getSystemEventForRepository()->returns(
            array(
                'status'      => SystemEvent::STATUS_DONE,
                'create_date' => '1453886645',
                'log'         => '',
            )
        );

        $this->assertEqual(Git_Driver_Gerrit_ProjectCreatorStatus::DONE, $this->gerrit_status->getStatus($this->repository));
    }

    public function itHasStatusDoneWhenRepositoryIsExplicitlyMarkedAsDone()
    {
        stub($this->repository)->isMigratedToGerrit()->returns(true);
        stub($this->repository)->getMigrationStatus()->returns(Git_Driver_Gerrit_ProjectCreatorStatus::DONE);

        stub($this->dao)->getSystemEventForRepository()->returns(null);

        $this->assertEqual(Git_Driver_Gerrit_ProjectCreatorStatus::DONE, $this->gerrit_status->getStatus($this->repository));
    }

    public function itHasStatusQueueWhenSystemEventIsNew()
    {
        stub($this->repository)->isMigratedToGerrit()->returns(false);
        stub($this->repository)->getMigrationStatus()->returns(null);

        stub($this->dao)->getSystemEventForRepository()->returns(
            array(
                'status'      => SystemEvent::STATUS_NEW,
                'create_date' => '1453886645',
                'log'         => '',
            )
        );

        $this->assertEqual(Git_Driver_Gerrit_ProjectCreatorStatus::QUEUE, $this->gerrit_status->getStatus($this->repository));
    }

    public function itHasStatusQueueWhenSystemEventIsRunning()
    {
        stub($this->repository)->isMigratedToGerrit()->returns(true);
        stub($this->repository)->getMigrationStatus()->returns(null);

        stub($this->dao)->getSystemEventForRepository()->returns(
            array(
                'status'      => SystemEvent::STATUS_RUNNING,
                'create_date' => '1453886645',
                'log'         => '',
            )
        );

        $this->assertEqual(Git_Driver_Gerrit_ProjectCreatorStatus::QUEUE, $this->gerrit_status->getStatus($this->repository));
    }

    public function itHasStatusNullWhenRepositoryIsNotMigrated()
    {
        stub($this->repository)->isMigratedToGerrit()->returns(false);
        stub($this->repository)->getMigrationStatus()->returns(Git_Driver_Gerrit_ProjectCreatorStatus::DONE);

        $this->assertEqual(null, $this->gerrit_status->getStatus($this->repository));
    }
}
