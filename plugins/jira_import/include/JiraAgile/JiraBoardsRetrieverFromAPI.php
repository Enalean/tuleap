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

namespace Tuleap\JiraImport\JiraAgile;

use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use Tuleap\Tracker\Creation\JiraImporter\JiraCollectionBuilder;

class JiraBoardsRetrieverFromAPI implements JiraBoardsRetriever
{
    public const BOARD_URL   = '/rest/agile/1.0/board';
    private const TYPE_PARAM = 'type';
    private const SCRUM_TYPE = 'scrum';

    public function __construct(private JiraClient $client, private LoggerInterface $logger)
    {
    }

    /**
     * @throws \JsonException
     * @throws \Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException
     */
    public function getFirstScrumBoardForProject(string $jira_project_key): ?JiraBoard
    {
        $iterator = JiraCollectionBuilder::iterateUntilIsLast(
            $this->client,
            $this->logger,
            $this->getFirstBoardUrl($jira_project_key),
            'values',
        );
        foreach ($iterator as $json_board) {
            $this->assertBoardValuesResponseStructure($json_board);
            return new JiraBoard($json_board['id'], $json_board['self']);
        }
        return null;
    }

    private function getFirstBoardUrl(string $jira_project_key): string
    {
        return self::BOARD_URL . '?' . http_build_query([self::TYPE_PARAM => self::SCRUM_TYPE, 'projectKeyOrId' => $jira_project_key, 'maxResults' => 1]);
    }

    /**
     * @psalm-assert array{id: int, self: string} $json
     */
    private function assertBoardValuesResponseStructure(array $json): void
    {
        if (! isset($json['id'], $json['self'])) {
            throw new \RuntimeException(sprintf('%s route did not return the expected format for `values`: `id` or `self` are missing', self::BOARD_URL));
        }
    }

    public function getScrumBoardByIdForProject(string $jira_project_key, int $jira_board_id): ?JiraBoard
    {
        $url = $this->getBoardByIdUrl($jira_board_id);
        $this->logger->info('GET ' . $url);

        $json_board = $this->client->getUrl($url);
        if ($json_board === null) {
            $this->logger->warning('Jira board #' . $jira_board_id . " not found.");
            return null;
        }

        $this->assertBoardValuesResponseStructure($json_board);
        $this->assertBoardIsInProject($json_board, $jira_project_key);
        return new JiraBoard($json_board['id'], $json_board['self']);
    }

    /**
     * @psalm-assert array{id: int, self: string, type: string, location: array{projectKey: string}} $json
     */
    private function assertBoardIsInProject(array $json, string $jira_project_key): void
    {
        if (! isset($json['location'], $json['type'])) {
            throw new \RuntimeException(sprintf('%s route did not return the expected format: `location` or `type` are missing', self::BOARD_URL));
        }

        if (! isset($json['location']['projectKey'])) {
            throw new \RuntimeException(sprintf('%s route did not return the expected format for `location`: `projectKey` is missing', self::BOARD_URL));
        }

        if ($jira_project_key !== $json['location']['projectKey']) {
            throw new \RuntimeException('The provided board is no located into selected project.');
        }

        if ($json['type'] !== self::SCRUM_TYPE) {
            throw new \RuntimeException('The provided board is no a scrum board');
        }
    }

    private function getBoardByIdUrl(int $jira_board_id): string
    {
        return self::BOARD_URL . '/' . urlencode((string) $jira_board_id);
    }
}
