<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\JiraImporter\Configuration;

use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;

class JiraTimetrackingConfigurationRetriever
{
    public const CONFIGURATION_KEY = 'jira_timetracking';
    public const EXPECTED_VALUE    = 'JIRA';

    /**
     * @var JiraClient
     */
    private $jira_client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(JiraClient $jira_client, LoggerInterface $logger)
    {
        $this->jira_client = $jira_client;
        $this->logger      = $logger;
    }

    public function getJiraTimetrackingConfiguration(): ?string
    {
        $this->logger->debug("Get Jira timetracking platform configuration.");

        $timetracking_configuration_url = ClientWrapper::JIRA_CORE_BASE_URL . '/configuration/timetracking';
        $this->logger->debug("  GET " . $timetracking_configuration_url);

        $configration_data = $this->jira_client->getUrl($timetracking_configuration_url);
        if ($configration_data === null) {
            return null;
        }

        assert(is_array($configration_data));
        if (! array_key_exists('key', $configration_data)) {
            return null;
        }

        if ($configration_data['key'] === self::EXPECTED_VALUE) {
            return self::CONFIGURATION_KEY;
        }

        return null;
    }
}
