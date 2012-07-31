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

$current_dir = dirname(__FILE__);

require_once $current_dir.'/../../include/constants.php';
require_once $current_dir.'/../../../tracker/include/constants.php';
require_once AGILEDASHBOARD_BASE_DIR .'/Planning/MilestoneSelectorController.class.php';
require_once $current_dir.'/../builders/aMilestone.php';

class Planning_MilestoneSelectorControllerTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->planning_id       = '321';
        $this->user              = aUser()->withId(12)->build();
        $this->request                 = aRequest()->with('planning_id', $this->planning_id)->withUser($this->user)->build();
        $this->milestone_factory = mock('Planning_MilestoneFactory');

        $this->current_milestone_artifact_id = 444;

        $milestone = aMilestone()->withArtifact(anArtifact()->withId($this->current_milestone_artifact_id)->build())->build();
        stub($this->milestone_factory)->getCurrentMilestone($this->user, $this->planning_id)->returns($milestone);
    }

    public function tearDown() {
        parent::tearDown();
        EventManager::clearInstance();
    }

    function itRedirectToTheCurrentMilestone() {
        $GLOBALS['Response']->expectOnce('redirect', array(new PatternExpectation("/aid=$this->current_milestone_artifact_id/")));
        $controller = new Planning_MilestoneSelectorController($this->request, $this->milestone_factory);
        $controller->show();
    }

    function itRedirectToTheCurrentMilestoneCardwallIfAny() {
        $event_manager = mock('EventManager');
        EventManager::setInstance($event_manager);

        $event_manager->expectOnce(
            'processEvent',
            array(
                AGILEDASHBOARD_EVENT_MILESTONE_SELECTOR_REDIRECT,
                '*'
        ));
        $controller = new Planning_MilestoneSelectorController($this->request, $this->milestone_factory);
        $controller->show();
    }

    function itDoesntRedirectIfNoMilestone() {
        $milestone_factory = mock('Planning_MilestoneFactory');
        stub($milestone_factory)->getCurrentMilestone()->returns(mock('Planning_NoMilestone'));

        $GLOBALS['Response']->expectNever('redirect');
        $controller = new Planning_MilestoneSelectorController($this->request, $milestone_factory);
        $controller->show();
    }

}

?>
