<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

final class PendingIterationCreationProxyTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ITERATION_ID           = 70;
    private const PROGRAM_INCREMENT_ID   = 95;
    private const USER_ID                = 193;
    private const ITERATION_CHANGESET_ID = 3024;

    public function testItBuildsFromPrimitives(): void
    {
        $pending_creation = new PendingIterationCreationProxy(
            self::ITERATION_ID,
            self::PROGRAM_INCREMENT_ID,
            self::USER_ID,
            self::ITERATION_CHANGESET_ID
        );
        self::assertSame(self::ITERATION_ID, $pending_creation->getIterationId());
        self::assertSame(self::PROGRAM_INCREMENT_ID, $pending_creation->getProgramIncrementId());
        self::assertSame(self::USER_ID, $pending_creation->getUserId());
        self::assertSame(self::ITERATION_CHANGESET_ID, $pending_creation->getIterationChangesetId());
    }
}
