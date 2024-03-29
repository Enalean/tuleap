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
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;

final class JiraServerChangelogEntriesBuilder implements ChangelogEntriesBuilder
{
    public function __construct(
        private JiraClient $jira_client,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return ChangelogEntryValueRepresentation[]
     * @throws JiraConnectionException
     */
    public function buildEntriesCollectionForIssue(string $jira_issue_key): array
    {
        $this->logger->debug('  Start build changelog entries collection ...');

        $changelog_entries = [];

        $url   = ClientWrapper::JIRA_CORE_BASE_URL . '/issue/' . urlencode($jira_issue_key) . '?expand=changelog';
        $issue = $this->jira_client->getUrl($url);
        if (! $issue || ! isset($issue['changelog']['histories'])) {
            throw new ChangelogAPIResponseNotWellFormedException('No data or no `changelog` key for ' . $url);
        }

        foreach ($issue['changelog']['histories'] as $history) {
            $changelog_entries[] = JiraServerChangelogEntryValueRepresentation::buildFromAPIResponse($history);
        }

        $this->logger->debug('  Changelog entries built with success');

        return $changelog_entries;
    }
}
