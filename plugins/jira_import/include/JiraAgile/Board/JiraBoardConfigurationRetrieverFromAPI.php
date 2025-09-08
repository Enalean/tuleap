<?php
/**
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

namespace Tuleap\JiraImport\JiraAgile\Board;

use Psr\Log\LoggerInterface;
use Tuleap\JiraImport\JiraAgile\JiraBoard;
use Tuleap\JiraImport\JiraAgile\JiraBoardsRetrieverFromAPI;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;

class JiraBoardConfigurationRetrieverFromAPI implements JiraBoardConfigurationRetriever
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

    #[\Override]
    public function getScrumBoardConfiguration(JiraBoard $jira_board): ?JiraBoardConfiguration
    {
        $url = $this->getBoardConfigurationURL($jira_board);
        $this->logger->info('GET ' . $url);

        $configuration_json = $this->client->getUrl($url);
        if ($configuration_json === null) {
            return null;
        }

        return JiraBoardConfiguration::buildFromAPIResponse($configuration_json);
    }

    private function getBoardConfigurationURL(JiraBoard $jira_board): string
    {
        return JiraBoardsRetrieverFromAPI::BOARD_URL . '/' . (string) $jira_board->id . '/configuration';
    }
}
