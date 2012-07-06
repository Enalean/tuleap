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

    public function setUp() {
        $this->dao             = mock('Cardwall_OnTop_ColumnMappingFieldValueDao');
        $this->element_factory = mock('Tracker_FormElementFactory');
        $this->tracker_id      = 66;
        $this->tracker         = aTracker()->withId($this->tracker_id)->build();
        $this->factory         = new Cardwall_OnTop_Config_MappimgFieldValueCollectionFactory($this->dao, $this->element_factory);

        $status_field = aSelectboxField()->withId(121)->build();
        $stage_field  = aSelectboxField()->withId(122)->build();
        stub($this->element_factory)->getFieldById('121')->returns($status_field);
        stub($this->element_factory)->getFieldById('122')->returns($stage_field);
    }

    public function itCreatesAnEmptyCollectionIfNothingIsStoredInTheDatabase() {
        stub($this->dao)->searchMappingFieldValues($this->tracker_id)->returns(TestHelper::arrayToDar());
        $collection = $this->factory->create($this->tracker);
        $this->assertEqual(0, count($collection));
    }

    public function itCreatesACollectionFromTheDataStorage() {
        stub($this->dao)->searchMappingFieldValues($this->tracker_id)->returns(TestHelper::arrayToDar(
            array(
                'tracker_id'          => '1331',
                'field_id'            => '121',
                'value_id'            => '11',
                'column_id'           => '1',
            ),
            array(
                'tracker_id'          => '1332',
                'field_id'            => '122',
                'value_id'            => '12',
                'column_id'           => '1',
            )
        ));
        $collection = $this->factory->create($this->tracker);
        $this->assertEqual(2, count($collection));
    }
}
?>
