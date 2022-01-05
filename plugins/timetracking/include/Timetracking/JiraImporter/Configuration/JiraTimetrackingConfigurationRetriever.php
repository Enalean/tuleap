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
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;

class JiraTimetrackingConfigurationRetriever
{
    public const CONFIGURATION_KEY = 'jira_timetracking';
    private const TIMETRACKING_KEY = 'timeTrackingEnabled';

    public function __construct(private JiraClient $jira_client, private LoggerInterface $logger)
    {
    }

    public function getJiraTimetrackingConfiguration(): ?string
    {
        $this->logger->debug("Get Jira timetracking platform configuration.");

        $timetracking_configuration_url = ClientWrapper::JIRA_CORE_BASE_URL . '/configuration';
        $this->logger->debug("  GET " . $timetracking_configuration_url);

        try {
            $configuration_data = $this->jira_client->getUrl($timetracking_configuration_url);
        } catch (JiraConnectionException $exception) {
            if ($exception->getCode() === 404) {
                return null;
            }
            throw $exception;
        }

        if (isset($configuration_data[self::TIMETRACKING_KEY]) && $configuration_data[self::TIMETRACKING_KEY] === true) {
            return self::CONFIGURATION_KEY;
        }

        return null;
    }
}
