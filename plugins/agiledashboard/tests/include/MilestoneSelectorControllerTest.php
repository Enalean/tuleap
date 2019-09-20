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

require_once dirname(__FILE__).'/../bootstrap.php';

class Planning_MilestoneSelectorControllerTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        ForgeConfig::store();
        ForgeConfig::set('codendi_dir', AGILEDASHBOARD_BASE_DIR .'/../../..');
        $this->planning_id       = '321';
        $this->user              = aUser()->withId(12)->build();
        $this->request                 = aRequest()->with('planning_id', $this->planning_id)->withUser($this->user)->build();
        $this->milestone_factory = mock('Planning_MilestoneFactory');

        $this->current_milestone_artifact_id = 444;

        $milestone = aMilestone()->withArtifact(anArtifact()->withId($this->current_milestone_artifact_id)->build())->build();
        stub($this->milestone_factory)->getLastMilestoneCreated($this->user, $this->planning_id)->returns($milestone);
    }

    public function tearDown()
    {
        EventManager::clearInstance();
        ForgeConfig::restore();
        parent::tearDown();
    }

    function itRedirectToTheCurrentMilestone()
    {
        $GLOBALS['Response']->expectOnce('redirect', array(new PatternExpectation("/aid=$this->current_milestone_artifact_id/")));
        $controller = new Planning_MilestoneSelectorController($this->request, $this->milestone_factory);
        $controller->show();
    }

    function itRedirectToTheCurrentMilestoneCardwallIfAny()
    {
        $event_manager = \Mockery::mock(\EventManager::class);
        EventManager::setInstance($event_manager);

        $event_manager->shouldReceive('processEvent')->with(AGILEDASHBOARD_EVENT_MILESTONE_SELECTOR_REDIRECT, \Mockery::any());

        $controller = new Planning_MilestoneSelectorController($this->request, $this->milestone_factory);
        $controller->show();
    }

    function itDoesntRedirectIfNoMilestone()
    {
        $milestone_factory = mock('Planning_MilestoneFactory');
        stub($milestone_factory)->getLastMilestoneCreated()->returns(mock('Planning_NoMilestone'));

        $GLOBALS['Response']->expectNever('redirect');
        $controller = new Planning_MilestoneSelectorController($this->request, $milestone_factory);
        $controller->show();
    }
}
