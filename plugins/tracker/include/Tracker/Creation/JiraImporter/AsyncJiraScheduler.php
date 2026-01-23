<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

use Project;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\DB\DBTransactionExecutor;

readonly class AsyncJiraScheduler
{
    public function __construct(
        private PendingJiraImportDao $dao,
        private JiraRunner $jira_runner,
        private DBTransactionExecutor $db_transaction_executor,
    ) {
    }

    /**
     * @param non-empty-string $tracker_shortname
     */
    public function scheduleCreation(
        Project $project,
        \PFUser $user,
        string $jira_server,
        string $jira_user_email,
        ConcealedString $jira_token,
        string $jira_project_id,
        string $jira_issue_type_name,
        string $jira_issue_type_id,
        string $tracker_name,
        string $tracker_shortname,
        string $tracker_color,
        string $tracker_description,
    ): void {
        $this->db_transaction_executor->execute(
            function () use ($project, $user, $jira_server, $jira_user_email, $jira_token, $jira_project_id, $jira_issue_type_name, $jira_issue_type_id, $tracker_name, $tracker_shortname, $tracker_color, $tracker_description): void {
                $id = $this->dao->create(
                    (int) $project->getID(),
                    (int) $user->getId(),
                    $jira_server,
                    $jira_user_email,
                    $jira_token,
                    $jira_project_id,
                    $jira_issue_type_name,
                    $jira_issue_type_id,
                    $tracker_name,
                    $tracker_shortname,
                    $tracker_color,
                    $tracker_description
                );
                $this->jira_runner->queueJiraImportEvent($id);
            }
        );
    }
}
