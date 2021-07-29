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

namespace Tuleap\ProgramManagement\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\SearchIterations;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;

final class SearchIterationsStub implements SearchIterations
{
    /**
     * @var int[]
     */
    private array $iteration_ids;

    private function __construct(int ...$iteration_ids)
    {
        $this->iteration_ids = $iteration_ids;
    }

    public function searchIterations(ProgramIncrementIdentifier $program_increment): array
    {
        return $this->iteration_ids;
    }

    public static function withIterationIds(int ...$iteration_ids): self
    {
        return new self(...$iteration_ids);
    }
}
