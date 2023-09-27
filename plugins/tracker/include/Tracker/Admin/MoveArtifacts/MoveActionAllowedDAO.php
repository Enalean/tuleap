<?php
/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Admin\MoveArtifacts;

use Tuleap\DB\DataAccessObject;

class MoveActionAllowedDAO extends DataAccessObject
{
    public function isMoveActionAllowedInTracker(int $tracker_id): bool
    {
        $sql = "SELECT NULL
                FROM plugin_tracker_forbidden_move_action
                WHERE tracker_id = ?";

        $rows = $this->getDB()->run($sql, $tracker_id);

        return count($rows) === 0;
    }

    public function enableMoveArtifactInTracker(int $tracker_id): void
    {
        $this->getDB()->delete(
            'plugin_tracker_forbidden_move_action',
            ['tracker_id' => $tracker_id],
        );
    }

    public function forbidMoveArtifactInTracker(int $tracker_id): void
    {
        $sql = 'REPLACE INTO plugin_tracker_forbidden_move_action (tracker_id)
                VALUES (?)';

        $this->getDB()->run($sql, $tracker_id);
    }
}
