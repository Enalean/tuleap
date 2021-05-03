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

use PHPUnit\Framework\TestCase;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;

final class ProgramSearcherTest extends TestCase
{
    public function testItReturnsAProgramFromAProgramIncrementID(): void
    {
        $searcher = new ProgramSearcher($this->getStubDao(), BuildProgramStub::stubValidProgram());
        $result   = $searcher->getProgramOfProgramIncrement(42);

        self::assertEquals(ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 101), $result);
    }

    public function testItThrowsIfProgramWasNotFound(): void
    {
        $searcher = new ProgramSearcher($this->getStubDao(true), BuildProgramStub::stubValidProgram());
        $this->expectException(ProgramNotFoundException::class);

        $searcher->getProgramOfProgramIncrement(404);
    }

    private function getStubDao(bool $return_null = false): SearchProgram
    {
        return new class ($return_null) implements SearchProgram {
            /** @var bool */
            private $return_null;

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
