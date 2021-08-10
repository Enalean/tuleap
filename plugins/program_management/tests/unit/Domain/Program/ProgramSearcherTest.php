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

namespace Tuleap\ProgramManagement\Domain\Program;

use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;

final class ProgramSearcherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItReturnsAProgramFromAProgramIncrementID(): void
    {
        $user_identifier = UserIdentifierStub::buildGenericUser();
        $searcher        = new ProgramSearcher($this->getStubDao(), BuildProgramStub::stubValidProgram());
        $result          = $searcher->getProgramOfProgramIncrement(42, $user_identifier);

        self::assertEquals(ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 101, $user_identifier), $result);
    }

    public function testItThrowsIfProgramWasNotFound(): void
    {
        $user_identifier = UserIdentifierStub::buildGenericUser();
        $searcher        = new ProgramSearcher($this->getStubDao(true), BuildProgramStub::stubValidProgram());
        $this->expectException(ProgramNotFoundException::class);

        $searcher->getProgramOfProgramIncrement(404, $user_identifier);
    }

    private function getStubDao(bool $return_null = false): SearchProgram
    {
        return new class ($return_null) implements SearchProgram {
            private bool $return_null;

            public function __construct(bool $return_null)
            {
                $this->return_null = $return_null;
            }

            public function searchProgramOfProgramIncrement(int $program_increment_id): ?int
            {
                return ($this->return_null) ? null : 101;
            }
        };
    }
}
