<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation;

use Feedback;
use Project;
use Tracker_Migration_MigrationManager;
use Tuleap\Tracker\Creation\JiraImporter\PendingJiraImportDao;

class OngoingCreationFeedbackNotifier
{
    /**
     * @var Tracker_Migration_MigrationManager
     */
    private $tv3_migration_manager;
    /**
     * @var PendingJiraImportDao
     */
    private $pending_jira_import_dao;

    public function __construct(
        Tracker_Migration_MigrationManager $tv3_migration_manager,
        PendingJiraImportDao $pending_jira_import_dao
    ) {
        $this->tv3_migration_manager   = $tv3_migration_manager;
        $this->pending_jira_import_dao = $pending_jira_import_dao;
    }

    public function informUserOfOngoingMigrations(Project $project, \Response $response): void
    {
        $this->informTv3Migrations($project, $response);
        $this->informJiraMigrations($project, $response);
    }

    private function informTv3Migrations(Project $project, \Response $response): void
    {
        if ($this->tv3_migration_manager->thereAreMigrationsOngoingForProject($project)) {
            $response->addFeedback(
                Feedback::INFO,
                dgettext('tuleap-tracker', 'Some migrations are being processed. Your new trackers will appear as soon as the migrations are completed.')
            );
            $this->informUntruncatedEmailWillBeSent($project, $response);
        }
    }

    private function informUntruncatedEmailWillBeSent(Project $project, \Response $response): void
    {
        if ($project->getTruncatedEmailsUsage()) {
            $response->addFeedback(
                Feedback::INFO,
                dgettext('tuleap-tracker', 'An email not truncated will be sent at the end of the migration process.')
            );
        }
    }

    private function informJiraMigrations(Project $project, \Response $response): void
    {
        $pending_jira_creation = $this->pending_jira_import_dao->searchByProjectId((int) $project->getID());
        if (count($pending_jira_creation) > 0) {
            $tracker_names = [];
            foreach ($pending_jira_creation as $row) {
                $tracker_names[] = $row['tracker_shortname'];
            }

            $response->addFeedback(
                Feedback::INFO,
                sprintf(
                    dngettext(
                        'tuleap-tracker',
                        'A tracker creation from Jira is being processed for %s. Your new tracker will appear as soon as the import is completed.',
                        'Some tracker creations from Jira are being processed for %s. Your new trackers will appear as soon as the import is completed.',
                        count($tracker_names)
                    ),
                    implode(', ', $tracker_names)
                )
            );
        }
    }
}
