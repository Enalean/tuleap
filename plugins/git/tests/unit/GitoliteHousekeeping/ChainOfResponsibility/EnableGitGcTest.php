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

declare(strict_types=1);

namespace Tuleap\Git\GitoliteHousekeeping\ChainOfResponsibility;

use Git_GitoliteHousekeeping_ChainOfResponsibility_Command;
use Git_GitoliteHousekeeping_ChainOfResponsibility_EnableGitGc;
use Git_GitoliteHousekeeping_GitoliteHousekeepingDao;
use Git_GitoliteHousekeeping_GitoliteHousekeepingResponse;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class EnableGitGcTest extends TestCase
{
    private Git_GitoliteHousekeeping_GitoliteHousekeepingResponse&MockObject $response;
    private Git_GitoliteHousekeeping_GitoliteHousekeepingDao&MockObject $dao;
    private Git_GitoliteHousekeeping_ChainOfResponsibility_EnableGitGc $command;

    #[\Override]
    protected function setUp(): void
    {
        $this->response = $this->createMock(Git_GitoliteHousekeeping_GitoliteHousekeepingResponse::class);
        $this->dao      = $this->createMock(Git_GitoliteHousekeeping_GitoliteHousekeepingDao::class);

        $this->command = new Git_GitoliteHousekeeping_ChainOfResponsibility_EnableGitGc($this->response, $this->dao);
    }

    public function testItEnablesGitGc(): void
    {
        $this->response->expects($this->once())->method('info')->with('Enabling git gc');
        $this->dao->expects($this->once())->method('enableGitGc');

        $this->command->execute();
    }

    public function testItExecutesTheNextCommand(): void
    {
        $next = $this->createMock(Git_GitoliteHousekeeping_ChainOfResponsibility_Command::class);
        $next->expects($this->once())->method('execute');

        $this->command->setNextCommand($next);

        $this->response->method('info');
        $this->dao->method('enableGitGc');
        $this->command->execute();
    }
}
