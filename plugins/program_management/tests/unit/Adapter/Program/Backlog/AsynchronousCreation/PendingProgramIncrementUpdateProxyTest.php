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

final class PendingProgramIncrementUpdateProxyTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_INCREMENT_ID = 134;
    private const USER_ID              = 113;
    private const CHANGESET_ID         = 3250;

    public function testItBuildsFromPrimitives(): void
    {
        $pending_update = new PendingProgramIncrementUpdateProxy(
            self::PROGRAM_INCREMENT_ID,
            self::USER_ID,
            self::CHANGESET_ID
        );
        self::assertSame(self::PROGRAM_INCREMENT_ID, $pending_update->getProgramIncrementId());
        self::assertSame(self::USER_ID, $pending_update->getUserId());
        self::assertSame(self::CHANGESET_ID, $pending_update->getChangesetId());
    }
}
