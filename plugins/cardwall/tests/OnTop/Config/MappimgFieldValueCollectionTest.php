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
require_once dirname(__FILE__).'/../../../include/View/AdminView.class.php';
require_once dirname(__FILE__).'/../../../../tracker/tests/builders/aTracker.php';
require_once dirname(__FILE__).'/../../../../tracker/tests/builders/aField.php';

class Cardwall_OnTop_Config_MappimgFieldValueCollectionTest extends TuleapTestCase {
    
    function itReturnsTrusIfTheCollectionContainsAMapping() {
        $tracker = aTracker()->build();
        $field   = aSelectBoxField()->withId(42)->build();
        $mapping_value_1 = new Cardwall_OnTop_Config_MappimgFieldValue($tracker, $field, 101, 1);
        $mapping_value_2 = new Cardwall_OnTop_Config_MappimgFieldValue($tracker, $field, 102, 2);
        $mapping_value_3 = new Cardwall_OnTop_Config_MappimgFieldValue($tracker, $field, 103, 2);
        $collection = new Cardwall_OnTop_Config_MappimgFieldValueCollection();
        $collection->add($mapping_value_1);
        $collection->add($mapping_value_2);
        $collection->add($mapping_value_3);
        
        $this->assertTrue($collection->has($field, 101, 1));
        $this->assertTrue($collection->has($field, 102, 2));
        $this->assertFalse($collection->has($field, 103, 3));
    }
}
?>
