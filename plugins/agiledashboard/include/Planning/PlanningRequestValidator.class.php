<?php
/**
 * Copyright (c) Enalean, 2012 - 2017. All Rights Reserved.
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

/**
 * Validates planning creation requests.
 */
class Planning_RequestValidator
{

    /**
     * @var PlanningFactory
     */
    private $factory;

    /**
     * Creates a new validator instance.
     *
     * @param PlanningFactory $factory Used to retrieve existing planning trackers for validation purpose.
     */
    public function __construct(PlanningFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Returns true when the $request contains sufficent data to create a valid
     * Planning.
     *
     * Existing planning update validation is not implemented yet.
     *
     *
     * @return bool
     */
    public function isValid(Codendi_Request $request)
    {
        $group_id            = (int) $request->get('group_id');
        $planning_id         = $request->get('planning_id');
        $planning_parameters = $request->get('planning');

        if (! $planning_parameters) {
            $planning_parameters = array();
        }

        $planning_parameters = PlanningParameters::fromArray($planning_parameters);

        return $this->nameIsPresent($planning_parameters)
            && $this->backlogTrackerIdsArePresentAndArePositiveIntegers($planning_parameters)
            && $this->planningTrackerIdIsPresentAndIsAPositiveInteger($planning_parameters)
            && $this->planningTrackerIsNotThePlanningTrackerOfAnotherPlanningInTheSameProject($group_id, $planning_id, $planning_parameters);
    }

    /**
     * Checks whether name is present in the parameters.
     *
     * @param PlanningParameters $planning_parameters The validated parameters.
     *
     * @return bool
     */
    private function nameIsPresent(PlanningParameters $planning_parameters)
    {
        $name = new Valid_String();
        $name->required();

        return $name->validate($planning_parameters->name);
    }

    /**
     * Checks whether backlog tracker id is present in the parameters, and is
     * a valid positive integer.
     *
     * @param PlanningParameters $planning_parameters The validated parameters.
     *
     * @return bool
     */
    private function backlogTrackerIdsArePresentAndArePositiveIntegers(PlanningParameters $planning_parameters)
    {
        $backlog_tracker_id = new Valid_UInt();
        $backlog_tracker_id->required();
        $are_present = count($planning_parameters->backlog_tracker_ids) > 0;
        $are_valid   = true;

        foreach ($planning_parameters->backlog_tracker_ids as $tracker_id) {
            $are_valid = $are_valid && $backlog_tracker_id->validate($tracker_id);
        }

        return $are_present && $are_valid;
    }

    /**
     * Checks whether a planning tracker id is present in the parameters, and is
     * a valid positive integer.
     *
     * @param PlanningParameters $planning_parameters The validated parameters.
     *
     * @return bool
     */
    private function planningTrackerIdIsPresentAndIsAPositiveInteger(PlanningParameters $planning_parameters)
    {
        $planning_tracker_id = new Valid_UInt();
        $planning_tracker_id->required();

        return $planning_tracker_id->validate($planning_parameters->planning_tracker_id);
    }

    /**
     * Checks whether the planning tracker id in the request points to a tracker
     * that is not already used as a planning tracker in another planning of the
     * project identified by the request group_id.
     *
     * @param int                $group_id            The group id to check the existing planning trackers against.
     * @param int                $planning_id         The id of the planning to be updated.
     * @param PlanningParameters $planning_parameters The validated parameters.
     *
     * @return bool
     */
    private function planningTrackerIsNotThePlanningTrackerOfAnotherPlanningInTheSameProject($group_id, $planning_id, PlanningParameters $planning_parameters)
    {
        return ($this->planningTrackerIsTheCurrentOne($planning_id, $planning_parameters) ||
                $this->trackerIsNotAlreadyUsedAsAPlanningTrackerInProject($group_id, $planning_parameters));
    }

    /**
     * Checks the tracker planning id in $planning_parameters is the same as the one of the planning with the
     * given $planning_id.
     *
     * @param int                $planning_id         The planning with the current planning tracker id
     * @param PlanningParameters $planning_parameters The parameters being validated
     *
     * @return bool
     */
    private function planningTrackerIsTheCurrentOne($planning_id, PlanningParameters $planning_parameters)
    {
        $planning = $this->factory->getPlanning($planning_id);

        if (! $planning) {
            return false;
        }

        $current_planning_tracker_id = $planning->getPlanningTrackerId();
        $new_planning_tracker_id     = $planning_parameters->planning_tracker_id;

        return ($new_planning_tracker_id == $current_planning_tracker_id);
    }

    /**
     * Checks the tracker planning id in $planning_parameters is not already used as a planning tracker in one of the
     * plannings of the project with given $group_id.
     *
     * @param int                $group_id            The project where to search for existing planning trackers
     * @param PlanningParameters $planning_parameters The parameters being validated
     *
     * @return bool
     */
    private function trackerIsNotAlreadyUsedAsAPlanningTrackerInProject($group_id, PlanningParameters $planning_parameters)
    {
        $planning_tracker_id          = $planning_parameters->planning_tracker_id;
        $project_planning_tracker_ids = $this->factory->getPlanningTrackerIdsByGroupId($group_id);

        return ! in_array($planning_tracker_id, $project_planning_tracker_ids);
    }
}
