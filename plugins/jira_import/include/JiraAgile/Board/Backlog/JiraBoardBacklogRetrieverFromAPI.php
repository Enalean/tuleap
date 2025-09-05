<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\JiraImport\JiraAgile\Board\Backlog;

use Psr\Log\LoggerInterface;
use Tuleap\JiraImport\JiraAgile\JiraBoard;
use Tuleap\JiraImport\JiraAgile\JiraBoardsRetrieverFromAPI;
use Tuleap\Tracker\Creation\JiraImporter\JiraCollectionBuilder;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;

final class JiraBoardBacklogRetrieverFromAPI implements JiraBoardBacklogRetriever
{
    /**
     * @var JiraClient
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(JiraClient $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * @return BacklogIssueRepresentation[]
     */
    #[\Override]
    public function getBoardBacklogIssues(JiraBoard $jira_board): array
    {
        $backlog_issues = [];
        $iterator       = JiraCollectionBuilder::iterateUntilTotal($this->client, $this->logger, $this->getBoardBacklogURL($jira_board), 'issues');
        foreach ($iterator as $issue) {
            $backlog_issues[] = BacklogIssueRepresentation::buildFromAPIResponse($issue);
        }
        return $backlog_issues;
    }

    private function getBoardBacklogURL(JiraBoard $jira_board): string
    {
        return JiraBoardsRetrieverFromAPI::BOARD_URL . '/' . (string) $jira_board->id . '/backlog?' .
            http_build_query(
                [
                    'jql'     => 'issuetype not in subtaskIssueTypes()',
                ]
            );
    }
}
