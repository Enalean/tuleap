<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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

require_once dirname(__FILE__) .'/../../bootstrap.php';

class Cardwall_OnTop_Config_TrackerMappingFactoryTest extends TuleapTestCase
{

    public function setUp()
    {

        $this->field_122    = aSelectBoxField()->withId(122)->build();
        $this->field_123    = aSelectBoxField()->withId(123)->build();
        $this->field_124    = aSelectBoxField()->withId(124)->build();
        $this->status_field = aSelectBoxField()->withId(125)->build();

        $group_id           = 234;
        $this->tracker      = aMockTracker()->withId(3)->withProjectId($group_id)->build();
        $this->tracker_10   = aMockTracker()->withId(10)->withStatusField($this->status_field)->build();
        $this->tracker_20   = aMockTracker()->withId(20)->build();
        $project_trackers = array(
            3  => $this->tracker,
            10 => $this->tracker_10,
            20 => $this->tracker_20
        );

        $tracker_factory = stub('TrackerFactory')->getTrackersByGroupId($group_id)->returns($project_trackers);
        foreach ($project_trackers as $t) {
            stub($tracker_factory)->getTrackerById($t->getId())->returns($t);
        }

        $element_factory = mock('Tracker_FormElementFactory');
        stub($element_factory)->getFieldById(123)->returns($this->field_123);
        stub($element_factory)->getFieldById(124)->returns($this->field_124);
        stub($element_factory)->getUsedSbFields($this->tracker_10)->returns(
            array($this->field_122, $this->field_123)
        );
        stub($element_factory)->getUsedSbFields()->returns(array());

        $this->dao                   = mock('Cardwall_OnTop_ColumnMappingFieldDao');
        $this->value_mapping_factory = mock('Cardwall_OnTop_Config_ValueMappingFactory');
        stub($this->value_mapping_factory)->getMappings()->returns(array());
        stub($this->value_mapping_factory)->getStatusMappings()->returns(array());

        $this->columns = new Cardwall_OnTop_Config_ColumnFreestyleCollection(
            array(
                new Cardwall_Column(1, 'Todo', 'white'),
                new Cardwall_Column(2, 'On Going', 'white'),
                new Cardwall_Column(3, 'Done', 'white'),
            )
        );

        $this->factory = new Cardwall_OnTop_Config_TrackerMappingFactory($tracker_factory, $element_factory, $this->dao, $this->value_mapping_factory);
    }

    public function itRemovesTheCurrentTrackerFromTheProjectTrackers()
    {
        $expected_trackers = array(
            10 => $this->tracker_10,
            20 => $this->tracker_20
        );

        $this->assertIdentical($expected_trackers, $this->factory->getTrackers($this->tracker));
    }

    public function itLoadsMappingsFromTheDatabase()
    {
        stub($this->dao)->searchMappingFields($this->tracker->getId())->returns(TestHelper::arrayToDar(
            array('tracker_id' => 10, 'field_id' => 123),
            array('tracker_id' => 20, 'field_id' => 124)
        ));

        $mappings = $this->factory->getMappings($this->tracker, $this->columns);
        $this->assertEqual(2, count($mappings));
        $this->assertEqual($this->tracker_10, $mappings[10]->getTracker());
        $this->assertEqual($this->field_123, $mappings[10]->getField());
        $this->assertIsA($mappings[10], 'Cardwall_OnTop_Config_TrackerMappingFreestyle');
        $this->assertEqual(array($this->field_122, $this->field_123), $mappings[10]->getAvailableFields());
        $this->assertEqual($this->tracker_20, $mappings[20]->getTracker());
        $this->assertEqual($this->field_124, $mappings[20]->getField());
    }

    public function itUsesStatusFieldIfNoField()
    {
        stub($this->dao)->searchMappingFields($this->tracker->getId())->returns(TestHelper::arrayToDar(
            array('tracker_id' => 10, 'field_id' => null)
        ));

        $mappings = $this->factory->getMappings($this->tracker, $this->columns);
        $this->assertEqual(1, count($mappings));
        $this->assertEqual($this->status_field, $mappings[10]->getField());
        $this->assertIsA($mappings[10], 'Cardwall_OnTop_Config_TrackerMappingStatus');
    }

    public function itReturnsANoFieldMappingIfNothingInDBAndNoStatus()
    {
        stub($this->dao)->searchMappingFields($this->tracker->getId())->returns(TestHelper::arrayToDar(
            array('tracker_id' => 20, 'field_id' => null)
        ));

        $mappings = $this->factory->getMappings($this->tracker, $this->columns);
        $this->assertEqual(1, count($mappings));
        $this->assertIsA($mappings[20], 'Cardwall_OnTop_Config_TrackerMappingNoField');
    }

    public function itReturnsEmptyMappingIfNoStatus()
    {
        stub($this->dao)->searchMappingFields($this->tracker->getId())->returns(TestHelper::arrayToDar(
            array('tracker_id' => 20, 'field_id' => null)
        ));

        $mappings = $this->factory->getMappings($this->tracker, $this->columns);
        $this->assertEqual(1, count($mappings));
        //TDOD: check type of what is returned
    }

    public function itLoadValueMappings()
    {
        stub($this->dao)->searchMappingFields($this->tracker->getId())->returns(TestHelper::arrayToDar(
            array('tracker_id' => 20, 'field_id' => 124)
        ));
        stub($this->value_mapping_factory)
            ->getMappings($this->tracker, $this->tracker_20, $this->field_124)
            ->once();

        $this->factory->getMappings($this->tracker, $this->columns);
    }

    public function itLoadValueMappingsEvenForStatusField()
    {
        stub($this->dao)->searchMappingFields($this->tracker->getId())->returns(TestHelper::arrayToDar(
            array('tracker_id' => 10, 'field_id' => null)
        ));
        stub($this->value_mapping_factory)
            ->getStatusMappings($this->tracker_10, $this->columns)
            ->once();

        $this->factory->getMappings($this->tracker, $this->columns);
    }
}
