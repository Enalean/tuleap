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

namespace Tuleap\ScaledAgile\Adapter\Program\Plan;

use Tuleap\DB\DataAccessObject;
use Tuleap\ScaledAgile\Program\Plan\Plan;
use Tuleap\ScaledAgile\Program\Plan\PlanStore;
use Tuleap\ScaledAgile\TrackerData;

final class PlanDao extends DataAccessObject implements PlanStore
{
    /**
     * @throws \Throwable
     */
    public function save(Plan $plan): void
    {
        $this->getDB()->tryFlatTransaction(function () use ($plan): void {
            $sql = 'DELETE FROM plugin_scaled_agile_plan WHERE program_increment_tracker_id = ?';

            $program_increment_tracker_id = $plan->getProgramIncrementTracker()->getId();
            $this->getDB()->run($sql, $program_increment_tracker_id);

            $insert = [];
            foreach ($plan->getPlannableTrackerIds() as $plannable_tracker_id) {
                $insert[] = [
                    'program_increment_tracker_id' => $program_increment_tracker_id,
                    'plannable_tracker_id'         => $plannable_tracker_id
                ];
            }

            $this->getDB()->insertMany('plugin_scaled_agile_plan', $insert);
        });
    }

    public function isPlannable(int $plannable_tracker_id): bool
    {
        $sql = 'SELECT count(*) FROM plugin_scaled_agile_plan WHERE plannable_tracker_id = ?';

        return $this->getDB()->exists($sql, $plannable_tracker_id);
    }

    public function isPartOfAPlan(TrackerData $tracker_data): bool
    {
        $sql = 'SELECT COUNT(*) FROM plugin_scaled_agile_plan WHERE plannable_tracker_id = ? OR program_increment_tracker_id = ?';

        return $this->getDB()->exists($sql, $tracker_data->getTrackerId(), $tracker_data->getTrackerId());
    }

    public function getProgramIncrementTrackerId(int $project_id): ?int
    {
        $sql = 'SELECT program_increment_tracker_id FROM plugin_scaled_agile_plan
                INNER JOIN tracker ON tracker.id = plugin_scaled_agile_plan.program_increment_tracker_id
                    WHERE tracker.group_id = ?';

        return $this->getDB()->single($sql, [$project_id]);
    }
}
