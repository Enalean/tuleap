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
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\IssueType;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use Tuleap\Tracker\Creation\JiraImporter\JiraCollectionBuilder;

final class JiraEpicFromIssueTypeRetrieverFromAPI implements JiraEpicFromIssueTypeRetriever
{
    public function __construct(private JiraClient $client, private LoggerInterface $logger)
    {
    }

    /**
     * @return JiraEpic[]
     */
    #[\Override]
    public function getEpics(IssueType $issue_type, string $jira_project): array
    {
        $iterator = JiraCollectionBuilder::iterateUntilTotal(
            $this->client,
            $this->logger,
            $this->getUrlWithoutHost($issue_type, $jira_project),
            'issues',
        );
        $epics    = [];
        foreach ($iterator as $value) {
            $epics[] = JiraEpic::buildFromIssueAPI($value);
        }
        return $epics;
    }

    private function getUrlWithoutHost(IssueType $issue_type, string $jira_project): string
    {
        $params = [
            'jql'    => 'project="' . $jira_project . '" AND issuetype=' . $issue_type->getId(),
            'fields' => '*all',
            'expand' => 'renderedFields',
        ];

        return parse_url(ClientWrapper::JIRA_CORE_BASE_URL, PHP_URL_PATH) . '/search?' . http_build_query($params);
    }
}
