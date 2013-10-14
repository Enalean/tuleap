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

class Git_GitoliteHousekeeping_ChainOfResponsibility_ServiceStopperTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->response         = mock('Git_GitoliteHousekeeping_GitoliteHousekeepingResponse');
        $this->backend_service  = mock('BackendService');

        $this->command = new Git_GitoliteHousekeeping_ChainOfResponsibility_ServiceStopper($this->response, $this->backend_service);
    }

    public function itStopsTheService() {
        expect($this->response)->info('Stopping service')->once();
        expect($this->backend_service)->stop()->once();

        $this->command->execute();
    }

    public function itExecutesTheNextCommand() {
        $next = mock('Git_GitoliteHousekeeping_ChainOfResponsibility_Command');
        expect($next)->execute()->once();

        $this->command->setNextCommand($next);

        $this->command->execute();
    }
}
