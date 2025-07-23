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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\SearchOpenProgramIncrements;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class SearchOpenProgramIncrementsStub implements SearchOpenProgramIncrements
{
    private function __construct(private array $program_increments)
    {
    }

    /**
     * @no-named-arguments
     */
    public static function withProgramIncrements(ProgramIncrement $first_pi, ProgramIncrement ...$other_pis): self
    {
        return new self([$first_pi, ...$other_pis]);
    }

    public static function withoutProgramIncrements(): self
    {
        return new self([]);
    }

    #[\Override]
    public function searchOpenProgramIncrements(int $potential_program_id, UserIdentifier $user): array
    {
        return $this->program_increments;
    }
}
