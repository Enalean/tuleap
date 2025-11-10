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
use Tuleap\Tracker\Creation\JiraImporter\PendingJiraImportDao;

class OngoingCreationFeedbackNotifier
{
    public function __construct(
        private PendingJiraImportDao $pending_jira_import_dao,
    ) {
    }

    public function informUserOfOngoingMigrations(Project $project, \Response $response): void
    {
        $this->informJiraMigrations($project, $response);
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
