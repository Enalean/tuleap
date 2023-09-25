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

namespace Tuleap\Tracker;

use Tuleap\DB\DataAccessObject;

class PromotedTrackerDao extends DataAccessObject
{
    public function searchByProjectId(int $project_id): array
    {
        return $this->getDB()->run(
            "SELECT tracker.*
                FROM tracker
                    INNER JOIN plugin_tracker_promoted AS dropdown ON tracker.id = dropdown.tracker_id
                WHERE tracker.group_id = ?
                    AND tracker.deletion_date IS NULL
                ORDER BY tracker.name",
            $project_id
        );
    }

    public function isContaining(int $tracker_id): bool
    {
        $statement = 'SELECT TRUE FROM plugin_tracker_promoted WHERE tracker_id = ?';

        return $this->getDB()->cell($statement, $tracker_id) !== false;
    }

    public function insert(int $tracker_id): void
    {
        $this->getDB()->insertIgnore('plugin_tracker_promoted', ['tracker_id' => $tracker_id]);
    }

    public function delete(int $tracker_id): void
    {
        $this->getDB()->delete('plugin_tracker_promoted', ['tracker_id' => $tracker_id]);
    }
}
