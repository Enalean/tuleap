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
use Tuleap\JiraImport\JiraAgile\Board\Projects\JiraBoardProjectsRetriever;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use Tuleap\Tracker\Creation\JiraImporter\JiraCollectionBuilder;

class JiraBoardsRetrieverFromAPI implements JiraBoardsRetriever
{
    public const string BOARD_URL   = '/rest/agile/1.0/board';
    private const string TYPE_PARAM = 'type';
    private const string SCRUM_TYPE = 'scrum';

    public function __construct(
        private readonly JiraClient $client,
        private readonly LoggerInterface $logger,
        private readonly JiraBoardProjectsRetriever $jira_board_projects_retriever,
    ) {
    }

    /**
     * @throws \JsonException
     * @throws \Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException
     */
    #[\Override]
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

    #[\Override]
    public function getScrumBoardByIdForProject(string $jira_project_key, int $jira_board_id): ?JiraBoard
    {
        $url = $this->getBoardByIdUrl($jira_board_id);
        $this->logger->info('GET ' . $url);

        $json_board = $this->client->getUrl($url);
        if ($json_board === null) {
            $this->logger->warning('Jira board #' . $jira_board_id . ' not found.');
            return null;
        }

        $this->assertBoardValuesResponseStructure($json_board);
        $this->assertBoardIsInProject($json_board, $jira_project_key, $jira_board_id);
        return new JiraBoard($json_board['id'], $json_board['self']);
    }

    /**
     * @psalm-assert array{id: int, self: string, type: string, location: array{projectKey: string}} $json
     */
    private function assertBoardIsInProject(array $json, string $jira_project_key, int $jira_board_id): void
    {
        if (! isset($json['type'])) {
            throw new \RuntimeException(sprintf('%s route did not return the expected format: mandatory key `type` is missing', self::BOARD_URL));
        }

        if (isset($json['location'])) {
            $this->logger->debug('Board project information are in `location` key.');
            $this->assertBoardIsInProjectWithLocationKey($json['location'], $jira_project_key);
        } else {
            $this->logger->debug('No `location` key in board information. Need to do a specific query.');
            $this->assertBoardIsInProjectWithBoardProjectsQuery($jira_board_id, $jira_project_key);
        }

        if ($json['type'] !== self::SCRUM_TYPE) {
            throw new \RuntimeException('The provided board is no a scrum board');
        }
    }

    private function assertBoardIsInProjectWithLocationKey(array $json_location, string $jira_project_key): void
    {
        if (! isset($json_location['projectKey'])) {
            throw new \RuntimeException(sprintf('%s route did not return the expected format for `location`: `projectKey` is missing', self::BOARD_URL));
        }

        if ($jira_project_key !== $json_location['projectKey']) {
            throw new \RuntimeException('The provided board is no located into selected project.');
        }
    }

    private function assertBoardIsInProjectWithBoardProjectsQuery(
        int $jira_board_id,
        string $jira_project_key,
    ): void {
        foreach ($this->jira_board_projects_retriever->getBoardProjects($jira_board_id)->getJiraProjects() as $jira_board_project) {
            if (isset($jira_board_project['id']) && $jira_board_project['id'] === $jira_project_key) {
                return;
            }
        }

        throw new \RuntimeException('The provided board is no located into selected project.');
    }

    private function getBoardByIdUrl(int $jira_board_id): string
    {
        return self::BOARD_URL . '/' . urlencode((string) $jira_board_id);
    }
}
