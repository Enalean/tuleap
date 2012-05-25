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

require_once dirname(__FILE__).'/../../include/Planning/Milestone.class.php';

function aMilestone() {
    return new Test_Planning_MilestoneBuilder();
}

class Test_Planning_MilestoneBuilder {
    
    /**
     * @var int
     */
    private $group_id;
    
    /**
     * @var Planning
     */
    private $planning;
    
    /**
     * @var Tracker_Artifact
     */
    private $artifact;
    
    /**
     * @var array of Planning_Milestone
     */
    private $sub_milestones;
    
    public function __construct() {
        $this->group_id          = 123;
        $this->planning          = aPlanning()->build();
        $this->sub_milestones    = array();
    }
    
    public function withinTheSameProjectAs(Planning_Milestone $other_milestone) {
        $this->group_id = $other_milestone->getGroupId();
        return $this;
    }
    
    public function withArtifact(Tracker_Artifact $artifact) {
        $this->artifact = $artifact;
        return $this;
    }
    
    public function withGroupId($group_id) {
        $this->group_id = $group_id;
        return $this;
    }
    
    public function withPlanningId($planning_id) {
        $this->withPlanning(aPlanning()->withId($planning_id)->build());
        return $this;
    }
    
    public function withXRef($xref) {
        $this->artifact->withXRef($xref);
    }
    
    public function withPlanning(Planning $planning) {
        $this->planning = $planning;
        return $this;
    }
    
    public function withSubMilestones(array $sub_milestones) {
        $this->sub_milestones = $sub_milestones;
        return $this;
    }
    
    public function build() {
        $milestone = new Planning_Milestone($this->group_id,
                                            $this->planning,
                                            $this->artifact);
        $milestone->addSubMilestones($this->sub_milestones);
        return $milestone;
    }
}
?>
