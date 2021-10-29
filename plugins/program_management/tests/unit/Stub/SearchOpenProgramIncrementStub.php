<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\SearchOpenProgramIncrement;

final class SearchOpenProgramIncrementStub implements SearchOpenProgramIncrement
{
    private function __construct(private array $program_increments)
    {
    }
    public static function with(array $program_increments): self
    {
        return new self($program_increments);
    }

    public static function withoutOpenIncrement(): self
    {
        return new self([]);
    }

    public function searchOpenProgramIncrements(int $program_id): array
    {
        return $this->program_increments;
    }
}
