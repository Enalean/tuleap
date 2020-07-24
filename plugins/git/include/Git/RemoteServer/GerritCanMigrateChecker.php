<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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
 */

namespace Tuleap\Git;

use EventManager;
use Git_RemoteServer_GerritServerFactory;
use Project;

class GerritCanMigrateChecker
{
    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var Git_RemoteServer_GerritServerFactory
     */
    private $gerrit_server_factory;

    public function __construct(
        EventManager $event_manager,
        Git_RemoteServer_GerritServerFactory $gerrit_server_factory
    ) {
        $this->event_manager         = $event_manager;
        $this->gerrit_server_factory = $gerrit_server_factory;
    }

    /**
     * @return bool
     */
    public function canMigrate(Project $project)
    {
        $platform_can_use_gerrit = false;

        $this->event_manager->processEvent(
            GIT_EVENT_PLATFORM_CAN_USE_GERRIT,
            [
                'platform_can_use_gerrit' => &$platform_can_use_gerrit
            ]
        );

        $gerrit_servers = $this->gerrit_server_factory->getAvailableServersForProject($project);

        return $platform_can_use_gerrit && count($gerrit_servers) > 0;
    }
}
