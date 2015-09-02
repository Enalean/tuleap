<?php
/**
 * Copyright (c) Enalean SAS, 2013-2014. All rights reserved
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once 'pre.php';

$request_queue = (isset($argv[1])) ? $argv[1] : SystemEvent::DEFAULT_QUEUE;

$logger        = new BackendLogger();
$owner         = SystemEvent::OWNER_APP;
$custom_queues = array();
EventManager::instance()->processEvent(
    Event::SYSTEM_EVENT_GET_CUSTOM_QUEUES,
    array(
        'queues' => &$custom_queues,
    )
);
if (isset($custom_queues[$request_queue])) {
    $logger = $custom_queues[$request_queue]->getLogger();
    $logger->debug('Processing '. $request_queue .' queue.');
    $process = new SystemEventProcessCustomQueue($request_queue);
    $owner   = $custom_queues[$request_queue]->getOwner();
} else {
    switch ($request_queue) {
        case SystemEvent::OWNER_APP:
            $logger->debug('Processing default queue as app user.');
            $process = new SystemEventProcessApplicationOwnerDefaultQueue();
            break;
        case SystemEvent::DEFAULT_QUEUE:
            $logger->debug('Processing default queue as root user.');
            $owner   = SystemEvent::OWNER_ROOT;
            $process = new SystemEventProcessRootDefaultQueue();
            break;
        default:
            $logger->debug('Ignoring '. $request_queue .' queue.');
            exit(0);
    }
}

if ($owner === SystemEvent::OWNER_APP) {
    require_once 'common/system_event/SystemEventProcessor_ApplicationOwner.class.php';
    $processor = new SystemEventProcessor_ApplicationOwner(
        $process,
        $system_event_manager,
        new SystemEventDao(),
        $logger
    );
} else {
    require_once 'common/system_event/SystemEventProcessor_Root.class.php';
    $processor = new SystemEventProcessor_Root(
        $process,
        $system_event_manager,
        new SystemEventDao(),
        $logger,
        Backend::instance('Aliases'),
        Backend::instance('CVS'),
        Backend::instance('SVN'),
        Backend::instance('System'),
        new SiteCache($logger)
    );
}

$mutex = new SystemEventProcessorMutex(new SystemEventProcessManager(), $processor);
$mutex->execute();
