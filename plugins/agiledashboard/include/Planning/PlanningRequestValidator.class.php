<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'PlanningFactory.class.php';
require_once 'common/valid/ValidFactory.class.php';
require_once 'common/include/Codendi_Request.class.php';

/**
 * Validates planning creation requests.
 */
class Planning_RequestValidator {
    
    /**
     * @var PlanningFactory
     */
    private $factory;
    
    /**
     * Creates a new validator instance.
     * 
     * @param PlanningFactory $factory Used to retrieve existing planning trackers for validation purpose.
     */
    public function __construct(PlanningFactory $factory) {
        $this->factory = $factory;
    }
    
    /**
     * Returns true when the $request contains sufficent data to create a valid
     * Planning.
     * 
     * Existing planning update validation is not implemented yet.
     * 
     * @param Codendi_Request $request
     * 
     * @return bool
     */
    public function isValid(Codendi_Request $request) {
        return $this->nameIsPresent($request)
            && $this->atLeastOneBacklogTrackerIdIsPresentAndAllIdsArePositiveIntegers($request)
            && $this->planningTrackerIdIsPresentAndIsAPositiveInteger($request)
            && $this->planningTrackerIsNotAlreadyUsedAsAPlanningTrackerInTheProject($request);
    }
    
    /**
     * Checks whether a name is present in the request.
     * 
     * @param Codendi_Request $request The validated request.
     * 
     * @return bool
     */
    private function nameIsPresent(Codendi_Request $request) {
        $name = new Valid_String('planning_name');
        $name->required();
        
        return $request->valid($name);
    }
    
    /**
     * Checks whether at least one backlog tracker id is present in the request,
     * and is a valid positive integer.
     * 
     * @param Codendi_Request $request The validated request.
     * 
     * @return bool
     */
    private function atLeastOneBacklogTrackerIdIsPresentAndAllIdsArePositiveIntegers(Codendi_Request $request) {
        $backlog_tracker_ids = new Valid_UInt('backlog_tracker_ids');
        $backlog_tracker_ids->required();
        
        return $request->validArray($backlog_tracker_ids);
    }
    
    /**
     * Checks whether a planning tracker id is present in the request, and is
     * a valid positive integer.
     * 
     * @param Codendi_Request $request The validated request.
     * 
     * @return bool
     */
    private function planningTrackerIdIsPresentAndIsAPositiveInteger(Codendi_Request $request) {
        $planning_tracker_id = new Valid_UInt('planning_tracker_id');
        $planning_tracker_id->required();
        
        return $request->valid($planning_tracker_id);
    }
    
    /**
     * Checks whether the planning tracker id in the request points to a tracker
     * that is not already used as a planning tracker in the project identified
     * by the request group_id.
     * 
     * @param Codendi_Request $request The validated request.
     * 
     * @return bool
     */
    private function planningTrackerIsNotAlreadyUsedAsAPlanningTrackerInTheProject(Codendi_Request $request) {
        $group_id                     = $request->get('group_id');
        $planning_tracker_id          = $request->get('planning_tracker_id');
        $project_planning_tracker_ids = $this->factory->getPlanningTrackerIdsByGroupId($group_id);
        
        return ! in_array($planning_tracker_id, $project_planning_tracker_ids);
    }
}
?>
