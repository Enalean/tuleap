<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Tracker\Creation\JiraImporter;

use Psr\Log\LoggerInterface;

class JiraProjectBuilder
{
    /**
     * @return array<array{id: string, label: string}>
     * @throws UnexpectedFormatException
     * @throws JiraConnectionException
     * @throws \JsonException
     */
    public function build(JiraClient $jira_client, LoggerInterface $logger): array
    {
        if ($jira_client->isJiraCloud()) {
            return $this->getCollectionFromJiraCloud($jira_client, $logger);
        }
        return $this->getCollectionFromJiraServer($jira_client);
    }

    /**
     * @return array<array{id: string, label: string}>
     * @throws UnexpectedFormatException
     */
    private function getCollectionFromJiraCloud(JiraClient $jira_client, LoggerInterface $logger): array
    {
        $iterator           = JiraCollectionBuilder::iterateUntilIsLast(
            $jira_client,
            $logger,
            ClientWrapper::JIRA_CORE_BASE_URL . '/project/search',
            'values',
        );
        $project_collection = new JiraProjectCollection();
        foreach ($iterator as $project) {
            if (! isset($project['key'], $project['name'])) {
                throw new UnexpectedFormatException('`key` or `name` has not been founded in jira_representation');
            }
            $project_collection->addProject(
                [
                    'id'    => (string) $project['key'],
                    'label' => (string) $project['name'],
                ]
            );
        }
        return $project_collection->getJiraProjects();
    }

    /**
     * @throws UnexpectedFormatException
     * @throws JiraConnectionException
     * @throws \JsonException
     */
    private function getCollectionFromJiraServer(JiraClient $jira_client): array
    {
        $json = $jira_client->getUrl(ClientWrapper::JIRA_CORE_BASE_URL . '/project');
        if ($json === null) {
            throw new UnexpectedFormatException(ClientWrapper::JIRA_CORE_BASE_URL . '/project is supposed to return a collection of projects, null received');
        }
        $project_collection = new JiraProjectCollection();
        foreach ($json as $project_entry) {
            if (! isset($project_entry['key'], $project_entry['name'])) {
                throw new UnexpectedFormatException('`key` or `name` has not been founded in jira_representation');
            }
            $project_collection->addProject(
                [
                    'id' => (string) $project_entry['key'],
                    'label' => (string) $project_entry['name'],
                ]
            );
        }
        return $project_collection->getJiraProjects();
    }
}
