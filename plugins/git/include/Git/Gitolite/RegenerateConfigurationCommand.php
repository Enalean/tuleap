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

namespace Tuleap\Git\Gitolite;

use Git_SystemEventManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RegenerateConfigurationCommand extends Command
{
    public const NAME = 'git:regenerate-gitolite-configuration';

    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var Git_SystemEventManager
     */
    private $system_event_manager;

    public function __construct(\ProjectManager $project_manager, Git_SystemEventManager $system_event_manager)
    {
        parent::__construct(self::NAME);

        $this->project_manager      = $project_manager;
        $this->system_event_manager = $system_event_manager;
    }

    protected function configure(): void
    {
        $this->setDescription('Force the re-generation of the Gitolite configuration')
            ->addArgument(
                'project_ids',
                InputArgument::IS_ARRAY,
                'List of project IDs (separated by a space)'
            )
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'Force the re-generation of the configuration for all projects'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $should_all_configurations_be_regenerated = $input->getOption('all');
        if ($should_all_configurations_be_regenerated) {
            return $this->regenerateAllConfigurations($output);
        }

        return $this->regenerateConfigurationsOfSomeProjects($output, $input->getArgument('project_ids'));
    }

    private function regenerateAllConfigurations(OutputInterface $output): int
    {
        $projects    = $this->project_manager->getProjectsByStatus(\Project::STATUS_ACTIVE);
        $project_ids = [];
        foreach ($projects as $project) {
            $project_ids[] = $project->getID();
        }
        $this->system_event_manager->queueProjectsConfigurationUpdate($project_ids);

        $output->writeln('<info>Gitolite configuration for all active projects will shortly be re-generated</info>');
        return 0;
    }

    /**
     * @param string[] $project_ids
     */
    private function regenerateConfigurationsOfSomeProjects(OutputInterface $output, array $project_ids): int
    {
        $verified_project_ids = [];
        foreach ($project_ids as $project_id) {
            try {
                $project = $this->project_manager->getValidProject((int) $project_id);
                if (! $project->isActive()) {
                    $output->writeln('<comment>Project #' . $project_id . ' is not active, it will be ignored</comment>');
                    continue;
                }
                $verified_project_ids[] = $project_id;
            } catch (\Project_NotFoundException $exception) {
                $output->writeln('<error>Project #' . OutputFormatter::escape($project_id) . ' can not be found</error>');
                return 1;
            }
        }

        if (count($verified_project_ids) === 0) {
            $output->writeln([
                '<comment>No active project ID has been specified to re-generate Gitolite configuration</comment>',
                '',
                '<comment>' . OutputFormatter::escape($this->getSynopsis()) . '</comment>'
            ]);
            return 0;
        }

        $this->system_event_manager->queueProjectsConfigurationUpdate($verified_project_ids);
        $output->writeln('<info>Gitolite configuration will shortly be re-generated</info>');

        return 0;
    }
}
