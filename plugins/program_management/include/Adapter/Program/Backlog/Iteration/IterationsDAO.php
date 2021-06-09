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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\Iteration;

use Tuleap\DB\DataAccessObject;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\VerifyIsIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveIterationTracker;

final class IterationsDAO extends DataAccessObject implements VerifyIsIterationTracker, RetrieveIterationTracker
{
    public function isIterationTracker(int $tracker_id): bool
    {
        $sql  = 'SELECT NULL FROM plugin_program_management_program WHERE iteration_tracker_id = ?';
        $rows = $this->getDB()->run($sql, $tracker_id);

        return count($rows) > 0;
    }

    public function getIterationTrackerId(int $project_id): ?int
    {
        $sql = 'SELECT iteration_tracker_id FROM plugin_program_management_program
                INNER JOIN tracker ON tracker.id = plugin_program_management_program.iteration_tracker_id
                    WHERE tracker.group_id = ?';

        $tracker_id = $this->getDB()->cell($sql, $project_id);
        if (! $tracker_id) {
            return null;
        }

        return $tracker_id;
    }
}
