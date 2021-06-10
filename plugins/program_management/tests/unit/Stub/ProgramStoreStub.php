<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

use Exception;
use Tuleap\ProgramManagement\Domain\Program\ProgramStore;

final class ProgramStoreStub implements ProgramStore
{
    /**
     * @var int[]
     */
    private array $team_ids;

    /**
     * @param int[] $team_ids
     */
    public function __construct(array $team_ids)
    {
        $this->team_ids = $team_ids;
    }

    public function isProjectAProgramProject(int $project_id): bool
    {
        throw new Exception('Not implemented');
    }

    public function getTeamProjectIdsForGivenProgramProject(int $project_id): array
    {
        return array_map(
            static fn(int $team_id) => ['team_project_id' => $team_id],
            $this->team_ids
        );
    }

    public function saveProgram(int $program_project_id, int $team_project_id): void
    {
        throw new Exception('Not implemented');
    }

    public static function buildTeams(int ...$ids): self
    {
        return new self($ids);
    }
}
