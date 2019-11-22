<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\FRS;

use DirectoryIterator;
use ProjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CorrectFrsRepositoryPermissionsCommand extends Command
{
    public const NAME = 'frs:correct-repository-permissions';

    /**
     * @var DirectoryIterator
     */
    private $directory;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(DirectoryIterator $directory, ProjectManager $project_manager)
    {
        $this->directory       = $directory;
        $this->project_manager = $project_manager;
        parent::__construct(self::NAME);
    }

    protected function configure()
    {
        $this->setDescription('Set the FRS repository with the originals permissions.');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $count_changes = 0;
        foreach ($this->directory as $file) {
            if ($file->isDot()) {
                continue;
            }

            if ($file->getFilename() === 'DELETED' && $file->getGroup() !== 496) {
                if (!chgrp($this->directory->getPath() . '/' . $file, 496)) {
                    $output->writeln("<error>Wrong permissions of $file has not been changed.</error>");
                    continue;
                }
                $count_changes++;
                $output->writeln("<info>Project permissions of $file has been changed.</info>");
            }

            $project = $this->project_manager->getProjectByUnixName($file->getFilename());

            if ($project && $project->getUnixGID() !== $file->getGroup()) {
                if (!chgrp($this->directory->getPath() . '/' . $file, $project->getUnixGID())) {
                    $output->writeln("<error>Wrong permissions of $file has not been changed.</error>");
                    continue;
                }
                $count_changes++;
                $output->writeln("<info>Project permissions of $file has been changed.</info>");
            }
        }
        if ($count_changes > 0) {
            $output->writeln("<info>$count_changes permissions has been changed.</info>");
            return 0;
        }
        $output->writeln("<info>No permissions has been changed.</info>");

        return 0;
    }
}
