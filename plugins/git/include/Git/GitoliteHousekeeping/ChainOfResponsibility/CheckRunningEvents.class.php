<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once 'common/backend/BackendService.class.php';
require_once 'common/system_event/SystemEventProcessManager.class.php';
require_once 'common/system_event/SystemEventProcessRoot.class.php';

/**
 * I am checking that the events are not currently processed
 */
class Git_GitoliteHousekeeping_ChainOfResponsibility_CheckRunningEvents extends Git_GitoliteHousekeeping_ChainOfResponsibility_Command {

    /** @var SystemEventDao */
    private $system_event_dao;

    public function __construct(
        Git_GitoliteHousekeeping_GitoliteHousekeepingResponse $response,
        SystemEventProcessManager $process_manager,
        SystemEventProcessRoot $process
    ) {
        parent::__construct();
        $this->process_manager = $process_manager;
        $this->process         = $process;
        $this->response        = $response;
    }

    public function execute() {
        if ($this->process_manager->isAlreadyRunning($this->process)) {
            $this->response->error('There is still an event marked as running. Start again when all events marked as running are done.');
            $this->response->abort();
            return;
        }

        $this->executeNextCommand();
    }
}
