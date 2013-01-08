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
Mock::generate('Tracker_CrossSearch_SemanticValueFactory');

class Tracker_CrossSearch_SemanticStatusReportFieldTest extends TuleapTestCase {
    public function setUp() {
        parent::setUp();
        
        $this->status                 = 'open';
        $this->semantic_value_factory = new MockTracker_CrossSearch_SemanticValueFactory();
        $this->field                  = new Tracker_CrossSearch_SemanticStatusReportField($this->status, $this->semantic_value_factory);
        
        $this->setText('Status', array('plugin_tracker_crosssearch', 'semantic_status_label'));
        $this->setText('Any',    array('plugin_tracker_crosssearch', 'semantic_status_any'));
        $this->setText('Open',   array('plugin_tracker_crosssearch', 'semantic_status_open'));
        $this->setText('Closed', array('plugin_tracker_crosssearch', 'semantic_status_closed'));
    }
    
    public function itHasAnId() {
        $this->assertNotBlank($this->field->getId());
    }
    
    public function itHasALabel() {
        $this->assertNotBlank($this->field->getLabel());
    }
    
    public function itCanRenderASearchCriteria() {
        $criteria = new MockTracker_Report_Criteria();
        $html     = $this->field->fetchCriteria($criteria);
        
        $this->assertPattern('%<select.*Open.*</select>%s', $html);
        $this->assertPattern('%<select.*Any.*</select>%s', $html);
        $this->assertPattern('%<select.*Closed.*</select>%s', $html);
    }
    
    public function testTheCriteriaIncludesTheLabel() {
        $criteria = new MockTracker_Report_Criteria();
        $html     = $this->field->fetchCriteria($criteria);
        
        $this->assertPattern('%<label.*Status.*</label>%s', $html);
    }
    
    public function itUsesTheCriteriaToDeduceWichOptionsAreSelected() {
        $this->assertOptionIsSelected('Open');
        $this->assertOptionIsSelected('Closed');
        $this->assertOptionIsSelected('Any');
    }

    public function testNoOptionIsSelectedIfThereIsNoStatusCriteria() {
        $this->assertNoPattern("%selected=\"selected\">%", $this->fetchCriteria(null));
        $this->assertNoPattern("%selected=\"selected\">%", $this->fetchCriteria('non supported criteria'));
    }
    
    private function assertOptionIsSelected($option) {
        $html = $this->fetchCriteria(strtolower($option));
        
        $this->assertPattern("%selected=\"selected\">$option%", $html);
    }

    private function fetchCriteria($option) {
        $criteria = new MockTracker_Report_Criteria();
        $field    = new Tracker_CrossSearch_SemanticStatusReportField($option, $this->semantic_value_factory);
        
        return $field->fetchCriteria($criteria);
    }    
}
?>
