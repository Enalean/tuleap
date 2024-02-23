<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\SVN\AccessControl;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\SVNCore\SVNAccessFileReader;

class SVNCheckRepositoriesWithDuplicatedAccessFileSections extends Command
{
    public const NAME = 'svn:repositories-with-duplicated-sections-access-file';

    public function __construct(
        private readonly RepositoryManager $repository_manager,
        private readonly SVNAccessFileReader $access_file_reader,
        private readonly DuplicateSectionDetector $duplicate_section_detector,
    ) {
        parent::__construct(self::NAME);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("<comment>Start checking of duplicated sections in platform access files:</comment>");

        $all_active_repositories      = $this->repository_manager->getRepositoriesOfNonDeletedProjects();
        $repositories_with_duplicates = [];

        foreach ($all_active_repositories as $repository_by_project_collection) {
            foreach ($repository_by_project_collection->getRepositoryList() as $repository) {
                $svn_access_file_content = $this->access_file_reader->getAccessFileContent($repository);
                $collection_of_faults    = $this->duplicate_section_detector->inspect($svn_access_file_content);
                if (iterator_count($collection_of_faults) > 0) {
                    $repositories_with_duplicates[] = $repository;
                }
            }
        }

        $total_number_of_repositories_with_duplicates = count($repositories_with_duplicates);
        if ($total_number_of_repositories_with_duplicates === 0) {
            $output->writeln("<comment>No duplicated sections in platform access files found.</comment>");

            return 0;
        }

        $table = new Table($output);
        $table->setHeaders(["Project Id", "Project name", 'Repository']);
        $table->setStyle('box');

        foreach ($repositories_with_duplicates as $repository) {
            $project = $repository->getProject();
            $table->addRow([$project->getId(), $project->getUnixName(), $repository->getName()]);
        }

        $table->render();

        $output->writeln("<info>" . $total_number_of_repositories_with_duplicates . " SVN access files with duplicated sections found.</info>");
        return 0;
    }

    protected function configure()
    {
        $this->setDescription('List SVN multi repository access files with duplicated sections.');
    }
}
