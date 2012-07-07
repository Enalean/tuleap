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
require_once CARDWALL_BASE_DIR .'/OnTop/Config/MappingFieldValueCollectionFactory.class.php';
require_once TRACKER_BASE_DIR .'/../tests/builders/aMockTracker.php';
require_once TRACKER_BASE_DIR .'/../tests/builders/aField.php';

class Cardwall_OnTop_Config_MappingFieldValueCollectionFactoryTest extends TuleapTestCase {

    public function setUp() {
        $tracker_factory       = mock('TrackerFactory');
        $this->dao             = mock('Cardwall_OnTop_ColumnMappingFieldValueDao');
        $this->element_factory = mock('Tracker_FormElementFactory');
        $column_factory        = mock('Cardwall_OnTop_Config_ColumnFactory');
        $this->factory         = new Cardwall_OnTop_Config_MappingFieldValueCollectionFactory($this->dao, $tracker_factory, $this->element_factory, $column_factory);

        $assto_field    = aSelectboxField()->withId(120)->build();
        $status_field   = aSelectboxField()->withId(121)->build();
        $stage_field    = aSelectboxField()->withId(122)->build();
        $progress_field = aSelectboxField()->withId(123)->build();
        $this->status2_field  = aSelectboxField()->withId(124)->build();
        stub($this->element_factory)->getFieldById('120')->returns($assto_field);
        stub($this->element_factory)->getFieldById('121')->returns($status_field);
        stub($this->element_factory)->getFieldById('122')->returns($stage_field);
        stub($this->element_factory)->getFieldById('123')->returns($progress_field);
        stub($this->element_factory)->getFieldById('124')->returns($this->status2_field);

        $this->sprint_tracker = aMockTracker()->withId(66)->withStatusField($status_field)->withProjectId(101)->build();
        $this->story_tracker  = aMockTracker()->withId(67)->withStatusField($stage_field)->build();
        $this->tasks_tracker  = aMockTracker()->withId(68)->withStatusField($progress_field)->build();
        $this->bugs_tracker   = aMockTracker()->withId(69)->withStatusField($this->status2_field)->build();
        $this->faqs_tracker   = aMockTracker()->withId(70)->build();

        stub($tracker_factory)->getTrackersByGroupId(101)->returns(
            array($this->sprint_tracker, $this->story_tracker, $this->tasks_tracker, $this->bugs_tracker, $this->faqs_tracker)
        );

        stub($column_factory)->getColumnsFromStatusField($this->story_tracker)->returns(array(
            new Cardwall_OnTop_Config_Column(1001, 'Todo'),
            new Cardwall_OnTop_Config_Column(1002, 'On Going'),
            new Cardwall_OnTop_Config_Column(1003, 'Done'),
        ));
        stub($column_factory)->getColumnsFromStatusField($this->tasks_tracker)->returns(array(
            new Cardwall_OnTop_Config_Column(1011, 'Todo'),
            new Cardwall_OnTop_Config_Column(1013, 'Done'),
        ));
        stub($column_factory)->getColumnsFromStatusField($this->bugs_tracker)->returns(array(
            new Cardwall_OnTop_Config_Column(1021, 'Open'),
            new Cardwall_OnTop_Config_Column(1022, 'Closed'),
        ));
        stub($column_factory)->getColumnsFromStatusField($this->faqs_tracker)->returns(array());
        stub($column_factory)->getColumns($this->sprint_tracker)->returns(array(
            new Cardwall_OnTop_Config_Column(1031, 'Todo'),
            new Cardwall_OnTop_Config_Column(1032, 'On Going'),
            new Cardwall_OnTop_Config_Column(1033, 'Done'),
        ));
    }

    public function itCreatesACollectionFromTheOtherTrackersOfTheProjects() {
        stub($this->dao)->searchMappingFieldValues($this->sprint_tracker->getId())->returns(TestHelper::arrayToDar());
        $collection = $this->factory->getCollection($this->sprint_tracker);

        $this->assertEqual(3, count($collection->getForTracker($this->story_tracker)));
        $this->assertEqual(2, count($collection->getForTracker($this->tasks_tracker)));
        $this->assertEqual(0, count($collection->getForTracker($this->sprint_tracker)));
        $this->assertEqual(1, count($collection->getForTracker($this->faqs_tracker)));

        $bugs_mapping = $collection->getForTracker($this->bugs_tracker);
        $this->assertIsA($bugs_mapping[0], 'Cardwall_OnTop_Config_MappingFieldValueNoField');
    }

    public function itCreatesAMappingFromTheDataStorageAndTakeTheStatusField() {
        stub($this->dao)->searchMappingFieldValues($this->sprint_tracker->getId())->returns(TestHelper::arrayToDar(
            array(
                'tracker_id'          => $this->bugs_tracker->getId(),
                'field_id'            => null,
                'value_id'            => '1021',
                'column_id'           => '1031',
            ),
            array(
                'tracker_id'          => $this->bugs_tracker->getId(),
                'field_id'            => null,
                'value_id'            => '1022',
                'column_id'           => '1033',
            )
        ));
        $collection = $this->factory->getCollection($this->sprint_tracker);

        $bugs_mapping = $collection->getForTracker($this->bugs_tracker);
        $this->assertEqual(2, count($bugs_mapping));
    }
}
?>
