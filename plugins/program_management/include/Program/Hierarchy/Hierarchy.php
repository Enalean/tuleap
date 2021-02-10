<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Program\Hierarchy;

/**
 * @psalm-immutable
 */
final class Hierarchy
{
    /**
     * @var int
     */
    private $program_tracker_id;
    /**
     * @var int[]
     */
    private $team_backlog_ids;

    public function __construct(int $program_tracker_id, array $team_backlog_ids)
    {
        $this->program_tracker_id = $program_tracker_id;
        $this->team_backlog_ids   = $team_backlog_ids;
    }

    public function getProgramTrackerId(): int
    {
        return $this->program_tracker_id;
    }

    public function getTeamBacklogIds(): array
    {
        return $this->team_backlog_ids;
    }
}
