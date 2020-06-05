<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog;

use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;

class ChangelogEntriesBuilder
{
    /**
     * @var ClientWrapper
     */
    private $wrapper;

    public function __construct(
        ClientWrapper $wrapper
    ) {
        $this->wrapper = $wrapper;
    }

    public function buildEntriesCollectionForIssue(string $jira_issue_key): array
    {
        $changelog_entries = [];

        $changelog_response = $this->wrapper->getUrl(
            $this->getChangelogUrl($jira_issue_key, null, null)
        );

        if ($changelog_response === null) {
            throw JiraConnectionException::canNotRetrieveFullCollectionOfIssueChangelogsException();
        }

        if (
            ! isset($changelog_response['values']) ||
            ! isset($changelog_response['maxResults']) ||
            ! isset($changelog_response['total'])
        ) {
            throw JiraConnectionException::canNotRetrieveFullCollectionOfIssueChangelogsException();
        }

        foreach ($changelog_response['values'] as $changelog) {
            $changelog_entries[] = ChangelogEntryValueRepresentation::buildFromAPIResponse($changelog);
        }

        $count_loop = 1;
        $total      = (int) $changelog_response['total'];
        $is_last    = $total <= (int) $changelog_response['maxResults'];
        while (! $is_last) {
            $max_results = $changelog_response['maxResults'];
            $start_at    = $changelog_response['maxResults'] * $count_loop;

            $changelog_response = $this->wrapper->getUrl(
                $this->getChangelogUrl($jira_issue_key, $start_at, $max_results)
            );

            if ($changelog_response === null) {
                throw JiraConnectionException::canNotRetrieveFullCollectionOfIssueChangelogsException();
            }

            if (
                ! isset($changelog_response['values']) ||
                ! isset($changelog_response['maxResults']) ||
                ! isset($changelog_response['total'])
            ) {
                throw JiraConnectionException::canNotRetrieveFullCollectionOfIssueChangelogsException();
            }

            foreach ($changelog_response['values'] as $changelog) {
                $changelog_entries[] = $changelog;
            }
        }

        return $changelog_entries;
    }

    private function getChangelogUrl(
        string $jira_issue_key,
        ?int $start_at,
        ?int $max_results
    ): string {
        $params = [];

        if ($start_at !== null) {
            $params['startAt'] = $start_at;
        }

        if ($max_results !== null) {
            $params['maxResults'] = $max_results;
        }

        return "/issue/" . urlencode($jira_issue_key) . "/changelog" . http_build_query($params);
    }
}
