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

final class JiraSprintRetrieverFromAPI implements JiraSprintRetriever
{
    private const PARAM_START_AT = 'startAt';

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

    /**
     * @return JiraSprint[]
     * @throws \JsonException
     * @throws \Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException
     */
    public function getAllSprints(JiraBoard $board): array
    {
        $sprints  = [];
        $start_at = 0;
        do {
            $sprint_url = $this->getSprintUrl($board, $start_at);
            $this->logger->info('Get Sprints at ' . $sprint_url);
            $json = $this->client->getUrl($sprint_url);
            if (! isset($json['isLast'], $json['values'])) {
                throw new \RuntimeException(sprintf('%s route did not return the expected format: `isLast` or `values` key are missing', $this->getSprintUrl($board, $start_at)));
            }
            foreach ($json['values'] as $json_sprint) {
                $sprints[] = JiraSprint::buildFromAPI($json_sprint);
                $start_at++;
            }
        } while ($json['isLast'] !== true);
        return $sprints;
    }

    private function getSprintUrl(JiraBoard $board, int $start_at): string
    {
        return parse_url($board->url, PHP_URL_PATH) . '/sprint?' . http_build_query([self::PARAM_START_AT => $start_at]);
    }
}
