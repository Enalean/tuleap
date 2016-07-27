<?php
/**
 * Copyright Enalean (c) 2016. All rights reserved.
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

namespace Tuleap\Svn;

use System_Command;
use Tuleap\Svn\Repository\Repository;
use Logger;
use ForgeConfig;
use System_Command_CommandException;

class SvnAdmin
{
    /** @var System_Command */
    private $system_command;
    /** @var Logger */
    private $logger;

    public function __construct(System_Command $system_command, Logger $logger)
    {
        $this->system_command = $system_command;
        $this->logger         = $logger;
    }

    public function dumpRepository(Repository $repository)
    {
        $system_path = escapeshellarg($repository->getSystemPath());
        $dump_name   = escapeshellarg($repository->getBackupFileName());
        $dump_path   = escapeshellarg($repository->getSystemBackupPath());

        $commandline_folder = "mkdir -p $dump_path";

        try {
            $this->system_command->exec($commandline_folder);
        } catch (System_Command_CommandException $e) {
            $this->logger->error('[svn '.$repository->getName().'] svnadmin returned with status '.$e->return_value);
        }

        $commandline_dump = "svnadmin dump $system_path > $dump_path/$dump_name";

        try {
            $command_output = $this->system_command->exec($commandline_dump);
            foreach ($command_output as $line) {
                $this->logger->debug('[svn '.$repository->getName().'] svnadmin: '.$line);
            }
            $this->logger->debug('[svn '.$repository->getName().'] svnadmin returned with status 0');
        } catch (System_Command_CommandException $e) {
            foreach ($e->output as $line) {
                $this->logger->error('[svn '.$repository->getName().'] svnadmin: '.$line);
            }
            $this->logger->error('[svn '.$repository->getName().'] svnadmin returned with status '.$e->return_value);
        }

    }
}
