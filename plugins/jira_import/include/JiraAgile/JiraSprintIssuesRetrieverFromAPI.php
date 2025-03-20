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
use Tuleap\Tracker\Artifact\XML\Exporter\FieldChange\ArtifactLinkChange;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use Tuleap\Tracker\Creation\JiraImporter\JiraCollectionBuilder;
use Tuleap\Tracker\Creation\JiraImporter\UnexpectedFormatException;

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
        $iterator  = JiraCollectionBuilder::iterateUntilTotal(
            $this->client,
            $this->logger,
            $this->getUrlWithoutHost($sprint),
            'issues',
        );
        foreach ($iterator as $issue) {
            if (! isset($issue['id']) || ! is_numeric($issue['id'])) {
                throw new UnexpectedFormatException(sprintf('%s `issues` are supposed to have numerical `id`, `%s` given', $this->getUrlWithoutHost($sprint), $issue['id'] ?? 'null'));
            }
            $issue_ids[] = new ArtifactLinkChange((int) $issue['id']);
        }
        return $issue_ids;
    }

    private function getUrlWithoutHost(JiraSprint $sprint): string
    {
        return parse_url($sprint->url, PHP_URL_PATH) . '/issue?' . http_build_query(['fields' => 'id']);
    }
}
