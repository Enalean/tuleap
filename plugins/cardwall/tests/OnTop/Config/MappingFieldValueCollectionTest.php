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
require_once CARDWALL_BASE_DIR .'/OnTop/Config/MappingFieldValueCollection.class.php';
require_once TRACKER_BASE_DIR .'/../tests/builders/aTracker.php';
require_once TRACKER_BASE_DIR .'/../tests/builders/aField.php';

class Cardwall_OnTop_Config_MappingFieldValueCollectionTest extends TuleapTestCase {

    public function setUp() {
        $this->tracker       = aTracker()->withId(66)->build();
        $this->field         = aSelectBoxField()->withId(42)->build();
        $this->story_tracker = aTracker()->withId(10)->build();
        $this->bugs_tracker  = aTracker()->withId(11)->build();
        $this->bugs_field    = aSelectBoxField()->withId(43)->build();

        $this->mapping_value_1 = new Cardwall_OnTop_Config_MappingFieldValue($this->tracker, $this->field, 101, 1);
        $this->mapping_value_2 = new Cardwall_OnTop_Config_MappingFieldValue($this->tracker, $this->field, 102, 2);
        $this->mapping_value_3 = new Cardwall_OnTop_Config_MappingFieldValue($this->tracker, $this->field, 103, 2);
        $this->mapping_value_4 = new Cardwall_OnTop_Config_MappingFieldValue($this->story_tracker, $this->bugs_field, 104, 3);

        $this->collection = new Cardwall_OnTop_Config_MappingFieldValueCollection();
        $this->collection->add($this->mapping_value_1);
        $this->collection->add($this->mapping_value_2);
        $this->collection->add($this->mapping_value_3);
        $this->collection->add($this->mapping_value_4);
    }

    function itReturnsTrueIfTheCollectionContainsAMapping() {
        $this->assertTrue($this->collection->has($this->tracker,$this->field, 101, 1));
        $this->assertTrue($this->collection->has($this->tracker,$this->field, 102, 2));
        $this->assertFalse($this->collection->has($this->tracker,$this->field, 103, 3));
    }

    function itReturnsTheMappingsOfATracker() {
        $expected = array(
            $this->mapping_value_1,
            $this->mapping_value_2,
            $this->mapping_value_3
        );
        $this->assertEqual($expected, $this->collection->getForTracker($this->tracker));
        $this->assertFalse($this->collection->getForTracker($this->bugs_tracker));
    }
}
?>
