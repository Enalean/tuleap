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

require_once dirname(__FILE__) .'/../../../include/constants.php';
require_once dirname(__FILE__).'/../../../../tracker/include/constants.php';
require_once TRACKER_BASE_DIR .'/../tests/builders/aMockTracker.php';
require_once TRACKER_BASE_DIR .'/../tests/builders/aField.php';
require_once CARDWALL_BASE_DIR .'/OnTop/Config/TrackerMappingStatus.class.php';
require_once CARDWALL_BASE_DIR .'/OnTop/Config/ValueMapping.class.php';

class Cardwall_OnTop_Config_TrackerMappingTest extends TuleapTestCase {

    public function itReturnsAnEmptyLabelWhenThereIsNoValueMapping() {
        $value_mappings = array();
        $mapping = new Cardwall_OnTop_Config_TrackerMappingStatus(mock('Tracker'), array(), $value_mappings, aSelectBoxField()->build());
        $column = new Cardwall_OnTop_Config_Column(0, 'whatever', 'white', 'black');
        $this->assertEqual('', $mapping->getSelectedValueLabel($column));
    }

    public function itReturnsAnEmptyLabelWhenThereIsNoMappingForTheGivenColumn() {
        $value_todo       = new Tracker_FormElement_Field_List_Bind_StaticValue(101, 'Todo', '', 0, 0);
        $value_inprogress = new Tracker_FormElement_Field_List_Bind_StaticValue(102, 'In Progress', '', 0, 0);
        $value_done       = new Tracker_FormElement_Field_List_Bind_StaticValue(103, 'Done', '', 0, 0);
        
        $mapping_todo = mock('Cardwall_OnTop_Config_ValueMapping');
        stub($mapping_todo)->getValue()->returns($value_todo);
        stub($mapping_todo)->getColumnId()->returns(10);
        
        $mapping_ongoing = mock('Cardwall_OnTop_Config_ValueMapping');
        stub($mapping_ongoing)->getValue()->returns($value_inprogress);
        stub($mapping_ongoing)->getColumnId()->returns(11);
        
        $mapping_done = mock('Cardwall_OnTop_Config_ValueMapping');
        stub($mapping_done)->getValue()->returns($value_done);
        stub($mapping_done)->getColumnId()->returns(12);
        
        $value_mappings = array(
            101 => $mapping_todo,
            102 => $mapping_ongoing,
            103 => $mapping_done,
        );
        $mapping = new Cardwall_OnTop_Config_TrackerMappingStatus(mock('Tracker'), array(), $value_mappings, aSelectBoxField()->build());
        $column_which_match      = new Cardwall_OnTop_Config_Column(11, 'Ongoing', 'white', 'black');
        $column_which_dont_match = new Cardwall_OnTop_Config_Column(13, 'Ship It', 'white', 'black');
        $this->assertEqual('In Progress', $mapping->getSelectedValueLabel($column_which_match));
        $this->assertEqual('', $mapping->getSelectedValueLabel($column_which_dont_match));
        $this->assertEqual('Accept a default value', $mapping->getSelectedValueLabel($column_which_dont_match, 'Accept a default value'));
    }
}
?>
