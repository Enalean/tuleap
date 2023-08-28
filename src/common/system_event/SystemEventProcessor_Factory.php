<?php
/**
 * Copyright Enalean (c) 2015-Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

use Tuleap\SVNCore\ApacheConfGenerator;
use Tuleap\SystemEvent\GetSystemEventQueuesEvent;

class SystemEventProcessor_Factory
{
    /** @var EventManager */
    private $event_manager;

    /** @var SystemEventManager */
    private $system_event_manager;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    public function __construct(\Psr\Log\LoggerInterface $logger, SystemEventManager $system_event_manager, EventManager $event_manager)
    {
        $this->logger               = $logger;
        $this->system_event_manager = $system_event_manager;
        $this->event_manager        = $event_manager;
    }

    public function getProcessForQueue($request_queue)
    {
        $owner = SystemEvent::OWNER_APP;
        $event = new GetSystemEventQueuesEvent([]);
        $this->event_manager->processEvent(
            $event
        );

        $custom_queues = $event->getAvailableQueues();

        if (isset($custom_queues[$request_queue])) {
            $this->logger = $custom_queues[$request_queue]->getLogger();
            $this->logger->debug('Processing ' . $request_queue . ' queue.');
            $process = new SystemEventProcessCustomQueue($request_queue);
            $owner   = $custom_queues[$request_queue]->getOwner();
        } else {
            switch ($request_queue) {
                case SystemEvent::OWNER_APP:
                    $this->logger->debug('Processing default queue as app user.');
                    $process = new SystemEventProcessApplicationOwnerDefaultQueue();
                    break;
                case SystemEvent::DEFAULT_QUEUE:
                    $this->logger->debug('Processing default queue as root user.');
                    $owner   = SystemEvent::OWNER_ROOT;
                    $process = new SystemEventProcessRootDefaultQueue();
                    break;
                default:
                    $this->logger->debug('Ignoring ' . $request_queue . ' queue.');
                    exit(0);
            }
        }

        if ($owner === SystemEvent::OWNER_APP) {
            return new SystemEventProcessor_ApplicationOwner(
                $process,
                $this->system_event_manager,
                new SystemEventDao(),
                $this->logger
            );
        }

        return new SystemEventProcessor_Root(
            $process,
            $this->system_event_manager,
            new SystemEventDao(),
            $this->logger,
            Backend::instance('Aliases'),
            Backend::instanceSVN(),
            Backend::instance('System'),
            new SiteCache($this->logger),
            ApacheConfGenerator::build(),
        );
    }
}
