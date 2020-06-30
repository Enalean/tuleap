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

use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;

class ChangelogEntriesBuilder
{
    /**
     * @var ClientWrapper
     */
    private $wrapper;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ClientWrapper $wrapper,
        LoggerInterface $logger
    ) {
        $this->wrapper = $wrapper;
        $this->logger  = $logger;
    }

    /**
     * @return ChangelogEntryValueRepresentation[]
     * @throws JiraConnectionException
     */
    public function buildEntriesCollectionForIssue(string $jira_issue_key): array
    {
        $this->logger->debug("  Start build changelog entries collection ...");

        $changelog_entries = [];

        $changelog_response = $this->wrapper->getUrl(
            $this->getChangelogUrl($jira_issue_key, null, null)
        );
        $changelog_representation = ChangelogResponseRepresentation::buildFromAPIResponse($changelog_response);

        foreach ($changelog_representation->getValues() as $changelog) {
            $changelog_entries[] = ChangelogEntryValueRepresentation::buildFromAPIResponse($changelog);
        }

        $count_loop = 1;
        $total      = $changelog_representation->getTotal();
        $is_last    = $total <= $changelog_representation->getMaxResults();
        while (! $is_last) {
            $max_results = $changelog_representation->getMaxResults();
            $start_at    = $max_results * $count_loop;

            $changelog_response = $this->wrapper->getUrl(
                $this->getChangelogUrl($jira_issue_key, $start_at, $max_results)
            );
            $changelog_representation = ChangelogResponseRepresentation::buildFromAPIResponse($changelog_response);

            foreach ($changelog_representation->getValues() as $changelog) {
                $changelog_entries[] = $changelog;
            }

            $is_last = $changelog_representation->getTotal() <=
                ($changelog_representation->getStartAt() + $changelog_representation->getMaxResults());
            $count_loop++;
        }

        $this->logger->debug("  Changelog entries built with success");

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

        $this->logger->debug(
            "  GET /issue/" . urlencode($jira_issue_key) . "/changelog" . http_build_query($params)
        );

        return "/issue/" . urlencode($jira_issue_key) . "/changelog" . http_build_query($params);
    }
}
