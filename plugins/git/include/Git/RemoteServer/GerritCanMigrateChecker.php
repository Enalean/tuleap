<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Git\RemoteServer;

use Git_RemoteServer_GerritServerFactory;
use Project;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Git\Git\RemoteServer\GerritCanMigrateEvent;

class GerritCanMigrateChecker
{
    public function __construct(
        private readonly EventDispatcherInterface $event_event_dispatcher,
        private readonly Git_RemoteServer_GerritServerFactory $gerrit_server_factory,
    ) {
    }

    public function canMigrate(Project $project): bool
    {
        $event = $this->event_event_dispatcher->dispatch(new GerritCanMigrateEvent());

        if (! $event->canPlatformUseGerrit()) {
            return false;
        }

        $gerrit_servers = $this->gerrit_server_factory->getAvailableServersForProject($project);

        return count($gerrit_servers) > 0;
    }
}
