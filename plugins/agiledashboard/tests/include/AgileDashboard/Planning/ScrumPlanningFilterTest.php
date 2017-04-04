<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning;

require_once dirname(__FILE__) . '/../../../bootstrap.php';

class ScrumPlanningFilterTest extends \TuleapTestCase
{
    /**
     * @var \PFUser
     */
    private $user;

    /**
     * @var \Planning
     */
    private $planning;

    /**
     * @var \PlanningFactory
     */
    private $planning_factory;

    /**
     * @var Tuleap\AgileDashboard\ScrumForMonoMilestoneChecker
     */
    private $mono_milestone_checker;

    /**
     * @var  ScrumPlanningFilter
     */
    private $scrum_planning_filter;

    public function setUp()
    {
        parent::setUp();

        $this->mono_milestone_checker = mock('Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker');
        $this->planning_factory       = mock('PlanningFactory');
        $this->planning               = mock('Planning');
        $this->user                   = mock('PFUser');

        $this->scrum_planning_filter  = new ScrumPlanningFilter(
            mock('AgileDashboard_HierarchyChecker'),
            $this->mono_milestone_checker,
            $this->planning_factory
        );
    }

    public function itRetrieveMonoMilestoneTrackerWhenScrumMonoMilestoneIsEnabled()
    {
        stub($this->mono_milestone_checker)->isMonoMilestoneEnabled(101)->returns(true);
        stub($this->planning_factory)->getAvailableBacklogTrackers()->returns(array());
        expect($this->planning_factory)->getAvailableBacklogTrackers()->once();

        $this->scrum_planning_filter->getPlanningTrackersFiltered(
            array(1, 2),
            $this->planning,
            $this->user,
            101
        );
    }

    public function itRetrieveMultiMilestoneTrackerWhenScrumMonoMilestoneIsDisabled()
    {
        stub($this->mono_milestone_checker)->isMonoMilestoneEnabled(101)->returns(false);
        expect($this->planning_factory)->getAvailablePlanningTrackers()->once();
        stub($this->planning_factory)->getAvailablePlanningTrackers()->returns(array(mock('Tracker')));

        $this->scrum_planning_filter->getPlanningTrackersFiltered(
            array(),
            $this->planning,
            $this->user,
            101
        );
    }
}
