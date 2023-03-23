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

class ComponentsRetrieverFromAPI implements ComponentsRetriever
{
    public function __construct(
        private readonly JiraClient $client,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return JiraComponent[]
     */
    public function getProjectComponents(string $jira_project_key): array
    {
        $project_components = [];
        $iterator           = JiraCollectionBuilder::iterateUntilIsLast(
            $this->client,
            $this->logger,
            $this->getPaginatedProjectComponentsURL($jira_project_key),
            'values',
        );
        foreach ($iterator as $json_components) {
            $project_components[] = JiraComponent::buildFromAPIResponse($json_components);
        }
        return $project_components;
    }

    private function getPaginatedProjectComponentsURL(string $jira_project_key): string
    {
        return ClientWrapper::JIRA_CORE_BASE_URL . '/project/' . urlencode($jira_project_key) . '/component';
    }
}