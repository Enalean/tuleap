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
require_once CARDWALL_BASE_DIR .'/OnTop/Config/ColumnFactory.class.php';

class Cardwall_OnTop_Config_ColumnFactoryTest extends TuleapTestCase {

    public function setUp() {
        $values = array();
        foreach (array('new', 'verified', 'fixed') as $i => $value) {
            $$value = mock('Tracker_FormElement_Field_List_Bind_StaticValue');
            stub($$value)->getId()->returns(10 + $i);
            stub($$value)->getLabel()->returns(ucfirst($value));
            $values[] = $$value;
        }

        $this->status_field = aMockField()->build();
        stub($this->status_field)->getVisibleValuesPlusNoneIfAny()->returns($values);

        $this->tracker      = aMockTracker()->withId(42)->build();
        $this->swimline_tracker = aMockTracker()->build();
        $this->dao          = mock('Cardwall_OnTop_ColumnDao');
        $this->on_top_dao   = mock('Cardwall_OnTop_Dao');
        $this->factory      = new Cardwall_OnTop_Config_ColumnFactory($this->dao, $this->on_top_dao);
    }

    public function itBuildColumnsFromTheDataStorageIfFreestyleEnabledEvenIfStatus() {
        stub($this->on_top_dao)->isFreestyleEnabled()->returns(true);
        stub($this->tracker)->getStatusField()->returns($this->status_field);
        stub($this->dao)->searchColumnsByTrackerId(42)->returns(TestHelper::arrayToDar(
            array(
                'id'       => 1,
                'label'    => 'Todo',
                'bg_red'   => '123',
                'bg_green' => '12',
                'bg_blue'  => '10',
            ),
            array(
                'id'    => 2,
                'label' => 'On Going',
                'bg_red'   => null,
                'bg_green' => null,
                'bg_blue'  => null,
            )
        ));
        $columns = $this->factory->getDashboardColumns($this->tracker, $this->swimline_tracker);

        $this->assertIsA($columns, 'Cardwall_OnTop_Config_ColumnFreestyleCollection');
        $this->assertEqual(2, count($columns));
        $this->assertEqual("On Going", $columns[1]->getLabel());
        $this->assertEqual("rgb(123, 12, 10)", $columns[0]->getBgcolor());
        $this->assertEqual("white", $columns[0]->getFgcolor());
        $this->assertEqual("white", $columns[1]->getBgcolor());
        $this->assertEqual("black", $columns[1]->getFgcolor());
    }

    public function itBuildColumnsFromTheStatusValuesIfFreestyleNotEnabled() {
        stub($this->on_top_dao)->isFreestyleEnabled()->returns(false);
        stub($this->swimline_tracker)->getStatusField()->returns($this->status_field);
        stub($this->dao)->searchColumnsByTrackerId(42)->returns(TestHelper::arrayToDar());
        $columns = $this->factory->getDashboardColumns($this->tracker, $this->swimline_tracker);

        $this->assertIsA($columns, 'Cardwall_OnTop_Config_ColumnStatusCollection');
        $this->assertEqual(3, count($columns));
        $this->assertEqual("Verified", $columns[1]->getLabel());
    }

    public function itBuildsAnEmptyFreestyleCollectionIfThereIsNothingInTheDaoAndNoStatus() {
        stub($this->tracker)->getStatusField()->returns(null);
        stub($this->dao)->searchColumnsByTrackerId(42)->returns(TestHelper::arrayToDar());
        $columns = $this->factory->getDashboardColumns($this->tracker, $this->swimline_tracker);

        $this->assertIsA($columns, 'Cardwall_OnTop_Config_ColumnFreestyleCollection');
        $this->assertEqual(0, count($columns));
    }
}
?>
