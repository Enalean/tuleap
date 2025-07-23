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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Feature;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementUpdate;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementCreationBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementUpdateBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramIncrementChangedTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_INCREMENT_ID = 964;
    private const TRACKER_ID           = 50;
    private const USER_ID              = 104;
    private ProgramIncrementCreation $creation;
    private ProgramIncrementUpdate $update;

    #[\Override]
    protected function setUp(): void
    {
        $this->creation = ProgramIncrementCreationBuilder::buildWithIds(
            self::USER_ID,
            self::PROGRAM_INCREMENT_ID,
            self::TRACKER_ID,
            2886
        );
        $this->update   = ProgramIncrementUpdateBuilder::buildWithIds(
            self::USER_ID,
            self::PROGRAM_INCREMENT_ID,
            self::TRACKER_ID,
            8949,
            8948
        );
    }

    public function testItBuildsFromCreation(): void
    {
        $change = ProgramIncrementChanged::fromCreation($this->creation);
        self::assertSame(self::PROGRAM_INCREMENT_ID, $change->program_increment->getId());
        self::assertSame(self::TRACKER_ID, $change->tracker->getId());
        self::assertSame(self::USER_ID, $change->user->getId());
    }

    public function testItBuildsFromUpdate(): void
    {
        $change = ProgramIncrementChanged::fromUpdate($this->update);
        self::assertSame(self::PROGRAM_INCREMENT_ID, $change->program_increment->getId());
        self::assertSame(self::TRACKER_ID, $change->tracker->getId());
        self::assertSame(self::USER_ID, $change->user->getId());
    }
}
