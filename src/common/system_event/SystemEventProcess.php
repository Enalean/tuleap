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

interface SystemEventProcess
{
    /**
     * @return string
     */
    public function getQueue();

    /**
     * Return the lock identifier
     *
     * @return string
     */
    public function getLockName();

    /**
     * Return the command name that match the process to launch
     *
     * This string will be used to ensure that the `pid` of a running process corresponds to a command that looks like
     * a process that is at the origin of the pid.
     *
     * Let say `getPidFile` says that we "are" process 9888
     * if we look at the process table and find process 9888 with name "firefox" it's likely that original command
     * was killed/OMMed/crashed and the pid was re-attributed to another process.
     * Hence we can safely start another instance of the command
     *
     * Beware with the "looks like", we don't have good means to ensure that a given pid actually corresponds to an
     * instance of "the same app" so we do string compare on names to command name should be precise enough to not match
     * everything (command would never been launched) and generic enough to not match nothing (command would be launched
     * in parallel everytime).
     *
     * @return string
     */
    public function getCommandName();
}
