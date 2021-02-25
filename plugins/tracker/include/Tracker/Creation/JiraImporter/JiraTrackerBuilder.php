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

class JiraTrackerBuilder
{
    /**
     * @throws JiraConnectionException
     * @return IssueType[]
     */
    public function buildFromProjectKey(ClientWrapper $wrapper, string $project_key): array
    {
        $project_details = $wrapper->getUrl(ClientWrapper::JIRA_CORE_BASE_URL . '/project/' . urlencode($project_key));

        $tracker_list = [];
        if (! $project_details || ! $project_details['issueTypes']) {
            return $tracker_list;
        }

        foreach ($project_details['issueTypes'] as $json_issue_type) {
            $tracker_list[] = IssueType::buildFromAPIResponse($json_issue_type);
        }

        return $tracker_list;
    }

    /**
     * @throws JiraConnectionException
     * @throws \JsonException
     */
    public function buildFromIssueTypeId(JiraClient $wrapper, string $issue_type_id): ?IssueType
    {
        $json = $wrapper->getUrl(ClientWrapper::JIRA_CORE_BASE_URL . '/issuetype/' . urlencode($issue_type_id));
        if (! $json) {
            return null;
        }
        return IssueType::buildFromAPIResponse(
            $json
        );
    }
}
