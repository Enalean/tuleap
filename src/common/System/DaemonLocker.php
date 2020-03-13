<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
 *
 */

namespace Tuleap\System;

use Tuleap\System\Exception\DaemonLockerException;

class DaemonLocker
{
    private $pid_file;

    public function __construct($pid_file)
    {
        $this->pid_file = $pid_file;
    }

    public function isRunning()
    {
        if (file_exists($this->pid_file)) {
            $pid = (int) trim(file_get_contents($this->pid_file));
            $ret = posix_kill($pid, SIG_DFL);
            if ($ret === true) {
                throw new DaemonLockerException("Application already running with pid $pid");
            } else {
                switch (posix_get_last_error()) {
                    case PCNTL_ESRCH:
                        unlink($this->pid_file);
                        break;
                    case PCNTL_EPERM:
                        throw new DaemonLockerException("Application already running with pid $pid (EPERM)");
                    default:
                        throw new DaemonLockerException("Application already running with pid $pid (" . posix_get_last_error() . ", " . posix_strerror(posix_get_last_error()) . ")\n");
                }
            }
        }
        file_put_contents($this->pid_file, getmypid());
    }

    public function cleanExit()
    {
        unlink($this->pid_file);
        exit(0);
    }
}
