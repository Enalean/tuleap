<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\FormElement\Burnup;

use Tuleap\DB\DataAccessObject;

class CountElementsCacheDao extends DataAccessObject
{
    public function saveCachedFieldValueAtTimestampForSubelements(
        int $artifact_id,
        int $timestamp,
        int $total_subelements,
        int $closed_subelements
    ): void {
        $this->getDB()->run(
            "REPLACE INTO plugin_agiledashboard_tracker_field_burnup_cache_subelements
            (artifact_id, timestamp, total_subelements, closed_subelements)
            VALUES (?, ?, ?, ?)",
            $artifact_id,
            $timestamp,
            $total_subelements,
            $closed_subelements
        );
    }

    public function searchCachedDaysValuesByArtifactId(int $artifact_id, int $start_timestamp): ?array
    {
        return $this->getDB()->run(
            "SELECT timestamp, closed_subelements, total_subelements
             FROM plugin_agiledashboard_tracker_field_burnup_cache_subelements
             WHERE artifact_id = ?
             AND timestamp >= ?",
            $artifact_id,
            $start_timestamp
        );
    }
}
