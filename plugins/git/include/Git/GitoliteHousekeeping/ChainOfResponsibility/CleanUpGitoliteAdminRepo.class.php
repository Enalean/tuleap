<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

use Symfony\Component\Process\Process;

/**
 * I do the real stuff: backuping admin repo and cloning a fresh one
 */
class Git_GitoliteHousekeeping_ChainOfResponsibility_CleanUpGitoliteAdminRepo extends Git_GitoliteHousekeeping_ChainOfResponsibility_Command
{

    /** @var Git_GitoliteHousekeeping_GitoliteHousekeepingResponse */
    private $response;

    /** @var string */
    private $gitolite_var_path;

    /** @var string */
    private $remote_admin_repository;

    /** @var string */
    private $execute_as = 'codendiadm';

    public function __construct(
        Git_GitoliteHousekeeping_GitoliteHousekeepingResponse $response,
        $gitolite_var_path,
        $remote_admin_repository
    ) {
        parent::__construct();
        $this->response                = $response;
        $this->gitolite_var_path       = $gitolite_var_path;
        $this->remote_admin_repository = $remote_admin_repository;
    }

    public function execute(): void
    {
        $admin_dir  = 'admin';
        $backup_dir = $this->gitolite_var_path . '/admin.old';
        if (is_dir($backup_dir)) {
            $this->response->error("The gitolite backup dir $backup_dir already exists. Please remove it.");
            $this->response->abort();
            return;
        }

        $this->response->info("Moving $admin_dir to $backup_dir and cloning $this->remote_admin_repository");
        $cmd = '(cd ' . escapeshellarg($this->gitolite_var_path) . ' && mv ' . escapeshellarg($admin_dir) . ' ' .  escapeshellarg($backup_dir) .
            ' && git clone ' . escapeshellarg($this->remote_admin_repository) . ' '  . escapeshellarg($admin_dir) . ')';
        $this->executeCmd($cmd);

        $this->executeNextCommand();
    }

    /**
     * Used by unit tests to bypass the fact that clone must be done by codendiadm
     */
    public function clearExecuteAs()
    {
        $this->execute_as = null;
    }

    private function executeCmd(string $cmd): void
    {
        if ($this->execute_as) {
            $cmd = "su -c '\$COMMAND' - \$EXECUTE_AS";
        }

        $process = Process::fromShellCommandline($cmd);
        $process->mustRun(null, ['COMMAND' => $cmd, 'EXECUTE_AS' => $this->execute_as ?? '']);
    }
}
