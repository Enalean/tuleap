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
            $this->getBoardUrl($jira_project_key),
            'values',
        );
        foreach ($iterator as $json_board) {
            $this->assertBoardValuesResponseStructure($json_board);
            return new JiraBoard($json_board['id'], $json_board['self']);
        }
        return null;
    }

    public function getBoardUrl(string $jira_project_key): string
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
}
