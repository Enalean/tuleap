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
 */

namespace Tuleap;

use ForgeConfig;
use GitRepository;
use Psr\Log\LoggerInterface;
use System_Command;
use System_Command_CommandException;
use Tuleap\Project\XML\Export\ArchiveInterface;

class GitBundle
{
    /**
     * @var System_Command
     */
    private $system_command;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(System_Command $system_command, LoggerInterface $logger)
    {
        $this->system_command = $system_command;
        $this->logger         = $logger;
    }

    public function dumpRepository(
        GitRepository $repository,
        ArchiveInterface $archive,
        $temporary_dump_path_on_filesystem
    ) {
        try {
            if ($this->doesRepositoryHaveCommits($repository) === false) {
                $this->logger->debug('[git ' . $repository->getName() . '] no commit found bundle not created');

                return true;
            }

            $file_name          = $repository->getName() . '.bundle';
            $repository_path    = $repository->getFullPath();
            $path_to_filesystem = "$temporary_dump_path_on_filesystem/$file_name";

            $this->logger->info('Create git bundle for repository ' . $repository->getName());

            $this->createDumpPath($temporary_dump_path_on_filesystem);
            $this->createBundle(
                $repository,
                $repository_path,
                $file_name,
                $temporary_dump_path_on_filesystem,
                $archive
            );
            $this->addRepositoryToArchive($archive, 'export/' . $file_name, $path_to_filesystem);

            $this->logger->debug(
                '[git ' . $repository->getName() . '] Backup done in [ ' . "$path_to_filesystem" . ']'
            );
        } catch (System_Command_CommandException $e) {
            foreach ($e->output as $line) {
                $this->logger->error('[git ' . $repository->getName() . '] git-bundle: ' . $line);
            }
            $this->logger->error(
                '[git ' . $repository->getName() . '] git-bundle returned with status ' . $e->return_value
            );
        }
    }

    private function createBundle(
        GitRepository $repository,
        $repository_path,
        $file_name,
        $dump_path_on_filesystem,
        ArchiveInterface $archive
    ) {
        $command = "umask 77 && cd " . escapeshellarg($repository_path) .
            " && git bundle create " . escapeshellarg($file_name) . " --all" .
            " && mv " . escapeshellarg($file_name) . " " . escapeshellarg($dump_path_on_filesystem);

        $this->system_command->exec($command);

        if (is_dir($archive->getArchivePath())) {
            $command = "chmod -R 755 " . escapeshellarg($archive->getArchivePath()) . "/export";

            $this->system_command->exec($command);
        }
        $this->logger->debug('[git ' . $repository->getName() . '] git bundle: success' . $repository_path);
    }

    private function createDumpPath($dump_path_on_filesystem)
    {
        $dump_path_on_filesystem = escapeshellarg($dump_path_on_filesystem);

        $this->logger->debug($dump_path_on_filesystem);
        $command = "umask 77 && mkdir -p $dump_path_on_filesystem";
        $this->system_command->exec($command);

        $command = "chown " . escapeshellarg(ForgeConfig::get('sys_http_user')) . ":" . escapeshellarg(ForgeConfig::get('sys_http_user')) .
            " $dump_path_on_filesystem && chmod 750 $dump_path_on_filesystem";
        $this->system_command->exec($command);
    }

    private function addRepositoryToArchive(ArchiveInterface $archive, $path_in_archive, $path_to_filesystem)
    {
        $archive->addFile($path_in_archive, $path_to_filesystem);
    }

    private function doesRepositoryHaveCommits(GitRepository $repository)
    {
        $repository_path = escapeshellarg($repository->getFullPath());
        $command         = 'find ' . $repository_path . '/objects -type f | wc -l';
        $output          = $this->system_command->exec($command);

        return isset($output[0]) && $output[0] !== "0";
    }
}
