<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

namespace Tuleap\Project;

use Project_Creation_Exception;
use SystemEventProcessor_Factory;
use SystemEvent;
use SystemEventProcessorMutex;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;
use Tuleap\DB\DBFactory;

final class SystemEventRunner implements SystemEventRunnerInterface
{

    /** @var SystemEventProcessor_Factory */
    private $system_event_processor_factory;

    public function __construct(SystemEventProcessor_Factory $system_event_processor_factory)
    {
        $this->system_event_processor_factory = $system_event_processor_factory;
    }

    public function checkPermissions(): void
    {
        // Check we have permissions to create project and run system events
        if (posix_geteuid() !== 0) {
            throw new Project_Creation_Exception("You need to be root to create a project for import");
        }
    }

    public function runSystemEvents(): void
    {
        $processor = $this->system_event_processor_factory->getProcessForQueue(SystemEvent::DEFAULT_QUEUE);

        $store   = new SemaphoreStore();
        $factory = new LockFactory($store);

        $mutex = new SystemEventProcessorMutex($processor, $factory, DBFactory::getMainTuleapDBConnection());
        $mutex->waitAndExecute();
    }
}
