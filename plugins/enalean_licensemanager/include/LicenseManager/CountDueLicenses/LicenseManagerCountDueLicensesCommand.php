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

namespace Tuleap\Enalean\LicenseManager\CountDueLicenses;

use ProjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use UserManager;

class LicenseManagerCountDueLicensesCommand extends Command
{
    public const NAME = 'license-manager:count-due-licenses';

    /**
     * @var \UserDao
     */
    private $user_dao;

    /**
     * @var DueLicencesDao
     */
    private $licenses_dao;

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var string
     */
    private $config_path;

    public function __construct(
        \UserDao $user_dao,
        DueLicencesDao $licenses_dao,
        UserManager $user_manager,
        string $etc_root_path,
    ) {
        parent::__construct(self::NAME);
        $this->user_dao     = $user_dao;
        $this->licenses_dao = $licenses_dao;
        $this->user_manager = $user_manager;
        $this->config_path  = $etc_root_path . '/visitors_parameters.json';
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if (! $this->licenses_dao->doesUserlogTableExists()) {
            $output->writeln("<error>Plugin userlog must be installed and enabled.</error>");
            return 1;
        }

        if (! is_file($this->config_path)) {
            $config_file_path = $this->config_path;

            $output->writeln("<error>Config file $config_file_path not found</error>");
            return 1;
        }

        $visitors_paramaters   = $this->getVisitorsParameters();
        $visitors_project_ids  = $visitors_paramaters['visitors_project_id'];
        $history_file_location = (string) $visitors_paramaters['csv_history_file_location'];

        if (! $visitors_project_ids || empty($visitors_project_ids) || ! $history_file_location) {
            $config_file_path = $this->config_path;

            $output->writeln("<error>visitors_project_ids and csv_history_file_location must be defined in $config_file_path</error>");
            return 1;
        }

        $visitors_project_ids = $this->getProjectIds($visitors_project_ids);


        foreach ($visitors_project_ids as $project_id) {
            if (! $this->doesProjectExists($project_id)) {
                $output->writeln('<error>The provided project ID' . (string) $project_id . ' does not match an existing project</error>');

                return 1;
            }
        }

        $controller = new CountDueLicensesController(
            $this->user_dao,
            $this->licenses_dao,
            $this->user_manager,
            $output,
            new UserEvolutionHistoryExporter($output, $history_file_location)
        );

        $controller->countDueLicences($visitors_project_ids);

        return 0;
    }

    protected function configure()
    {
        $this->setDescription('Count the number of active users, visitors and outputs the number of due licenses.');
    }

    private function getVisitorsParameters(): array
    {
        return json_decode(
            file_get_contents(
                $this->config_path
            ),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    private function doesProjectExists(int $project_id): bool
    {
        $project = (ProjectManager::instance())->getProject($project_id);

        return ! $project->isError();
    }

    /**
     * @param array | int | string $visitors_project_ids
     * @return int[]
     */
    private function getProjectIds($visitors_project_ids): array
    {
        $visitors_project_ids = is_array($visitors_project_ids) ? $visitors_project_ids : [$visitors_project_ids];

        $project_ids = [];
        foreach ($visitors_project_ids as $project_id) {
            $project_ids[] = (int) $project_id;
        }

        return $project_ids;
    }
}
