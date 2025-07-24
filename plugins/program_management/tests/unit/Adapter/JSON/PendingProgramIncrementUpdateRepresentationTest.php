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

namespace Tuleap\ProgramManagement\Adapter\JSON;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementUpdate;
use Tuleap\ProgramManagement\Tests\Builder\IterationCreationBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementUpdateBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PendingProgramIncrementUpdateRepresentationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_ITERATION_ID                 = 276;
    private const SECOND_ITERATION_ID                = 245;
    private const FIRST_ITERATION_CHANGESET_ID       = 4807;
    private const SECOND_ITERATION_CHANGESET_ID      = 2710;
    private const PROGRAM_INCREMENT_ID               = 54;
    private const PROGRAM_INCREMENT_CHANGESET_ID     = 9556;
    private const PROGRAM_INCREMENT_OLD_CHANGESET_ID = 9555;
    private const USER_ID                            = 199;
    private ProgramIncrementUpdate $update;

    #[\Override]
    protected function setUp(): void
    {
        $this->update = ProgramIncrementUpdateBuilder::buildWithIds(
            self::USER_ID,
            self::PROGRAM_INCREMENT_ID,
            97,
            self::PROGRAM_INCREMENT_CHANGESET_ID,
            self::PROGRAM_INCREMENT_OLD_CHANGESET_ID
        );
    }

    public function testItBuildsFromUpdateAndCreations(): void
    {
        $first_creation  = IterationCreationBuilder::buildWithIds(
            self::FIRST_ITERATION_ID,
            53,
            self::PROGRAM_INCREMENT_ID,
            self::USER_ID,
            self::FIRST_ITERATION_CHANGESET_ID
        );
        $second_creation = IterationCreationBuilder::buildWithIds(
            self::SECOND_ITERATION_ID,
            53,
            self::PROGRAM_INCREMENT_ID,
            self::USER_ID,
            self::SECOND_ITERATION_CHANGESET_ID
        );

        $representation = PendingProgramIncrementUpdateRepresentation::fromUpdateAndCreations(
            $this->update,
            $first_creation,
            $second_creation
        );
        self::assertSame(self::PROGRAM_INCREMENT_ID, $representation->program_increment_id);
        self::assertSame(self::USER_ID, $representation->user_id);
        self::assertSame(self::PROGRAM_INCREMENT_CHANGESET_ID, $representation->changeset_id);
        [$first_representation, $second_representation] = $representation->iterations;
        self::assertSame(self::FIRST_ITERATION_ID, $first_representation->id);
        self::assertSame(self::FIRST_ITERATION_CHANGESET_ID, $first_representation->changeset_id);
        self::assertSame(self::SECOND_ITERATION_ID, $second_representation->id);
        self::assertSame(self::SECOND_ITERATION_CHANGESET_ID, $second_representation->changeset_id);
    }

    public function testItBuildsWithEmptyIterations(): void
    {
        $representation = PendingProgramIncrementUpdateRepresentation::fromUpdateAndCreations(
            $this->update
        );
        self::assertEmpty($representation->iterations);
    }
}
