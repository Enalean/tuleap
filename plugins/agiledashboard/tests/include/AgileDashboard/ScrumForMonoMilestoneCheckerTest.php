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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\AgileDashboard;

require_once dirname(__FILE__) . '/../../bootstrap.php';

use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use TuleapTestCase;

class ScrumForMonoMilestoneCheckerTest extends TuleapTestCase
{
    /**
     * @var \PlanningFactory
     */
    private $planning_factory;

    /**
     * @var \PFUser
     */
    private $user;

    /**
     * @var ScrumForMonoMilestoneChecker
     */
    private $scrum_mono_milestone_checker;

    /**
     * @var Tuleap\AgileDashboard\ScrumForMonoMilestoneDao
     */
    private $scrum_mono_milestone_dao;

    public function setUp()
    {
        $this->user                     = mock('PFUser');
        $this->scrum_mono_milestone_dao = mock('Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDao');
        $this->planning_factory               = mock('PlanningFactory');

        $this->scrum_mono_milestone_checker = new ScrumForMonoMilestoneChecker(
            $this->scrum_mono_milestone_dao,
            $this->planning_factory
        );
    }

    public function itReturnsTrueWhenConfigurationIsInScrumV1()
    {
        stub($this->scrum_mono_milestone_dao)->isMonoMilestoneActivatedForProject()->returns(false);

        $this->assertTrue(
            $this->scrum_mono_milestone_checker->doesScrumMonoMilestoneConfigurationAllowsPlanningCreation(
                $this->user,
                101
            )
        );
    }

    public function itReturnsFalseWhenOnePlanningIsDefinedAndConfigurationAllowsMonoMilestone()
    {
        stub($this->scrum_mono_milestone_dao)->isMonoMilestoneActivatedForProject()->returns(array(101));
        stub($this->planning_factory)->getPlannings()->returns(array(1));

        $this->assertFalse(
            $this->scrum_mono_milestone_checker->doesScrumMonoMilestoneConfigurationAllowsPlanningCreation(
                $this->user,
                101
            )
        );
    }

    public function itReturnsFalseWhenTwoPlanningsAreDefinedAndConfigurationAllowsMonoMilestone()
    {
        stub($this->scrum_mono_milestone_dao)->isMonoMilestoneActivatedForProject()->returns(array(101));
        stub($this->planning_factory)->getPlannings()->returns(array(1,2));

        $this->assertFalse(
            $this->scrum_mono_milestone_checker->doesScrumMonoMilestoneConfigurationAllowsPlanningCreation(
                $this->user,
                101
            )
        );
    }

    public function itAlwaysReturnsTrueWhenMonoMilestoneIsDefinedInDb()
    {
        stub($this->scrum_mono_milestone_dao)->isMonoMilestoneActivatedForProject()->returns(array(101));

        $this->assertTrue(
            $this->scrum_mono_milestone_checker->isScrumMonoMilestoneAvailable(
                $this->user,
                101
            )
        );
    }

    public function itReturnsTrueWhenOnePlanningIsDefinedAndUserIsInLabMode()
    {
        stub($this->scrum_mono_milestone_dao)->isMonoMilestoneActivatedForProject()->returns(false);
        stub($this->planning_factory)->getPlannings()->returns(array(1));
        stub($this->user)->useLabFeatures()->returns(true);

        $this->assertTrue(
            $this->scrum_mono_milestone_checker->isScrumMonoMilestoneAvailable(
                $this->user,
                101
            )
        );
    }

    public function itReturnsFalseWhenOnePlanningIsDefinedAndUserIsNotInLabMode()
    {
        stub($this->scrum_mono_milestone_dao)->isMonoMilestoneActivatedForProject()->returns(false);
        stub($this->planning_factory)->getPlannings()->returns(array(1));
        stub($this->user)->useLabFeatures()->returns(false);

        $this->assertFalse(
            $this->scrum_mono_milestone_checker->isScrumMonoMilestoneAvailable(
                $this->user,
                101
            )
        );
    }
}
