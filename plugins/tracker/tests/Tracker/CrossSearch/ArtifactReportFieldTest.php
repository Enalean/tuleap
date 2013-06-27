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
require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

Mock::generate('Tracker_Report_Criteria');
Mock::generate('Tracker');
Mock::generate('Tracker_Artifact');
Mock::generate('Tracker_FormElement_Field_ArtifactLink');
Mock::generate('Tracker_FormElementFactory');

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
    
    public function itDisplaysTheTitleOfTheTracker() {
        $this->tracker->setReturnValue('getName', 'Sprint');
        $this->tracker->setReturnValue('getId', 666);
        $artifactReportField = new Tracker_CrossSearch_ArtifactReportField($this->tracker, array());
        $markup              = $this->fetchCriteria($artifactReportField);
        $this->assertPattern('%Sprint%', $markup);
        $this->assertPattern('%666%',    $markup);
        
    }
 
    
    public function itReturnsTheArtifactLinkOfTheTracker() {
        $form_element_factory      = new MockTracker_FormElementFactory();
        $art_link_release_field_id = 135;
        $artifact_link_field_of_release_tracker = new MockTracker_FormElement_Field_ArtifactLink();
        $artifact_link_field_of_release_tracker->setReturnValue('getId', $art_link_release_field_id);
        $form_element_factory->setReturnValue('getUsedArtifactLinkFields', array($artifact_link_field_of_release_tracker), array($this->tracker));
        
        $artifact_report_field     = new Tracker_CrossSearch_ArtifactReportField($this->tracker, array());
        $database_field_key        = $artifact_report_field->getArtifactLinkFieldName($form_element_factory);
        
        $this->assertEqual($database_field_key, 'art_link_'.$art_link_release_field_id);
    }
    
    public function itReturnsNoArtifactLinkFieldNameWhenTrackerHasNoArtifact() {
        $form_element_factory = stub('Tracker_FormElementFactory')->getUsedArtifactLinkFields($this->tracker)->returns(array());
        $artifact_report_field = new Tracker_CrossSearch_ArtifactReportField($this->tracker, array());
        $this->assertNull($artifact_report_field->getArtifactLinkFieldName($form_element_factory));
    }
    
    public function ItDisplaysJustTheOptions_Any_IfThereAreNoArtifactsGiven() {
        $artifactReportField = new Tracker_CrossSearch_ArtifactReportField($this->tracker, array());
        $markup              = $this->fetchCriteria($artifactReportField);
        $this->assertPattern('%value="">Any%', $markup);
//         $this->assertPattern('%value="100">None%', $markup);
    }
    
    public function ItDisplaysASelectMultipleWithAllArtifactsOfTheCorrespondingTracker() {
        $artifact            = stub('Tracker_Artifact')->getId()->returns(123);
        $artifact            = stub($artifact)->getTitle()->returns('artifact 123');
        $artifact->isSelected= true;
        
        $artifactReportField = new Tracker_CrossSearch_ArtifactReportField($this->tracker, array($artifact));
        $markup              = $this->fetchCriteria($artifactReportField);
        
        $this->assertPattern('%value="123">artifact 123%', $markup);
    }
    
    private function fetchCriteria($artifactReportField) {
        $criteria = new MockTracker_Report_Criteria();
        return $artifactReportField->fetchCriteria($criteria);
    }
}
?>