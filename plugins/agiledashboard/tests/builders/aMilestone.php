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

require_once dirname(__FILE__).'/../../include/Planning/ArtifactMilestone.class.php';
require_once dirname(__FILE__).'/../../../tracker/tests/builders/anArtifact.php';
require_once 'aPlanning.php';

function aMilestone()
{
    return new Test_Planning_MilestoneBuilder();
}

class Test_Planning_MilestoneBuilder
{
    /**
     * @var \Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker
     */
    private $scrum_mono_milestone_checker;

    /**
     * @var Project
     */
    private $project;

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

    public function __construct()
    {
        $this->project                      = mock('Project');
        $this->planning                     = aPlanning()->build();
        $this->sub_milestones               = array();
        $this->artifact                     = anArtifact()->build();
        $this->scrum_mono_milestone_checker = mock('\Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker');
    }

    public function withinTheSameProjectAs(Planning_Milestone $other_milestone)
    {
        $this->project = $other_milestone->getProject();
        return $this;
    }

    public function withArtifact(Tracker_Artifact $artifact)
    {
        $this->artifact = $artifact;
        return $this;
    }

    public function withGroup($project)
    {
        $this->project = $project;
        return $this;
    }

    public function withPlanningId($planning_id)
    {
        $this->withPlanning(aPlanning()->withId($planning_id)->build());
        return $this;
    }

    public function withXRef($xref)
    {
        $this->artifact->withXRef($xref);
    }

    public function withPlanning(Planning $planning)
    {
        $this->planning = $planning;
        return $this;
    }

    public function build()
    {
        $milestone = new Planning_ArtifactMilestone(
            $this->project,
            $this->planning,
            $this->artifact,
            $this->scrum_mono_milestone_checker
        );

        return $milestone;
    }
}
