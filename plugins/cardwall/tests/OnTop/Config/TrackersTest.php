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
require_once CARDWALL_BASE_DIR .'/View/AdminView.class.php';
require_once TRACKER_BASE_DIR .'/../tests/builders/aTracker.php';
require_once TRACKER_BASE_DIR .'/../tests/builders/aField.php';
require_once dirname(__FILE__).'/../../../include/View/AdminView.class.php';

class Cardwall_OnTop_Config_Trackers_getNonMappedTrackersTest extends TuleapTestCase {

    public function itReturnsAllTrackersWhenNothingIsMapped() {
        $trackers = array(10 => aTracker()->withId(10)->build(),
                          11 => aTracker()->withId(11)->build());
        $mappings = new Cardwall_OnTop_Config_MappimgFields(array());
        $config_trackers = new Cardwall_OnTop_Config_Trackers($trackers, aTracker()->withId(77)->build(), $mappings);
        $this->assertEqual($trackers, $config_trackers->getNonMappedTrackers());
    }

    public function itStripsTheCurrentTracker() {
        $current_tracker = aTracker()->withId(10)->build();
        $other_tracker   = aTracker()->withId(99)->build();
        $trackers = array(10 => $current_tracker,
                          99 => $other_tracker);
        $mappings = new Cardwall_OnTop_Config_MappimgFields(array());
        $config_trackers = new Cardwall_OnTop_Config_Trackers($trackers, $current_tracker, $mappings);
        $this->assertEqual(array(99 => $other_tracker), $config_trackers->getNonMappedTrackers());
    }

    public function itStripsTheMappedTracker() {
        $current_tracker = aTracker()->withId(10)->build();
        $story_tracker   = aTracker()->withId(11)->build();
        $task_tracker    = aTracker()->withId(12)->build();
        $other_tracker   = aTracker()->withId(99)->build();
        $trackers = array(10 => $current_tracker,
                          11 => $story_tracker,
                          12 => $task_tracker,
                          99 => $other_tracker);
        $mappings = mock('Cardwall_OnTop_Config_MappimgFields');
        stub($mappings)->getTrackers()->returns(
            array(
                11 => $story_tracker,
                12 => $task_tracker,
            )
        );
        $config_trackers = new Cardwall_OnTop_Config_Trackers($trackers, $current_tracker, $mappings);
        $this->assertEqual(array(99 => $other_tracker), $config_trackers->getNonMappedTrackers());
    }
}

class Cardwall_OnTop_Config_Trackers_getMappedTrackersTest extends TuleapTestCase {

    public function itReturnsTheMappedTracker() {
        $current_tracker = aTracker()->withId(10)->build();
        $story_tracker   = aTracker()->withId(11)->build();
        $task_tracker    = aTracker()->withId(12)->build();
        $other_tracker   = aTracker()->withId(99)->build();
        $trackers = array(10 => $current_tracker,
                          11 => $story_tracker,
                          12 => $task_tracker,
                          99 => $other_tracker);

        $mapped_trackers = array(
            11 => $story_tracker,
            12 => $task_tracker,
        );
        $mappings = mock('Cardwall_OnTop_Config_MappimgFields');
        stub($mappings)->getTrackers()->returns($mapped_trackers);
        $config_trackers = new Cardwall_OnTop_Config_Trackers($trackers, $current_tracker, $mappings);
        $this->assertEqual($mapped_trackers, $config_trackers->getMappedTrackers());
    }

    public function itStripsTheCurrentTracker() {
        $current_tracker = aTracker()->withId(10)->build();
        $story_tracker   = aTracker()->withId(11)->build();
        $task_tracker    = aTracker()->withId(12)->build();
        $other_tracker   = aTracker()->withId(99)->build();
        $trackers = array(10 => $current_tracker,
                          11 => $story_tracker,
                          12 => $task_tracker,
                          99 => $other_tracker);

        $mapped_trackers = array(
            10 => $current_tracker,
            11 => $story_tracker,
            12 => $task_tracker,
        );
        $expected = array(
            11 => $story_tracker,
            12 => $task_tracker,
        );
        $mappings = mock('Cardwall_OnTop_Config_MappimgFields');
        stub($mappings)->getTrackers()->returns($mapped_trackers);
        $config_trackers = new Cardwall_OnTop_Config_Trackers($trackers, $current_tracker, $mappings);
        $this->assertEqual($expected, $config_trackers->getMappedTrackers());
    }
}

class Cardwall_OnTop_Config_FieldMappingsFactory_getMappingsTest extends TuleapTestCase {
    
    public function itReturnsAnEmptyMappingIfThereAreNoMappings() {
        $field_mappng_factory = mock('Cardwall_OnTop_Config_FieldMappingFactory');
        $tracker_factory = mock('TrackerFactory');
        $mapping_dao     = stub('Cardwall_OnTop_ColumnMappingFieldDao')->searchMappingFields()->returns(array());
        $tracker         = aTracker()->build();
        $factory = new Cardwall_OnTop_Config_FieldMappingsFactory($tracker_factory, $mapping_dao, $field_mappng_factory);
        $this->assertIdentical(array(), $factory->getMappings($tracker));
    }
    
    public function itReturnsAnEmptyMappingIfThereAreNoMoreTrackers() {
        $tracker         = aTracker()->withId(3)->withProjectId(234)->build();
        $field_mapping_factory = mock('Cardwall_OnTop_Config_FieldMappingFactory');
        $tracker_factory = stub('TrackerFactory')->getTrackersByGroupId(234)->returns(array(3 => $tracker));
        $mapping_dao     = stub('Cardwall_OnTop_ColumnMappingFieldDao')->searchMappingFields()->returns(array());
        $tracker         = aTracker()->withProjectId(234)->build();
        $factory = new Cardwall_OnTop_Config_FieldMappingsFactory($tracker_factory, $mapping_dao, $field_mapping_factory);
        $this->assertIdentical(array(), $factory->getMappings($tracker));
    }

    public function itReturnsAMappingForEveryTupleProvidedByTheDao() {
        $group_id        = 234;
        $tracker         = aTracker()->withId(3)->withProjectId($group_id)->build();
        $other_tracker1  = aTracker()->withId(10)->build();
        $other_tracker2  = aTracker()->withId(20)->build();
        $project_trackers= array(3  => $tracker,
                                 10 => $other_tracker1,
                                 20 => $other_tracker2);
        $field_id1       = 123;
        $field_id2       = 456;
        $raw_mappings    = array( 
                                array('tracker_id' => 10, 'field_id' => $field_id1),
                                array('tracker_id' => 20, 'field_id' => $field_id2));
        $mapping1 = mock('Cardwall_OnTop_Config_TrackerFieldMapping');
        $mapping2 = mock('Cardwall_OnTop_Config_TrackerFieldMapping');
        $field_mapping_factory = mock('Cardwall_OnTop_Config_FieldMappingFactory');
        stub($field_mapping_factory)->newMapping($other_tracker1, $field_id1)->returns($mapping1);
        stub($field_mapping_factory)->newMapping($other_tracker2, $field_id2)->returns($mapping2);
        $tracker_factory = stub('TrackerFactory')->getTrackersByGroupId($group_id)->returns($project_trackers);
        $mapping_dao     = stub('Cardwall_OnTop_ColumnMappingFieldDao')->searchMappingFields($tracker->getId())->returns($raw_mappings);
        $factory = new Cardwall_OnTop_Config_FieldMappingsFactory($tracker_factory, $mapping_dao, $field_mapping_factory);
        $this->assertEqual(array($mapping1, $mapping2), $factory->getMappings($tracker));
    }
    
}
class Cardwall_OnTop_Config_FieldMappingFactory_newMappingTest extends TuleapTestCase {
        
    public function itReturnsAMappingWithFieldTrackerAndAvailableFields() {
        $tracker         = aTracker()->withId(3)->build();
        $field = aSelectBoxField()->build();
        $element_factory = stub('Tracker_FormElementFactory')->getFieldById(123)->returns($field);
        $fields = array(
                    aSelectBoxField()->build(),
                    aSelectBoxField()->build());
        stub($element_factory)->getUsedSbFields($tracker)->returns($fields);
        
        $expected_mapping = new Cardwall_OnTop_Config_TrackerFieldMapping($tracker, $field, $fields);
        
        $factory = new Cardwall_OnTop_Config_FieldMappingFactory($element_factory);
        $actual_mapping = $factory->newMapping($tracker, 123);
        
        $this->assertEqual($expected_mapping, $actual_mapping);
    }
    
    public function testTheMappingsContainAvailableSelectBoxFields() {
        
    }

    
}

?>
