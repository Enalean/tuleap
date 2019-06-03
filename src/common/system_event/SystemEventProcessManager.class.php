<?php
/**
 * Copyright Enalean (c) 2011 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
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

/**
 * Say if a SystemEventProcess is running, and create or delete its pid file
 */
class SystemEventProcessManager
{
    /**
     * @see http://www.php.net/manual/en/function.posix-kill.php#49596
     * @return bool
     */
    public function isAlreadyRunning(SystemEventProcess $process)
    {
        $pid_file = $process->getPidFile();
        if (file_exists($pid_file)) {
            $prev_pid = file_get_contents($pid_file);
            if (($prev_pid !== FALSE) && posix_kill((int) trim($prev_pid), SIG_DFL)) {
                // A program using this PID is currently running
                // It might be a PID number collision: check the program name
                $ps = new \Symfony\Component\Process\Process(['/bin/ps', '-ww', '-o', 'args=', '--pid', $prev_pid]);
                $ps->run();
                if ($ps->isSuccessful() && strpos($ps->getOutput(), $process->getCommandName()) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    public function createPidFile(SystemEventProcess $process)
    {
        if (@file_put_contents($process->getPidFile(), getmypid()) === false) {
            throw new Exception('Cannot write pid file, aborting');
        }
    }

    public function deletePidFile(SystemEventProcess $process)
    {
        unlink($process->getPidFile());
    }
}
