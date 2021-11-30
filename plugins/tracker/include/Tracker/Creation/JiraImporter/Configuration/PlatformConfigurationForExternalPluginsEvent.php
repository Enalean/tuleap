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

namespace Tuleap\Tracker\Creation\JiraImporter\Configuration;

use Psr\Log\LoggerInterface;
use Tuleap\Event\Dispatchable;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;

class PlatformConfigurationForExternalPluginsEvent implements Dispatchable
{
    public const NAME = 'platformConfigurationForExternalPluginsEvent';

    /**
     * @var JiraClient
     * @psalm-readonly
     */
    private $jira_client;

    /**
     * @var PlatformConfiguration
     */
    private $platform_configuration;

    /**
     * @var LoggerInterface
     * @psalm-immutable
     */
    private $logger;

    public function __construct(
        JiraClient $jira_client,
        PlatformConfiguration $platform_configuration,
        LoggerInterface $logger,
    ) {
        $this->jira_client            = $jira_client;
        $this->platform_configuration = $platform_configuration;
        $this->logger                 = $logger;
    }

    public function getJiraClient(): JiraClient
    {
        return $this->jira_client;
    }

    public function addConfigurationInCollection(string $configuration_name): void
    {
        $this->platform_configuration->addAllowedConfiguration($configuration_name);
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
