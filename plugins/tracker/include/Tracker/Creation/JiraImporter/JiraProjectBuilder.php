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

class JiraProjectBuilder
{
    /**
     * @throws JiraConnectionException
     */
    public function build(ClientWrapper $wrapper): array
    {
        $jira_projects = $wrapper->getUrl('/project/search');

        $project_collection = new JiraProjectCollection();

        if (! $jira_projects) {
            return $project_collection->getJiraProjects();
        }

        $this->buildProjectList($jira_projects, $project_collection);

        $count   = 1;
        $is_last = $jira_projects['isLast'];
        while (! $is_last) {
            $max_results = $jira_projects['maxResults'];
            $offset      = $jira_projects['maxResults'] * $count;

            $jira_projects = $wrapper->getUrl(
                "/project/search?&startAt=" . urlencode((string) $offset) . "&maxResults=" .
                urlencode((string) $max_results)
            );

            if (! $jira_projects) {
                throw JiraConnectionException::canNotRetrieveFullCollectionException();
            }

            $this->buildProjectList($jira_projects, $project_collection);

            $is_last = $jira_projects['isLast'];
            $count++;
        }

        return $project_collection->getJiraProjects();
    }

    private function buildProjectList(?array $jira_projects, JiraProjectCollection $collection): void
    {
        if (! $jira_projects || ! $jira_projects['values']) {
            return;
        }

        foreach ($jira_projects['values'] as $project) {
            if (! $project['key'] || ! $project['name']) {
                throw new \LogicException('Key or name has not been founded in jira_representation');
            }
            $collection->addProject(
                [
                    'id'    => $project['key'],
                    'label' => $project['name'],
                ]
            );
        }
    }
}
