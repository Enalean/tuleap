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
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use Tuleap\Tracker\Creation\JiraImporter\UnexpectedFormatException;

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
    public function getBoardBacklogIssues(JiraBoard $jira_board): array
    {
        $backlog_issues = [];

        $start_at = 0;
        do {
            $url = $this->getBoardBacklogURL($jira_board, $start_at);
            $this->logger->info('GET ' . $url);
            $json = $this->client->getUrl($url);
            if (! isset($json['issues'], $json['total'])) {
                throw new UnexpectedFormatException($url . ' is supposed to return a payload with `total` and `issues`');
            }
            foreach ($json['issues'] as $issue) {
                $backlog_issues[] = BacklogIssueRepresentation::buildFromAPIResponse($issue);
                $start_at++;
            }
        } while ($start_at < $json['total']);

        return $backlog_issues;
    }

    private function getBoardBacklogURL(JiraBoard $jira_board, int $start_at): string
    {
        return JiraBoardsRetrieverFromAPI::BOARD_URL . '/' . (string) $jira_board->id . '/backlog?' .
            http_build_query(
                [
                    "startAt" => $start_at,
                    "jql"     => "issuetype not in subtaskIssueTypes()"
                ]
            );
    }
}
