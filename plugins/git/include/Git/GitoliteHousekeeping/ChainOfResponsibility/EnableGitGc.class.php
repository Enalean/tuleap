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

/**
 * I enable git gc in next system events
 */
class Git_GitoliteHousekeeping_ChainOfResponsibility_EnableGitGc extends Git_GitoliteHousekeeping_ChainOfResponsibility_Command
{

    /** @var Git_GitoliteHousekeeping_GitoliteHousekeepingResponse */
    private $response;

    /** @var Git_GitoliteHousekeeping_GitoliteHousekeepingDao */
    private $housekeeping_dao;

    public function __construct(
        Git_GitoliteHousekeeping_GitoliteHousekeepingResponse $response,
        Git_GitoliteHousekeeping_GitoliteHousekeepingDao $housekeeping_dao
    ) {
        parent::__construct();
        $this->housekeeping_dao = $housekeeping_dao;
        $this->response         = $response;
    }

    public function execute()
    {
        $this->response->info('Enabling git gc');
        $this->housekeeping_dao->enableGitGc();

        $this->executeNextCommand();
    }
}
