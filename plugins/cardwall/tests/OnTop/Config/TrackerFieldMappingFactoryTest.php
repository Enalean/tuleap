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
require_once TRACKER_BASE_DIR .'/../tests/builders/aTracker.php';
require_once TRACKER_BASE_DIR .'/../tests/builders/aField.php';
require_once CARDWALL_BASE_DIR .'/OnTop/Config/TrackerFieldMappingFactory.class.php';
require_once CARDWALL_BASE_DIR .'/OnTop/Config/TrackerFieldMapping.class.php';

class Cardwall_OnTop_Config_TrackerFieldMappingFactory_newMappingTest extends TuleapTestCase {
        
    public function itReturnsAMappingWithFieldTrackerAndAvailableFields() {
        $tracker         = aTracker()->withId(3)->build();
        $field = aSelectBoxField()->build();
        $element_factory = stub('Tracker_FormElementFactory')->getFieldById(123)->returns($field);
        $fields = array(
                    aSelectBoxField()->build(),
                    aSelectBoxField()->build());
        stub($element_factory)->getUsedSbFields($tracker)->returns($fields);
        
        $expected_mapping = new Cardwall_OnTop_Config_TrackerFieldMapping($tracker, $field, $fields);
        
        $factory = new Cardwall_OnTop_Config_TrackerFieldMappingFactory($element_factory);
        $actual_mapping = $factory->newMapping($tracker, 123);
        
        $this->assertEqual($expected_mapping, $actual_mapping);
    }
    
}

?>