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
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use Tuleap\Tracker\Creation\JiraImporter\UnexpectedFormatException;
use Tuleap\Tracker\XML\Exporter\FieldChange\ArtifactLinkChange;

final class JiraSprintIssuesRetrieverFromAPI implements JiraSprintIssuesRetriever
{
    /**
     * @var JiraClient
     */
    private $client;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(JiraClient $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function getArtifactLinkChange(JiraSprint $sprint): array
    {
        $issue_ids = [];
        do {
            $issues_url = $this->getUrlWithoutHost($sprint, count($issue_ids));
            $this->logger->info('Fetch sprint issues: ' . $issues_url);
            $json = $this->client->getUrl($issues_url);
            if (! isset($json['issues'], $json['total'])) {
                throw new UnexpectedFormatException($issues_url . ' is supposed to return a payload with `total` and `issues`');
            }
            foreach ($json['issues'] as $issue) {
                if (! isset($issue['id']) || ! is_numeric($issue['id'])) {
                    throw new UnexpectedFormatException(sprintf('%s `issues` are supposed to have numerical `id`, `%s` given', $issues_url, $issue['id']));
                }
                $issue_ids[] = new ArtifactLinkChange((int) $issue['id']);
            }
        } while ($json['total'] !== count($issue_ids));
        return $issue_ids;
    }

    private function getUrlWithoutHost(JiraSprint $sprint, int $start_at): string
    {
        return parse_url($sprint->url, PHP_URL_PATH) . '/issue?' . http_build_query(['fields' => 'id', 'startAt' => $start_at]);
    }
}
