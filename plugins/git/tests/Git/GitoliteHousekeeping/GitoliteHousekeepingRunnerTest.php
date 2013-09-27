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

require_once dirname(__FILE__).'/../../bootstrap.php';
require_once 'common/backend/BackendService.class.php';

class Git_GitoliteHousekeeping_GitoliteHousekeepingRunnerTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->process_manager  = mock('SystemEventProcessManager');
        $this->process          = mock('SystemEventProcessRoot');
        $this->housekeeping_dao = mock('Git_GitoliteHousekeeping_GitoliteHousekeepingDao');
        $this->response         = mock('Git_GitoliteHousekeeping_GitoliteHousekeepingResponse');
        $this->backend_service  = mock('BackendService');

        $this->gitolite_var_path       = 'gitolite_var_path';
        $this->remote_admin_repository = 'remote_admin_repository';

        $this->runner = new Git_GitoliteHousekeeping_GitoliteHousekeepingRunner(
            $this->process_manager,
            $this->process,
            $this->housekeeping_dao,
            $this->response,
            $this->backend_service,
            $this->gitolite_var_path,
            $this->remote_admin_repository
        );
    }

    public function itLoadsAllCommandsInTheRightOrder() {
        $expected_commands = array(
            'Git_GitoliteHousekeeping_ChainOfResponsibility_ServiceStopper',
            'Git_GitoliteHousekeeping_ChainOfResponsibility_CheckRunningEvents',
            'Git_GitoliteHousekeeping_ChainOfResponsibility_CleanUpGitoliteAdminRepo',
            'Git_GitoliteHousekeeping_ChainOfResponsibility_EnableGitGc',
            'Git_GitoliteHousekeeping_ChainOfResponsibility_ServiceRestarter',
            'Git_GitoliteHousekeeping_ChainOfResponsibility_DoNothing'
        );

        $chain = $this->runner->getChain();

        foreach ($expected_commands as $expected_command) {
            $this->assertIsA($chain, $expected_command);
            $chain = $chain->getNextCommand();
        }
    }
}
