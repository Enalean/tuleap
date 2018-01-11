<?php
/**
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
 * 
 * Tuleap and Enalean names and logos are registrated trademarks owned by
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
require_once 'SystemEventProcessor.class.php';

class SystemEventProcessor_ApplicationOwner extends SystemEventProcessor {

    public function getOwner() {
        return SystemEvent::OWNER_APP;
    }

    protected function postEventsActions(array $executed_events_ids, $queue_name) {
        $this->ensureWorkersAreRunning();
        $params = array(
            'executed_events_ids'  => $executed_events_ids,
            'queue_name'           => $queue_name
        );

        EventManager::instance()->processEvent(
            Event::POST_SYSTEM_EVENTS_ACTIONS,
            $params
        );
    }

    private function ensureWorkersAreRunning()
    {
        $this->logger->debug("Check if backend workers are running");
        for($i = 0; $i < $this->getBackendWorkerCount(); $i++) {
            \Tuleap\Queue\Worker::run($this->logger, $i);
        }
    }

    private function getBackendWorkerCount()
    {
       if (ForgeConfig::get('sys_nb_backend_workers') !== false) {
           return abs((int) ForgeConfig::get('sys_nb_backend_workers'));
       }
       if (ForgeConfig::get('sys_async_emails') !== false) {
           return 1;
       }
       return 0;
    }

    public function getProcessOwner() {
        return ForgeConfig::get('sys_http_user');
    }
}
