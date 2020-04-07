<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Git\Repository;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class RepositoriesWithObjectsOverTheLimitCommand extends Command
{
    public const NAME = 'git:repositories-with-object-over-the-size-limit';

    /**
     * @var \GitRepositoryFactory
     */
    private $repository_factory;
    /**
     * @var GitRepositoryObjectsSizeRetriever
     */
    private $repository_objects_size_retriever;

    public function __construct(
        \GitRepositoryFactory $repository_factory,
        GitRepositoryObjectsSizeRetriever $repository_objects_size_retriever
    ) {
        parent::__construct(self::NAME);
        $this->repository_factory                = $repository_factory;
        $this->repository_objects_size_retriever = $repository_objects_size_retriever;
    }

    protected function configure(): void
    {
        $this->setDescription('Search for repositories active in the last 2 months with objects over the size limit')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'The output format (txt or json)', 'txt');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        switch ($format = $input->getOption('format')) {
            case 'txt':
                $this->displayAsText($output);
                break;
            case 'json':
                $this->displayAsJSON($output);
                break;
            default:
                throw new \RuntimeException(sprintf('Unsupported format "%s". See help for supported formats.', $format));
        }

        return 0;
    }

    /**
     * @return LargestObjectSizeGitRepository[]
     */
    private function getRepositoriesWithLargestObjectSize(OutputInterface $output): array
    {
        $repositories                = $this->repository_factory->getAllRepositoriesWithActivityInTheLast2Months();
        $repositories_over_the_limit = [];

        $progress_bar = new ProgressBar($output, count($repositories));
        $progress_bar->start();
        foreach ($repositories as $repository) {
            $repository_with_largest_object_size = $this->repository_objects_size_retriever->getLargestObjectSize($repository);
            if ($repository_with_largest_object_size->isOverTheObjectSizeLimit()) {
                $repositories_over_the_limit[] = $repository_with_largest_object_size;
            }
            $progress_bar->advance();
        }
        $progress_bar->finish();
        $progress_bar->clear();

        return $repositories_over_the_limit;
    }

    private function displayAsText(OutputInterface $output): void
    {
        $table_rows = [];
        foreach ($this->getRepositoriesWithLargestObjectSize($output) as $repository_over_the_limit) {
            $repository   = $repository_over_the_limit->getRepository();
            $table_rows[] = [
                $repository->getProjectId(),
                $repository->getProject()->getUnixName(),
                $repository->getId(),
                $repository->getFullName(),
                $repository_over_the_limit->getLargestObjectSize()
            ];
        }

        $table = new Table($output);
        $table->setHeaders(['Project ID', 'Project name', 'Repository ID', 'Repository name', 'Object size'])
            ->setRows($table_rows);

        $table->render();
    }

    private function displayAsJSON(OutputInterface $output): void
    {
        $rows = [];
        foreach ($this->getRepositoriesWithLargestObjectSize($output) as $repository_over_the_limit) {
            $repository = $repository_over_the_limit->getRepository();
            $rows[]     = [
                'project_id' => $repository->getProjectId(),
                'project_unixname' => $repository->getProject()->getUnixName(),
                'repository_id' => $repository->getId(),
                'repository_name' => $repository->getFullName(),
                'object_size' => $repository_over_the_limit->getLargestObjectSize()
            ];
        }
        $output->write(json_encode($rows));
    }
}
