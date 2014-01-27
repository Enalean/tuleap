<?php

/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\ProFTPd;

use Tuleap\ProFTPd\SystemEvent;

class SystemEventManager {
    /** @var SystemEventManager */
    private $system_event_manager;

    public function __construct(\SystemEventManager $system_event_manager) {
        $this->system_event_manager = $system_event_manager;
    }

    public function queueDirectoryCreate($project_name) {
        $this->system_event_manager->createEvent(
            SystemEvent\PROFTPD_DIRECTORY_CREATE::NAME,
            $project_name,
            \SystemEvent::PRIORITY_HIGH,
            \SystemEvent::OWNER_ROOT
        );
    }

    public function getTypes() {
        return array(
            SystemEvent\PROFTPD_DIRECTORY_CREATE::NAME,
        );
    }
}
