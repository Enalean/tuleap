<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * I run git gc on gitolite admin working copy
 */
class Git_GitoliteHousekeeping_GitoliteHousekeepingGitGc
{
    /** @var Git_GitoliteHousekeeping_GitoliteHousekeepingDao */
    private $dao;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var string */
    private $gitolite_admin_working_copy;

    public function __construct(
        Git_GitoliteHousekeeping_GitoliteHousekeepingDao $dao,
        \Psr\Log\LoggerInterface $logger,
        $gitolite_admin_working_copy,
    ) {
        $this->dao                         = $dao;
        $this->logger                      = $logger;
        $this->gitolite_admin_working_copy = $gitolite_admin_working_copy;
    }

    public function cleanUpGitoliteAdminWorkingCopy()
    {
        if ($this->dao->isGitGcEnabled()) {
            $this->logger->info('Running git gc on gitolite admin working copy.');
            $this->execGitGcAsAppAdm();
        } else {
            $this->logger->warning(
                'Cannot run git gc on gitolite admin working copy. ' .
                'Please run as root: /usr/share/tuleap/src/utils/php-launcher.sh ' .
                '/usr/share/tuleap/plugins/git/bin/gl-admin-housekeeping.php'
            );
        }
    }

    /**
     * @protected for testing purpose
     */
    protected function execGitGcAsAppAdm()
    {
        try {
            $admin_wc = rtrim($this->gitolite_admin_working_copy, '/');

            $process = new Process([
                '/usr/bin/sudo',
                '-u',
                'codendiadm',
                Git_Exec::getGitCommand(),
                '--work-tree',
                $admin_wc,
                '--git-dir',
                $admin_wc . '/.git',
                'gc',
            ]);
            $process->setTimeout(null);
            $process->mustRun();
            $this->logger->debug($process->getOutput());
        } catch (ProcessFailedException $exception) {
            $this->logger->error('git gc failure: ' .  $exception->getMessage());
            $this->logger->error($exception->getProcess()->getErrorOutput());
            throw $exception;
        }
    }
}
