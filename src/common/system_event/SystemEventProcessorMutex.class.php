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

use Symfony\Component\Lock\LockFactory;
use Tuleap\DB\DBConnection;

class SystemEventProcessorMutex
{
    private $process_owner;
    private $runnable;

    /**
     * @var LockFactory
     */
    private $lock_factory;

    /**
     * @var DBConnection
     */
    private $db_connection;

    public function __construct(
        IRunInAMutex $runnable,
        LockFactory $lock_factory,
        DBConnection $db_connection
    ) {
        $this->process_owner = $runnable->getProcessOwner();
        $this->runnable      = $runnable;
        $this->lock_factory  = $lock_factory;
        $this->db_connection = $db_connection;
    }

    /**
     * @throws Exception
     */
    public function execute()
    {
        $process = $this->getProcess();
        $lock = $this->lock_factory->createLock($process->getLockName());
        if ($lock->acquire()) {
            $this->runnable->execute($process->getQueue());
            $lock->release();
        }
    }

    /**
     * @throws Exception
     */
    public function waitAndExecute()
    {
        $process = $this->getProcess();
        $lock = $this->lock_factory->createLock($process->getLockName());
        if ($lock->acquire(true)) {
            $this->db_connection->reconnectAfterALongRunningProcess();
            $this->runnable->execute($process->getQueue());
            $lock->release();
        }
    }

    /**
     * @return SystemEventProcess
     * @throws Exception
     */
    private function getProcess()
    {
        $this->checkCurrentUserProcessOwner();
        return $this->runnable->getProcess();
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function checkCurrentUserProcessOwner()
    {
        $current_process_username = $this->getCurrentProcessUserName();
        if ($current_process_username != $this->process_owner) {
            throw new Exception("Must be " . $this->process_owner . " to run this script (currently:$current_process_username)\n");
        }
        return true;
    }

    protected function getCurrentProcessUserName()
    {
        $process_user = posix_getpwuid(posix_geteuid());
        return $process_user['name'];
    }
}
