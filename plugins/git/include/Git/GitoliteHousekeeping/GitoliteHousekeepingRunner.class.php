<?php
/**
 * Copyright (c) Enalean, 2013-2019. All Rights Reserved.
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

/**
 * I process the clean up of the gitolite admin working copy
 */
class Git_GitoliteHousekeeping_GitoliteHousekeepingRunner
{

    /** @var Git_GitoliteHousekeeping_GitoliteHousekeepingDao */
    private $housekeeping_dao;

    /** @var Git_GitoliteHousekeeping_GitoliteHousekeepingResponse */
    private $response;

    /** @var BackendService */
    private $backend_service;

    public function __construct(
        SystemEventProcessManager $process_manager,
        SystemEventProcess $process,
        Git_GitoliteHousekeeping_GitoliteHousekeepingDao $housekeeping_dao,
        Git_GitoliteHousekeeping_GitoliteHousekeepingResponse $response,
        BackendService $backend_service,
        $gitolite_var_path,
        $remote_admin_repository
    ) {
        $this->process_manager         = $process_manager;
        $this->process                 = $process;
        $this->housekeeping_dao        = $housekeeping_dao;
        $this->response                = $response;
        $this->backend_service         = $backend_service;
        $this->gitolite_var_path       = $gitolite_var_path;
        $this->remote_admin_repository = $remote_admin_repository;
    }

    public function run()
    {
        $this->getChain()->execute();
    }

    /** @return Git_GitoliteHousekeeping_ChainOfResponsibility_Command */
    public function getChain()
    {
        $commands = array(
            new Git_GitoliteHousekeeping_ChainOfResponsibility_ServiceStopper($this->response, $this->backend_service),
            new Git_GitoliteHousekeeping_ChainOfResponsibility_CheckRunningEvents($this->response, $this->process_manager, $this->process),
            new Git_GitoliteHousekeeping_ChainOfResponsibility_CleanUpGitoliteAdminRepo($this->response, $this->gitolite_var_path, $this->remote_admin_repository),
            new Git_GitoliteHousekeeping_ChainOfResponsibility_EnableGitGc($this->response, $this->housekeeping_dao),
            new Git_GitoliteHousekeeping_ChainOfResponsibility_ServiceRestarter($this->response, $this->backend_service)
        );
        $this->setUpChainFromArrayOfCommands($commands);
        $head_of_chain = $commands[0];

        return $head_of_chain;
    }

    private function setUpChainFromArrayOfCommands(array $commands)
    {
        $current_command = null;
        foreach ($commands as $next_command) {
            if ($current_command !== null) {
                $current_command->setNextCommand($next_command);
            }
            $current_command = $next_command;
        }
    }
}
