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

require_once dirname(__FILE__).'/../../../bootstrap.php';
require_once 'common/backend/BackendService.class.php';

class Git_GitoliteHousekeeping_ChainOfResponsibility_CheckRunningEventsTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->process_manager = mock('SystemEventProcessManager');
        $this->process         = mock('SystemEventProcessRoot');
        $this->response        = mock('Git_GitoliteHousekeeping_GitoliteHousekeepingResponse');
        $this->next            = mock('Git_GitoliteHousekeeping_ChainOfResponsibility_Command');

        $this->command = new Git_GitoliteHousekeeping_ChainOfResponsibility_CheckRunningEvents($this->response, $this->process_manager, $this->process);
        $this->command->setNextCommand($this->next);
    }

    public function itExecuteTheNextCommandIfThereIsNoRunningEvents() {
        stub($this->process_manager)->isAlreadyRunning($this->process)->returns(false);

        expect($this->next)->execute()->once();

        $this->command->execute();
    }

    public function itDoesNotExectuteTheNextCommandIfThereIsARunningEvent() {
        stub($this->process_manager)->isAlreadyRunning($this->process)->returns(true);

        expect($this->next)->execute()->never();

        $this->command->execute();
    }

    public function itStopsTheExecutionWhenThereIsARemainingSystemEventRunning() {
        stub($this->process_manager)->isAlreadyRunning($this->process)->returns(true);

        expect($this->response)->error('There is still an event marked as running. Start again when all events marked as running are done.')->once();
        expect($this->response)->abort()->once();

        $this->command->execute();
    }
}
