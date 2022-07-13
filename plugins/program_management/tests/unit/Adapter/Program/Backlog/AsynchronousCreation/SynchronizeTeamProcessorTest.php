<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Psr\Log\Test\TestLogger;
use Tuleap\ProgramManagement\Tests\Stub\TeamSynchronizationEventStub;

class SynchronizeTeamProcessorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItHandlesTeamSynchronizationEvents(): void
    {
        $logger = new TestLogger();
        $event  = TeamSynchronizationEventStub::buildWithIds(1, 123);
        (new SynchronizeTeamProcessor($logger))->processTeamSynchronization($event);

        self::assertTrue($logger->hasDebugThatContains("Team 123 of Program 1 needs PI and Iterations synchronization"));
    }
}
