<?php
/**
 * Copyright Enalean (c) 2011, 2012, 2013, 2015. All rights reserved.
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

require_once 'IRunInAMutex.php';
require_once 'SystemEventProcessManager.class.php';

/**
 * Process stored events.
 *
 * How it works:
 * This script stores its process ID in /var/run/tuleap_process_system_event.pid,
 * and deletes it when it has finished.
 * If the PID file already exists, it means either that an instance is currently
 * running, or that a previous call to this script failed (and thus did not delete
 * the PID file). In this case, we send a signal to the process which PID is
 * in the file. If it returns TRUE, it means that there is a process
 * with this PID running. Then, we check that the command running with this PID
 * contains the name of this script (process_system_events.php). In this case,
 * we simply exit.
 */
class SystemEventProcessorMutex {
    private $process_owner;
    private $runnable;

    public function __construct(SystemEventProcessManager $process_manager, IRunInAMutex $runnable) {
        $this->process_owner = $runnable->getProcessOwner();
        $this->runnable        = $runnable;
        $this->process_manager = $process_manager;
    }

    public function execute() {
        $process = $this->getProcess();
        if (! $this->process_manager->isAlreadyRunning($process)) {
            $this->executeWithPidFile($process);
        }
    }

    public function waitAndExecute() {
        $process = $this->getProcess();
        while ($this->process_manager->isAlreadyRunning($process)) {
            sleep(1);
        }
        $this->executeWithPidFile($process);
    }

    private function getProcess() {
        $this->checkCurrentUserProcessOwner();
        return $this->runnable->getProcess();
    }

    private function executeWithPidFile(SystemEventProcess $process) {
        $this->process_manager->createPidFile($process);
        $this->runnable->execute($process->getQueue());
        $this->process_manager->deletePidFile($process);
    }

    protected function checkCurrentUserProcessOwner() {
        $current_process_username = $this->getCurrentProcessUserName();
        if ($current_process_username != $this->process_owner) {
            throw new Exception("Must be ".$this->process_owner." to run this script (currently:$current_process_username)\n");
        }
        return true;
    }

    protected function getCurrentProcessUserName() {
        $process_user = posix_getpwuid(posix_geteuid());
        return $process_user['name'];
    }
}
