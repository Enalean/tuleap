<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use BackendService;
use Git_GitoliteHousekeeping_ChainOfResponsibility_ServiceRestarter;
use Git_GitoliteHousekeeping_GitoliteHousekeepingResponse;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ServiceRestarterTest extends TestCase
{
    private Git_GitoliteHousekeeping_GitoliteHousekeepingResponse&MockObject $response;
    private BackendService&MockObject $backend_service;
    private Git_GitoliteHousekeeping_ChainOfResponsibility_ServiceRestarter $command;

    #[\Override]
    protected function setUp(): void
    {
        $this->response        = $this->createMock(Git_GitoliteHousekeeping_GitoliteHousekeepingResponse::class);
        $this->backend_service = $this->createMock(BackendService::class);

        $this->command = new Git_GitoliteHousekeeping_ChainOfResponsibility_ServiceRestarter($this->response, $this->backend_service);
    }

    public function testItRestartsTheServiceAndEndsWithSuccess(): void
    {
        $this->response->expects($this->once())->method('info')->with('Restarting service');
        $this->backend_service->expects($this->once())->method('start');
        $this->response->expects($this->once())->method('success');

        $this->command->execute();
    }
}
