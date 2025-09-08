<?php
/*
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
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use Tuleap\Tracker\Creation\JiraImporter\JiraCollectionBuilder;
use Tuleap\Tracker\Creation\JiraImporter\UnexpectedFormatException;

final class JiraEpicIssuesRetrieverFromAPI implements JiraEpicIssuesRetriever
{
    /**
     * @var JiraClient
     */
    private $jira_client;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(JiraClient $jira_client, LoggerInterface $logger)
    {
        $this->jira_client = $jira_client;
        $this->logger      = $logger;
    }

    #[\Override]
    public function getIssueIds(JiraEpic $epic, string $jira_project): array
    {
        $iterator  = JiraCollectionBuilder::iterateUntilTotal(
            $this->jira_client,
            $this->logger,
            $this->getUrlWithoutHost($epic, $jira_project),
            'issues'
        );
        $issue_ids = [];
        foreach ($iterator as $value) {
            if (! isset($value['id'])) {
                throw new UnexpectedFormatException($this->getUrlWithoutHost($epic, $jira_project) . ' `issues` key is suppose to be an array with `id` key ');
            }
            $issue_ids[] = $value['id'];
        }
        return $issue_ids;
    }

    private function getUrlWithoutHost(JiraEpic $epic, string $jira_project): string
    {
        $params = [
            'jql'    => 'project="' . $jira_project . '" AND parent=' . $epic->id,
            'fields' => '*all',
            'expand' => 'renderedFields',
        ];

        return parse_url(ClientWrapper::JIRA_CORE_BASE_URL, PHP_URL_PATH) . '/search?' . http_build_query($params);
    }
}
