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

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;

class PlatformConfigurationRetriever
{
    /**
     * @var EventDispatcherInterface
     */
    private $event_dispatcher;

    public function __construct(EventDispatcherInterface $event_dispatcher)
    {
        $this->event_dispatcher = $event_dispatcher;
    }

    public function getJiraPlatformConfiguration(JiraClient $wrapper, LoggerInterface $logger): PlatformConfiguration
    {
        $logger->debug('Get Jira platform configurations.');
        $platform_configuration = new PlatformConfiguration();
        $this->event_dispatcher->dispatch(
            new PlatformConfigurationForExternalPluginsEvent($wrapper, $platform_configuration, $logger)
        );

        return $platform_configuration;
    }
}
