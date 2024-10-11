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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Git_GitoliteHousekeeping_ChainOfResponsibility_EnableGitGcTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Git_GitoliteHousekeeping_GitoliteHousekeepingResponse&\Mockery\MockInterface
     */
    private $response;
    /**
     * @var Git_GitoliteHousekeeping_GitoliteHousekeepingDao&\Mockery\MockInterface
     */
    private $dao;
    private Git_GitoliteHousekeeping_ChainOfResponsibility_EnableGitGc $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->response = \Mockery::spy(\Git_GitoliteHousekeeping_GitoliteHousekeepingResponse::class);
        $this->dao      = \Mockery::spy(Git_GitoliteHousekeeping_GitoliteHousekeepingDao::class);

        $this->command = new Git_GitoliteHousekeeping_ChainOfResponsibility_EnableGitGc($this->response, $this->dao);
    }

    public function testItEnablesGitGc(): void
    {
        $this->response->shouldReceive('info')->with('Enabling git gc')->once();
        $this->dao->shouldReceive('enableGitGc')->once();

        $this->command->execute();
    }

    public function testItExecutesTheNextCommand(): void
    {
        $next = \Mockery::spy(\Git_GitoliteHousekeeping_ChainOfResponsibility_Command::class);
        $next->shouldReceive('execute')->once();

        $this->command->setNextCommand($next);

        $this->command->execute();
    }
}
