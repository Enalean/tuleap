<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Domain\Workspace\BuildProgramFlags;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\ProgramFlag;

final class PlannedIterations
{
    /**
     * @param ProgramFlag[] $program_flags
     */
    private function __construct(
        private array $program_flags,
    ) {
    }

    /**
     * @throws ProgramIncrementNotFoundException
     */
    public static function build(
        BuildProgramFlags $build_program_flags,
        ProgramIdentifier $program_identifier,
    ): self {
        $program_flags = $build_program_flags->build($program_identifier);

        return new self($program_flags);
    }

    /**
     * @return ProgramFlag[]
     */
    public function getProgramFlag(): array
    {
        return $this->program_flags;
    }
}
