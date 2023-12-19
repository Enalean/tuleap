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

namespace Tuleap\SVN\AccessControl;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\SVN\Repository\RepositoryManager;

class SVNRefreshAllAccessFilesCommand extends Command
{
    public const NAME = 'svn:refresh-projects-access-files';

    /**
     * @var RepositoryManager
     */
    private $repository_manager;
    /**
     * @var AccessFileHistoryFactory
     */
    private $access_file_history_factory;
    /**
     * @var AccessFileHistoryCreator
     */
    private $access_file_history_creator;

    public function __construct(
        RepositoryManager $repository_manager,
        AccessFileHistoryFactory $access_file_history_factory,
        AccessFileHistoryCreator $access_file_history_creator,
    ) {
        $this->repository_manager          = $repository_manager;
        $this->access_file_history_factory = $access_file_history_factory;
        $this->access_file_history_creator = $access_file_history_creator;

        parent::__construct(self::NAME);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("<comment>Start refresh access files:</comment>");

        $collection              = $this->repository_manager->getRepositoriesOfNonDeletedProjects();
        $number_updated_projects = count($collection);

        if ($number_updated_projects === 0) {
            $output->writeln("<comment>No SVN multi-repositories found.</comment>");
            $output->writeln("<comment>End of refresh access files.</comment>");

            return 0;
        }

        $count_refreshed_repositories = 0;

        $table = new Table($output);
        $table->setHeaders(["Project Id", "Project name", 'Repository']);
        $table->setStyle('box');

        foreach ($collection as $repository_by_project_collection) {
            $project = $repository_by_project_collection->getProject();
            $table->addRow([$project->getId(), $project->getUnixName(), null]);

            $this->refreshRepositories($repository_by_project_collection->getRepositoryList(), $table);
            $count_refreshed_repositories += count($repository_by_project_collection->getRepositoryList());
        }

        $table->render();

        $output->writeln("<info>" . $count_refreshed_repositories . " SVN access files restored.</info>");
        return 0;
    }

    private function refreshRepositories(array $project_repositories, Table $table): void
    {
        foreach ($project_repositories as $repository) {
            $current_version = $this->access_file_history_factory->getCurrentVersion($repository);
            $this->access_file_history_creator->saveAccessFile($repository, $current_version);

            $table->addRow([null, null, $repository->getName()]);
        }
    }

    protected function configure()
    {
        $this->setDescription('restore SVN multi repository access files.');
    }
}
