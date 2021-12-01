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

namespace Tuleap\Tracker\Migration;

use Tuleap\DB\DataAccessObject;

class LegacyTrackerMigrationDao extends DataAccessObject
{
    public function isLegacyTrackerAlreadyMigratedWithOriginalIds(int $legacy_tracker_ids): bool
    {
        $sql = "SELECT NULL
                FROM plugin_tracker_legacy_tracker_migrated
                WHERE legacy_tracker_id = ?";

        $rows = $this->getDB()->run($sql, $legacy_tracker_ids);

        return count($rows) > 0;
    }

    public function flagLegacyTrackerMigratedWithOriginalIds(int $legacy_tracker_ids): void
    {
        $this->getDB()->insert(
            'plugin_tracker_legacy_tracker_migrated',
            [
                'legacy_tracker_id' => $legacy_tracker_ids,
            ]
        );
    }
}
