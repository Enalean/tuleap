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
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\SVNCore\Repository;
use Tuleap\SVNCore\SVNAccessFileReader;

final class SVNCheckRepositoriesWithDuplicatedAccessFileSections extends Command
{
    public const NAME = 'svn:repositories-with-duplicated-sections-access-file';

    public function __construct(
        private readonly RepositoryManager $repository_manager,
        private readonly SVNAccessFileReader $access_file_reader,
        private readonly DuplicateSectionDetector $duplicate_section_detector,
    ) {
        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        $this->setDescription('List SVN multi repository access files with duplicated sections.')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'The output format (txt or json)', 'txt');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        match ($format = $input->getOption('format')) {
            'txt' => $this->displayAsText($output),
            'json' => $this->displayAsJSON($output),
            default => throw new \RuntimeException(sprintf('Unsupported format "%s". See help for supported formats.', $format)),
        };

        return self::SUCCESS;
    }

    /**
     * @psalm-return list<Repository>
     */
    private function getRepositoriesWithDuplicatedSectionInAccessFile(OutputInterface $output): array
    {
        $all_active_repositories = $this->repository_manager->getRepositoriesOfNonDeletedProjects();

        $all_active_repositories_count = 0;
        foreach ($all_active_repositories as $repository_by_project_collection) {
            $all_active_repositories_count += count($repository_by_project_collection->getRepositoryList());
        }

        $progress_bar = new ProgressBar($output, $all_active_repositories_count);
        $progress_bar->start();

        $repositories_with_issue = [];

        foreach ($all_active_repositories as $repository_by_project_collection) {
            foreach ($repository_by_project_collection->getRepositoryList() as $repository) {
                $svn_access_file_content = $this->access_file_reader->getAccessFileContent($repository);
                $collection_of_faults    = $this->duplicate_section_detector->inspect($svn_access_file_content);
                if (iterator_count($collection_of_faults) > 0) {
                    $repositories_with_issue[] = $repository;
                }

                $progress_bar->advance();
            }
        }

        $progress_bar->finish();
        $progress_bar->clear();

        return $repositories_with_issue;
    }

    private function displayAsText(OutputInterface $output): void
    {
        $table_rows   = [];
        $repositories = $this->getRepositoriesWithDuplicatedSectionInAccessFile($output);

        if (count($repositories) <= 0) {
            $output->writeln("<info>No duplicated sections in platform access files found.</info>");
            return;
        }

        foreach ($repositories as $repository) {
            $project      = $repository->getProject();
            $table_rows[] = [
                $project->getID(),
                $project->getUnixName(),
                $repository->getId(),
                $repository->getName(),
            ];
        }

        $table = new Table($output);
        $table->setHeaders(['Project ID', 'Project name', 'Repository ID', 'Repository name'])
            ->setRows($table_rows);

        $table->render();

        $output->writeln("<info>" . count($repositories) . " SVN access files with duplicated sections found.</info>");
    }

    private function displayAsJSON(OutputInterface $output): void
    {
        $rows = [];
        foreach ($this->getRepositoriesWithDuplicatedSectionInAccessFile($output) as $repository) {
            $project = $repository->getProject();

            $rows[] = [
                'project_id' => (int) $project->getID(),
                'project_unixname' => $project->getUnixName(),
                'repository_id' => $repository->getId(),
                'repository_name' => $repository->getName(),
            ];
        }
        $output->write(OutputFormatter::escape(\Psl\Json\encode($rows)));
    }
}
