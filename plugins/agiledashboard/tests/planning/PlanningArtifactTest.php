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

require_once 'PlanningItemTestCase.class.php';
require_once dirname(__FILE__).'/../../include/Planning/PlanningArtifact.class.php';
require_once dirname(__FILE__).'/../../../tracker/tests/builders/aMockArtifact.php';
require_once dirname(__FILE__).'/../../../tracker/tests/builders/aTracker.php';
require_once dirname(__FILE__).'/../builders/aPlanning.php';

class PlanningArtifactTest extends PlanningItemTestCase {
    
    public function setUp() {
        parent::setUp();
        
        $this->edit_uri = 'http://someurl';
        $this->xref     = 'some #xref';
        $this->title    = 'do something interresting';
        $this->id       = '234872';
        
        $this->planning_tracker_id = 123;
        $this->other_tracker_id    = 456;
        
        $this->planning = aPlanning()->withPlanningTrackerId($this->planning_tracker_id)
                                     ->build();
        
        $this->artifact = aMockArtifact()->withUri($this->edit_uri)
                                         ->withXRef($this->xref)
                                         ->withTitle($this->title)
                                         ->withId($this->id)
                                         ->build();
        
        $this->item = new PlanningArtifact($this->artifact, $this->planning);
    }
    
    public function itIsPlannifiableIfItsTrackerMatchesThePlanningOne() {
        stub($this->artifact)->getTrackerId()
                             ->returns($this->planning_tracker_id);
        
        $this->assertTrue($this->item->isPlannifiable());
    }
    
    public function itIsNotPlannifiableIfItsTrackerDoesNotMatchThePlanningOne() {
        stub($this->artifact)->getTrackerId()
                             ->returns($this->other_tracker_id);
        
        $this->assertFalse($this->item->isPlannifiable());
    }
}

?>
