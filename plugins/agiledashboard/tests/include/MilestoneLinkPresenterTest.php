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

require_once dirname(__FILE__).'/../../include/Planning/MilestoneLinkPresenter.class.php';
require_once dirname(__FILE__).'/../builders/aPlanning.php';
require_once dirname(__FILE__).'/../builders/aMilestone.php';
require_once dirname(__FILE__).'/../../../tracker/tests/builders/aMockArtifact.php';

class Planning_MilestoneLinkPresenterTest extends TuleapTestCase {
    
    public function setUp() {
        $artifact        = aMockArtifact()->withId(123)
                                          ->withTitle('Foo')
                                          ->withXRef('milestone #123')
                                          ->build();
        $project = stub('Project')->getID()->returns(456);
        $this->milestone = aMilestone()->withArtifact($artifact)
                                       ->withGroup($project)
                                       ->withPlanningId(789)
                                       ->build();
        $this->presenter = new Planning_MilestoneLinkPresenter($this->milestone);
    }
    
    public function itHasAnUriPointingToThePlanningViewOfTheMilestone() {
        $this->assertEqual($this->presenter->getUri(), '/plugins/agiledashboard/?group_id=456&action=show&planning_id=789&aid=123');
    }
    
    public function itHasAnXRefMatchingTheMilestoneUnderlyingArtifact() {
        $this->assertEqual($this->presenter->getXRef(), 'milestone #123');
    }
    
    public function itHasATitleMatchingTheUnderlyingArtifact() {
        $this->assertEqual($this->presenter->getTitle(), 'Foo');
    }
}
?>
