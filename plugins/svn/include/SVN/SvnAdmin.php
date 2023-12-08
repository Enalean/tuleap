<?php
/**
 * Copyright Enalean (c) 2016 - Present. All rights reserved.
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

namespace Tuleap\SVN;

use System_Command;
use Tuleap\SVNCore\Repository;
use Psr\Log\LoggerInterface;
use ForgeConfig;
use System_Command_CommandException;
use BackendSVN;

class SvnAdmin
{
    /** @var System_Command */
    private $system_command;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @var BackendSVN
     */
    private $backend_svn;

    public function __construct(
        System_Command $system_command,
        LoggerInterface $logger,
        BackendSVN $backend_svn,
    ) {
        $this->system_command = $system_command;
        $this->logger         = $logger;
        $this->backend_svn    = $backend_svn;
    }

    public function dumpRepository(Repository $repository, $dump_path)
    {
        try {
            $this->createDumpPath($dump_path);
            $this->svnAdminDumpRepository($repository, $dump_path);
            $this->removeReadPermissions($repository, $dump_path);

            $dump_name = $repository->getBackupFileName();
            $this->logger->debug('[svn ' . $repository->getName() . '] Backup done in [ ' . "$dump_path/$dump_name" . ']');
        } catch (System_Command_CommandException $e) {
            foreach ($e->output as $line) {
                $this->logger->error('[svn ' . $repository->getName() . '] svnadmin: ' . $line);
            }
            $this->logger->error('[svn ' . $repository->getName() . '] svnadmin returned with status ' . $e->return_value);
        }
    }

    private function removeReadPermissions(Repository $repository, $dump_path)
    {
        $dump_name = escapeshellarg($dump_path . "/" . $repository->getBackupFileName());
        $command   = "chown " . ForgeConfig::get('sys_http_user') . ":" . ForgeConfig::get('sys_http_user') .
            " $dump_name && chmod 640 $dump_name";
        $this->system_command->exec($command);
    }

    private function svnAdminDumpRepository(Repository $repository, $dump_path)
    {
        $system_path = escapeshellarg($repository->getSystemPath());
        $dump_name   = escapeshellarg($dump_path . "/" . $repository->getBackupFileName());

        $command = "umask 77 && svnadmin dump --quiet $system_path > $dump_name";
        $this->system_command->exec($command);
        $this->logger->info('[svn ' . $repository->getName() . '] svnadmin: dump success');
    }

    private function createDumpPath($dump_path)
    {
        $dump_path = escapeshellarg($dump_path);

        $command = "umask 77 && mkdir -p $dump_path";
        $this->system_command->exec($command);

        $command = "chown " . ForgeConfig::get('sys_http_user') . ":" . ForgeConfig::get('sys_http_user') .
            " $dump_path && chmod 750 $dump_path";
        $this->system_command->exec($command);
    }

    public function importRepository(Repository $repository)
    {
        $system_path = escapeshellarg($repository->getSystemPath());
        $dump_name   = escapeshellarg($repository->getBackupFileName());
        $dump_path   = escapeshellarg($repository->getSystemBackupPath());
        $dump_file   = $dump_path . "/" . $dump_name;
        $permissions = escapeshellarg(ForgeConfig::get('sys_http_user') . ":" . $repository->getProject()->getUnixName());

        try {
            $command        = "su -l " . ForgeConfig::get('sys_http_user') . " -c 'svnadmin create " . $system_path . "'";
            $command_output = $this->system_command->exec($command);
            foreach ($command_output as $line) {
                $this->logger->debug('[svn ' . $repository->getName() . '] svnadmin create: ' . $line);
            }

            $command        = "su -l " . ForgeConfig::get('sys_http_user') . " -c 'cd $system_path && umask 77 && svnadmin load . < $dump_file'";
            $command_output = $this->system_command->exec($command);
            foreach ($command_output as $line) {
                $this->logger->debug('[svn ' . $repository->getName() . '] svnadmin load: ' . $line);
            }

            $command = "chown -R $permissions $system_path";
            $this->logger->debug($command);
            $command_output = $this->system_command->exec($command);
            foreach ($command_output as $line) {
                $this->logger->debug('[svn ' . $repository->getName() . '] svnadmin load: ' . $line);
            }

            $this->backend_svn->updateHooks(
                $repository->getProject(),
                $repository->getSystemPath(),
                true,
                ForgeConfig::get('tuleap_dir') . '/plugins/svn/bin/',
                'svn_post_commit.php',
                ForgeConfig::get('tuleap_dir') . '/src/utils/php-launcher.sh',
                'svn_pre_commit.php'
            );

            $this->logger->debug('[svn ' . $repository->getName() . '] svnadmin restore: done');
        } catch (System_Command_CommandException $e) {
            foreach ($e->output as $line) {
                $this->logger->error('[svn ' . $repository->getName() . '] svnadmin: ' . $line);
            }
            $this->logger->error('[svn ' . $repository->getName() . '] svnadmin returned with status ' . $e->return_value);
        }
    }
}
