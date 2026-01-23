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

final readonly class JiraEpicIssuesRetrieverFromAPI implements JiraEpicIssuesRetriever
{
    public function __construct(private JiraClient $jira_client, private LoggerInterface $logger)
    {
    }

    #[\Override]
    public function getIssueIds(JiraEpic $epic, string $jira_project): array
    {
        $issue_ids = [];
        foreach ($this->getIterator($epic, $jira_project) as $value) {
            if (! isset($value['id'])) {
                throw new UnexpectedFormatException($this->getUrlWithoutHost($epic, $jira_project) . ' `issues` key is suppose to be an array with `id` key ');
            }
            $issue_ids[] = $value['id'];
        }
        return $issue_ids;
    }

    private function getIterator(JiraEpic $epic, string $jira_project): \Generator
    {
        if ($this->jira_client->isJiraCloud()) {
            return JiraCollectionBuilder::iterateUntilIsLast(
                $this->jira_client,
                $this->logger,
                ClientWrapper::JIRA_CLOUD_JQL_SEARCH_URL . '?' . $this->getUrlWithoutHost($epic, $jira_project),
                'issues'
            );
        }
        return JiraCollectionBuilder::iterateUntilTotal(
            $this->jira_client,
            $this->logger,
            ClientWrapper::JIRA_CORE_BASE_URL . '/search?' . $this->getUrlWithoutHost($epic, $jira_project),
            'issues'
        );
    }

    private function getUrlWithoutHost(JiraEpic $epic, string $jira_project): string
    {
        return http_build_query([
            'jql'    => 'project="' . $jira_project . '" AND parent=' . $epic->id,
            'fields' => '*all',
            'expand' => 'renderedFields',
        ]);
    }
}
