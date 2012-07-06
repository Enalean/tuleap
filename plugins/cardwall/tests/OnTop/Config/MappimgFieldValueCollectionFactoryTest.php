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

require_once dirname(__FILE__).'/../../../include/View/AdminView.class.php';
require_once dirname(__FILE__).'/../../../../tracker/tests/builders/aTracker.php';
require_once dirname(__FILE__).'/../../../../tracker/tests/builders/aField.php';

class Cardwall_OnTop_Config_MappimgFieldValueCollectionFactoryTest extends TuleapTestCase {

    function itCreatesAnEmptyCollectionIfNothingIsStoredInTheDatabase() {
        $dao             = mock('Cardwall_OnTop_ColumnMappingFieldValueDao');
        $element_factory = mock('Tracker_FormElementFactory');
        $tracker_id = 66;
        $factory    = new Cardwall_OnTop_Config_MappimgFieldValueCollectionFactory($dao, $element_factory);

        $collection = $factory->create(aTracker()->withId($tracker_id)->build());
        $this->assertEqual(0, count($collection));
    }
}
?>
