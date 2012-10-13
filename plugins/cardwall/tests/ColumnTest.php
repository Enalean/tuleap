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

class Cardwall_Column_isInColumnTest extends TuleapTestCase {
    
    
    //TODO move this to the configTest file
    public function setUp() {
        parent::setUp();
        $tracker = aMockTracker()->withId(33)->build();
        $swimline_tracker = aMockTracker()->build();
        $this->artifact = stub('Tracker_Artifact')->getTracker()->returns($tracker);
        $this->field = mock('Tracker_FormElement_Field_MultiSelectbox');
        $this->field_provider = stub('Cardwall_FieldProviders_IProvideFieldGivenAnArtifact')->getField($this->artifact)->returns($this->field);
        $dao = mock('Cardwall_OnTop_Dao');
        $column_factory = mock('Cardwall_OnTop_Config_ColumnFactory');
        $tracker_mapping_factory = mock('Cardwall_OnTop_Config_TrackerMappingFactory');
        $this->config = new Cardwall_OnTop_Config($tracker, $swimline_tracker, $dao, $column_factory, $tracker_mapping_factory);
    }
    
    public function itIsInTheCellIfTheLabelMatches() {
        stub($this->field)->getFirstValueFor($this->artifact->getLastChangeset())->returns('ongoing');
        $column   = $this->newCardwall_Column(0, 'ongoing');
        $this->assertIn($column);
    }
    
    public function itIsNotInTheCellIfTheLabelDoesntMatch() {
        stub($this->field)->getFirstValueFor($this->artifact->getLastChangeset())->returns('ongoing');
        $column   = $this->newCardwall_Column(0, 'done');
        $this->assertNotIn($column);
    }

    public function itIsInTheCellIfItHasNoStatusAndTheColumnHasId100() {
        $null_status = null;
        stub($this->field)->getFirstValueFor($this->artifact->getLastChangeset())->returns($null_status);
        $column   = $this->newCardwall_Column(100, 'done');
        $this->assertIn($column);
    }

    public function itIsNotInTheCellIfItHasNoStatus() {
        $null_status = null;
        stub($this->field)->getFirstValueFor($this->artifact->getLastChangeset())->returns($null_status);
        $column   = $this->newCardwall_Column(123, 'done');
        $this->assertNotIn($column);
    }

    public function itIsNotInTheCellIfHasANonMatchingLabelTheColumnIdIs100() {
        stub($this->field)->getFirstValueFor($this->artifact->getLastChangeset())->returns('ongoing');
        $column   = $this->newCardwall_Column(100, 'done');
        $this->assertNotIn($column);
    }

    private function assertIn($column) {
         $this->assertTrue($this->config->isInColumn($this->artifact, $this->field_provider, $column));
    }
    
    private function assertNotIn($column) {
         $this->assertFalse($this->config->isInColumn($this->artifact, $this->field_provider, $column));
    }

    public function newCardwall_Column($id, $label) {
        $bgcolor = $fgcolor = 0;
        return new Cardwall_Column($id, $label, $bgcolor, $fgcolor);
    }
    
}

?>
