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

namespace Tuleap\Tracker\Creation\JiraImporter;

use ProjectManager;
use UserManager;

class PendingJiraImportBuilder
{
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(
        ProjectManager $project_manager,
        UserManager $user_manager
    ) {
        $this->project_manager = $project_manager;
        $this->user_manager    = $user_manager;
    }

    /**
     * @param array{id: int, project_id: int, user_id: int, created_on: int, jira_server: string, jira_user_email: string, encrypted_jira_token: string, jira_project_id: string, jira_issue_type_name: string, tracker_name: string, tracker_shortname: string, tracker_color: string, tracker_description: string} $row
     *
     * @throws UnableToBuildPendingJiraImportException
     */
    public function buildFromRow(array $row): PendingJiraImport
    {
        $project = $this->project_manager->getProject($row['project_id']);
        if ($project->isError() || ! $project->isActive()) {
            throw new UnableToBuildPendingJiraImportException('Project does not exist or is not anymore active.');
        }

        $user = $this->user_manager->getUserById($row['user_id']);
        if (! $user || ! $user->isAlive()) {
            throw new UnableToBuildPendingJiraImportException('User does not exist or is not anymore alive.');
        }

        return new PendingJiraImport(
            $row['id'],
            $project,
            $user,
            (new \DateTimeImmutable())->setTimestamp($row['created_on']),
            $row['jira_server'],
            $row['jira_user_email'],
            $row['encrypted_jira_token'],
            $row['jira_project_id'],
            $row['jira_issue_type_name'],
            $row['tracker_name'],
            $row['tracker_shortname'],
            $row['tracker_color'],
            $row['tracker_description']
        );
    }
}
