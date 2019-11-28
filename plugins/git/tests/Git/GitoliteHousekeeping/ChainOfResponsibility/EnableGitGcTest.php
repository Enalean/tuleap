<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

require_once __DIR__ .'/../../../bootstrap.php';

class Git_GitoliteHousekeeping_ChainOfResponsibility_EnableGitGcTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->response = \Mockery::spy(\Git_GitoliteHousekeeping_GitoliteHousekeepingResponse::class);
        $this->dao      = \Mockery::spy(Git_GitoliteHousekeeping_GitoliteHousekeepingDao::class);

        $this->command = new Git_GitoliteHousekeeping_ChainOfResponsibility_EnableGitGc($this->response, $this->dao);
    }

    public function itEnablesGitGc()
    {
        $this->response->shouldReceive('info')->with('Enabling git gc')->once();
        $this->dao->shouldReceive('enableGitGc')->once();

        $this->command->execute();
    }

    public function itExecutesTheNextCommand()
    {
        $next = \Mockery::spy(\Git_GitoliteHousekeeping_ChainOfResponsibility_Command::class);
        $next->shouldReceive('execute')->once();

        $this->command->setNextCommand($next);

        $this->command->execute();
    }
}
