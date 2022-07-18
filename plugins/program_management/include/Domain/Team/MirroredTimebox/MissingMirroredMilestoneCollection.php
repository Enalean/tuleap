<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\ProgramManagement\Domain\Team\MirroredTimebox;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrement;
use Tuleap\ProgramManagement\Domain\ProjectReference;

/**
 * @psalm-immutable
 */
final class MissingMirroredMilestoneCollection
{
    /**
     * @param int[] $missing_program_increments_ids
     */
    private function __construct(public int $team_id, public array $missing_program_increments_ids)
    {
    }

    /**
     * @param ProgramIncrement[] $open_program_increments
     * @param ProjectReference[] $aggregated_teams
     * @return self[]
     */
    public static function buildCollectionFromProgramIdentifier(
        SearchMirrorTimeboxesFromProgram $timebox_searcher,
        array $open_program_increments,
        array $aggregated_teams,
    ): array {
        $missing_open_program_increments = [];
        foreach ($aggregated_teams as $team) {
            $missing_program_increment_ids = [];
            foreach ($open_program_increments as $open_program_increment) {
                if (! $timebox_searcher->hasMirroredTimeboxesFromProgram($team, $open_program_increment)) {
                    $missing_program_increment_ids[] = $open_program_increment->id;
                }
            }

            if (! empty($missing_program_increment_ids)) {
                $missing_open_program_increments[$team->getId()] = new self($team->getId(), $missing_program_increment_ids);
            }
        }

        return $missing_open_program_increments;
    }

    /**
     * @param ProgramIncrement[] $open_program_increments
     * @param ProjectReference[] $aggregated_teams
     */
    public static function buildFromProgramIdentifierAndTeam(
        SearchMirrorTimeboxesFromProgram $timebox_searcher,
        array $open_program_increments,
        ProjectReference $team,
    ): ?self {
        $missing_program_increment_ids = [];
        foreach ($open_program_increments as $open_program_increment) {
            if (! $timebox_searcher->hasMirroredTimeboxesFromProgram($team, $open_program_increment)) {
                $missing_program_increment_ids[] = $open_program_increment->id;
            }
        }

        if (! empty($missing_program_increment_ids)) {
            return new self($team->getId(), $missing_program_increment_ids);
        }

        return null;
    }
}
