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

use DateTimeImmutable;
use PFUser;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use UserDao;
use UserManager;

class CountDueLicensesController
{
    /**
     * @var UserDao
     */
    private $user_dao;

    /**
     * @var DueLicencesDao
     */
    private $due_licences_dao;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var UserEvolutionHistoryExporter
     */
    private $history_exporter;

    public function __construct(
        UserDao $user_dao,
        DueLicencesDao $due_licences_dao,
        UserManager $user_manager,
        OutputInterface $output,
        UserEvolutionHistoryExporter $history_exporter,
    ) {
        $this->user_dao         = $user_dao;
        $this->due_licences_dao = $due_licences_dao;
        $this->user_manager     = $user_manager;
        $this->output           = $output;
        $this->history_exporter = $history_exporter;
    }

    /**
     * @param int[] $project_ids
     */
    public function countDueLicences(array $project_ids): void
    {
        $current_timestamp = new DateTimeImmutable("now");

        $active_users = $this->user_dao->searchByStatus(PFUser::STATUS_ACTIVE);

        $this->output->write("<info>- Retrieving user accesses</info>");

        $real_users     = $this->due_licences_dao->getRealUsers($project_ids);
        $real_users_ids = array_column($real_users, 'user_id');

        $this->output->writeln("<info> ... done</info>");
        $this->output->writeln("<info>- Counting users projects</info>");

        $nb_active_users = count($active_users);

        $progress_bar = new ProgressBar($this->output, $nb_active_users);

        foreach ($active_users as $active_user) {
            $progress_bar->advance();

            $user_id = $active_user['user_id'];
            $user    = $this->user_manager->getUserById($user_id);

            if (! $user) {
                continue;
            }

            $user_projects = $user->getProjects();

            if (count($user_projects) > 0 && ! in_array($user_id, $real_users_ids)) {
                $real_users[] = $active_user;
            }
        }

        $progress_bar->clear();

        $nb_real_users = count($real_users);

        $this->displayResults($current_timestamp, $nb_active_users, $nb_real_users, $real_users);
        $this->history_exporter->exportHistory($current_timestamp, $nb_active_users, $nb_real_users);
    }

    private function displayResults(
        DateTimeImmutable $current_timestamp,
        int $nb_active_users,
        int $nb_real_users,
        array $real_users,
    ): void {
        $this->output->writeln('###############################################');
        $this->output->writeln('Date: ' . $current_timestamp->format(DateTimeImmutable::ATOM));
        $this->output->writeln('Number of active users: ' . $nb_active_users);
        $this->output->writeln('Number of visitors: ' . ($nb_active_users - $nb_real_users));
        $this->output->writeln('Number of licences required : ' . $nb_real_users);
        $this->output->writeln('###############################################');
        $this->output->writeln('Licenses required for: (id, user_name, realname, email)');


        foreach ($real_users as $user) {
            $this->output->writeln(
                $user["user_id"]
                . ' | '
                . $user["user_name"]
                . ' | '
                . $user["realname"]
                . ' | '
                . $user["email"]
            );
        }

        $this->output->writeln('');
    }
}
