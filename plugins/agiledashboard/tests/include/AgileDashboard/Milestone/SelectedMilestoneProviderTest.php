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
require_once dirname(__FILE__).'/../../../bootstrap.php';

class AgileDashboard_Milestone_SelectedMilestoneProviderTest extends TuleapTestCase
{

    public const FIELD_NAME = AgileDashboard_Milestone_MilestoneReportCriterionProvider::FIELD_NAME;
    public const ANY        = AgileDashboard_Milestone_MilestoneReportCriterionProvider::ANY;

    public function setUp()
    {
        parent::setUp();

        $this->artifact_id = 123;
        $this->artifact    = anArtifact()->withId($this->artifact_id)->build();
        $this->milestone   = aMilestone()->withArtifact($this->artifact)->build();

        $this->user    = aUser()->build();
        $this->project = mock('Project');

        $this->milestone_factory = stub('Planning_MilestoneFactory')->getBareMilestoneByArtifactId($this->user, $this->artifact_id)->returns($this->milestone);
    }

    public function itReturnsTheIdOfTheMilestone()
    {
        $additional_criteria = array(
            self::FIELD_NAME => new Tracker_Report_AdditionalCriterion(self::FIELD_NAME, 123)
        );

        $provider = new AgileDashboard_Milestone_SelectedMilestoneProvider($additional_criteria, $this->milestone_factory, $this->user, $this->project);

        $this->assertEqual($provider->getMilestoneId(), 123);
    }

    public function itReturnsAnyWhenNoCriterion()
    {
        $additional_criteria = array();

        $provider = new AgileDashboard_Milestone_SelectedMilestoneProvider($additional_criteria, $this->milestone_factory, $this->user, $this->project);

        $this->assertEqual($provider->getMilestoneId(), AgileDashboard_Milestone_MilestoneReportCriterionProvider::ANY);
    }
}
