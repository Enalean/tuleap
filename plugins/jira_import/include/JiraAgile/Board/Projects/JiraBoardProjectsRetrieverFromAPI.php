<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\JiraImport\JiraAgile\Board\Projects;

use Psr\Log\LoggerInterface;
use Tuleap\JiraImport\JiraAgile\JiraBoardsRetrieverFromAPI;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use Tuleap\Tracker\Creation\JiraImporter\JiraCollectionBuilder;
use Tuleap\Tracker\Creation\JiraImporter\JiraProjectCollection;

final class JiraBoardProjectsRetrieverFromAPI implements JiraBoardProjectsRetriever
{
    public function __construct(
        private readonly JiraClient $client,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[\Override]
    public function getBoardProjects(int $jira_board_id): JiraProjectCollection
    {
        $collection = new JiraProjectCollection();

        $iterator = JiraCollectionBuilder::iterateUntilIsLast(
            $this->client,
            $this->logger,
            $this->getBoardProjectsByIdUrl($jira_board_id),
            'values',
        );
        foreach ($iterator as $json_board_project) {
            if (! isset($json_board_project['name'], $json_board_project['key'])) {
                throw new \RuntimeException(
                    sprintf(
                        '%s route did not return the expected format for `values`: `name` or `key` are missing',
                        $this->getBoardProjectsByIdUrl($jira_board_id),
                    )
                );
            }

            $collection->addProject(
                [
                    'id'    => (string) $json_board_project['key'],
                    'label' => (string) $json_board_project['name'],
                ]
            );
        }

        return $collection;
    }

    private function getBoardProjectsByIdUrl(int $jira_board_id): string
    {
        return JiraBoardsRetrieverFromAPI::BOARD_URL . '/' . urlencode((string) $jira_board_id) . '/project';
    }
}
