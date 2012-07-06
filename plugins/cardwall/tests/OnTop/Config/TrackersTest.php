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

class Cardwall_OnTop_Config_Trackers_getNonMappedTrackersTest extends TuleapTestCase {

    public function itReturnsAllTrackersWhenNothingIsMapped() {
        $trackers = array(10 => aTracker()->withId(10)->build(),
                          11 => aTracker()->withId(11)->build());
        $mappings = new Cardwall_OnTop_Config_MappingFields(array());
        $config_trackers = new Cardwall_OnTop_Config_Trackers($trackers, aTracker()->withId(77)->build(), $mappings);
        $this->assertEqual($trackers, $config_trackers->getNonMappedTrackers());
    }

    public function itStripsTheCurrentTracker() {
        $current_tracker = aTracker()->withId(10)->build();
        $other_tracker   = aTracker()->withId(99)->build();
        $trackers = array(10 => $current_tracker,
                          99 => $other_tracker);
        $mappings = new Cardwall_OnTop_Config_MappingFields(array());
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
        $mappings = mock('Cardwall_OnTop_Config_MappingFields');
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
        $mappings = mock('Cardwall_OnTop_Config_MappingFields');
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
        $mappings = mock('Cardwall_OnTop_Config_MappingFields');
        stub($mappings)->getTrackers()->returns($mapped_trackers);
        $config_trackers = new Cardwall_OnTop_Config_Trackers($trackers, $current_tracker, $mappings);
        $this->assertEqual($expected, $config_trackers->getMappedTrackers());
    }
}

?>
