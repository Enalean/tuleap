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

class JiraBoardsRetrieverFromAPI implements JiraBoardsRetriever
{
    public const BOARD_URL      = '/rest/agile/1.0/board';
    private const TYPE_PARAM    = 'type';
    private const SCRUM_TYPE    = 'scrum';
    private const TYPE_START_AT = 'startAt';
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
     * @throws \JsonException
     * @throws \Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException
     */
    public function getFirstScrumBoardForProject(string $jira_project_key): ?JiraBoard
    {
        $start_at = 0;
        do {
            $url = $this->getBoardUrl($start_at);
            $this->logger->info('GET ' . $url);
            $json = $this->client->getUrl($url);
            $this->assertBoardResponseStructure($json);
            if ($json['total'] === 0) {
                return null;
            }
            foreach ($json['values'] as $json_board) {
                $this->assertBoardValuesResponseStructure($json_board);
                if ($json_board['location']['projectKey'] === $jira_project_key) {
                    return new JiraBoard($json_board['id'], $json_board['self'], $json_board['location']['projectId'], $json_board['location']['projectKey']);
                }
                $start_at++;
            }
        } while ($json['isLast'] !== true);

        return null;
    }

    public function getBoardUrl(int $start_at): string
    {
        return self::BOARD_URL . '?' . http_build_query([self::TYPE_PARAM => self::SCRUM_TYPE, self::TYPE_START_AT => $start_at]);
    }

    /**
     * @param string|array|null $json
     * @psalm-assert array{total: int, isLast: bool, values: array} $json
     */
    private function assertBoardResponseStructure($json): void
    {
        if (! isset($json['total'], $json['values'], $json['isLast'])) {
            throw new \RuntimeException(sprintf('%s route did not return the expected format: `total` or `values` key are missing', self::BOARD_URL));
        }
    }

    /**
     * @psalm-assert array{id: int, self: string, location: array{projectId: int, projectKey: string}} $json
     */
    private function assertBoardValuesResponseStructure(array $json): void
    {
        if (! isset($json['id'], $json['self'], $json['location']['projectId'], $json['location']['projectKey'])) {
            throw new \RuntimeException(sprintf('%s route did not return the expected format for `values`: `id`, `location.projectId` or `location.projectKey` are missing', self::BOARD_URL));
        }
    }
}
