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

namespace Tuleap\JiraImport\Project\Components;

use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use Tuleap\Tracker\Creation\JiraImporter\JiraCollectionBuilder;

class ComponentIssuesRetrieverFromAPI implements ComponentIssuesRetriever
{
    public function __construct(
        private readonly JiraClient $client,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return JiraComponentLinkedIssue[]
     */
    public function getComponentIssues(JiraComponent $component, string $jira_project_key): array
    {
        $component_issues = [];
        $iterator         = JiraCollectionBuilder::iterateUntilTotal(
            $this->client,
            $this->logger,
            $this->getJQLComponentIssuesURL($component->name, $jira_project_key),
            'issues',
        );
        foreach ($iterator as $json_component_issue) {
            $component_issues[] = JiraComponentLinkedIssue::buildFromAPIResponse($json_component_issue);
        }
        return $component_issues;
    }

    private function getJQLComponentIssuesURL(string $component_name, string $jira_project_key): string
    {
        $params = [
            'jql'     => 'project="' . $jira_project_key . '" AND component="' . $component_name . '"',
            'startAt' => 0,
        ];

        return ClientWrapper::JIRA_CORE_BASE_URL . '/search?' . http_build_query($params);
    }
}
