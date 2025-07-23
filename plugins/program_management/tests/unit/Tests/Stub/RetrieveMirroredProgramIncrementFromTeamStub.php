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

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrieveMirroredProgramIncrementFromTeam;
use Tuleap\ProgramManagement\Domain\Team\TeamIdentifier;

final class RetrieveMirroredProgramIncrementFromTeamStub implements RetrieveMirroredProgramIncrementFromTeam
{
    /**
     * @param int[] $ids
     */
    private function __construct(
        private bool $should_return_null,
        private array $ids,
    ) {
    }

    /**
     * @no-named-arguments
     */
    public static function withIds(int $id, int ...$other_ids): self
    {
        return new self(false, [$id, ...$other_ids]);
    }

    public static function withNoMirror(): self
    {
        return new self(true, []);
    }

    #[\Override]
    public function getMirrorId(ProgramIncrementIdentifier $program_increment, TeamIdentifier $team): ?int
    {
        if ($this->should_return_null) {
            return null;
        }
        if (count($this->ids) > 0) {
            return array_shift($this->ids);
        }
        throw new \LogicException('No mirrored program increment ids configured');
    }
}
