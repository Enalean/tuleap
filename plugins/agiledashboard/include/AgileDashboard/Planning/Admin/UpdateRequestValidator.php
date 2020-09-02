<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning\Admin;

class UpdateRequestValidator
{
    /**
     * @param int[] $unavailable_planning_tracker_ids
     */
    public function getValidatedPlanning(
        \Planning $original_planning,
        \Codendi_Request $request,
        array $unavailable_planning_tracker_ids,
        ?ModificationBan $milestone_tracker_modification_ban
    ): ?\PlanningParameters {
        $planning_from_request = $request->get('planning');
        if (! $planning_from_request) {
            return null;
        }
        $updated_planning = \PlanningParameters::fromArray($planning_from_request);

        $is_valid = $this->nameIsPresent($updated_planning)
            && $this->backlogTrackerIdsArePresentAndArePositiveIntegers($updated_planning)
            && $this->planningTrackerIsValid(
                $unavailable_planning_tracker_ids,
                $original_planning,
                $updated_planning,
                $milestone_tracker_modification_ban
            );
        return ($is_valid) ? $updated_planning : null;
    }

    private function nameIsPresent(\PlanningParameters $planning_parameters): bool
    {
        $valid_name = new \Valid_String();
        $valid_name->required();

        return $valid_name->validate($planning_parameters->name);
    }

    private function backlogTrackerIdsArePresentAndArePositiveIntegers(\PlanningParameters $planning_parameters): bool
    {
        $backlog_tracker_id = new \Valid_UInt();
        $backlog_tracker_id->required();
        $are_present = count($planning_parameters->backlog_tracker_ids) > 0;
        $are_valid   = true;

        foreach ($planning_parameters->backlog_tracker_ids as $tracker_id) {
            $are_valid = $are_valid && $backlog_tracker_id->validate($tracker_id);
        }

        return $are_present && $are_valid;
    }

    /**
     * @param int[] $unavailable_planning_tracker_ids
     */
    private function planningTrackerIsValid(
        array $unavailable_planning_tracker_ids,
        \Planning $original_planning,
        \PlanningParameters $updated_planning,
        ?ModificationBan $milestone_tracker_modification_ban
    ): bool {
        if ($milestone_tracker_modification_ban !== null) {
            $updated_planning->planning_tracker_id = (string) $original_planning->getPlanningTrackerId();
            return true;
        }

        if (! $this->planningTrackerIdIsPresentAndIsAPositiveInteger($updated_planning)) {
            return false;
        }

        return ($this->planningTrackerDidNotChange($original_planning, $updated_planning)
            || $this->planningTrackerIsNotUsedInAnotherPlanningInProject(
                $unavailable_planning_tracker_ids,
                $updated_planning
            ));
    }

    private function planningTrackerIdIsPresentAndIsAPositiveInteger(\PlanningParameters $planning_parameters): bool
    {
        $planning_tracker_id = new \Valid_UInt();
        $planning_tracker_id->required();

        return $planning_tracker_id->validate($planning_parameters->planning_tracker_id);
    }

    /**
     * @psalm-mutation-free
     */
    private function planningTrackerDidNotChange(
        \Planning $original_planning,
        \PlanningParameters $updated_planning
    ): bool {
        return (int) $original_planning->getPlanningTrackerId() === (int) $updated_planning->planning_tracker_id;
    }

    /**
     * @param int[] $unavailable_planning_tracker_ids
     * @psalm-mutation-free
     */
    private function planningTrackerIsNotUsedInAnotherPlanningInProject(
        array $unavailable_planning_tracker_ids,
        \PlanningParameters $planning_parameters
    ): bool {
        return ! in_array((int) $planning_parameters->planning_tracker_id, $unavailable_planning_tracker_ids, true);
    }
}
