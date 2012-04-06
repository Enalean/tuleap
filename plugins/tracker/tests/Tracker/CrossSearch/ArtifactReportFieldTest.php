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

require_once dirname(__FILE__).'/../../../include/Tracker/Report/Tracker_Report_Criteria.class.php';

Mock::generate('Tracker_Report_Criteria');
Mock::generate('Tracker');

class Tracker_CrossSearch_ArtifactReportFieldTest extends TuleapTestCase {
    public function setUp() {
        parent::setUp();
        $this->tracker = new MockTracker();
    }
    
    public function itIsAlwaysUsed() {
        $artifactReportField = new Tracker_CrossSearch_ArtifactReportField($this->tracker, array());
        
        $this->assertTrue($artifactReportField->isUsed());
    }
    
    public function itLabelIsTheNameOfTheTracker() {
        $expected = 'Tracker Name';
        $this->tracker->setReturnValue('getName', $expected);
        
        $artifactReportField = new Tracker_CrossSearch_ArtifactReportField($this->tracker, array());
        
        $this->assertEqual($expected, $artifactReportField->getLabel());
    }
    
    public function itIdIsartifact_criteriaWithTrackerId() {
        $expectedId = '666';
        $expected   = 'artifact_of_tracker['.$expectedId.']';
        $this->tracker->setReturnValue('getId', $expectedId);
        
        $artifactReportField = new Tracker_CrossSearch_ArtifactReportField($this->tracker, array());
        
        $this->assertEqual($expected, $artifactReportField->getId());
    }
        
}
?>