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
require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class Tracker_Chart_BurndownTest_FakeData implements Tracker_Chart_Data_IProvideDataForBurndownChart {
    public $artifact_ids     = array();
    public $remaining_effort = array();
    public $min_day = 99999;
    public $max_day = 0;
    
    public function getRemainingEffort() {
        return $this->remaining_effort;
    }
    
    public function getMinDay() {
        return $this->min_day;
    }
    
    public function getMaxDay() {
        return $this->max_day;
    }
    
    public function getArtifactIds() {
        return $this->artifact_ids;
    }
}

class Tracker_Chart_BurndownTest extends TuleapTestCase {

    public function itNormalizeDataDayByDayStartingAtStartDate() {
        $data = new Tracker_Chart_BurndownTest_FakeData();
        $data->remaining_effort = array(
            15311 => array(
                5215 => null,
                5217 => null
            ),
            15443 => array(
                5215 => '1.0000',
                5217 => null,
                5239 => null,
                5241 => '13.0000'
            ),
            15441 => array(
                5215 => '2.0000',
                5217 => '1.0000',
                5239 => '0.5000',
                5241 => '14.0000')
        );
        $data->min_day = 15311;
        $data->max_day = 15443;
        $data->artifact_ids = array(5215, 5217, 5239, 5241);

        $burndown = new Tracker_Chart_Burndown($data);
        $burndown->setStartDateInDays(15441);
        
        $prepared_data = $burndown->getComputedData();
        
        // The 3 days
        $this->assertEqual(count($prepared_data), 3, "There are 3 days between start_date and max data");
        $this->assertEqual(array_keys($prepared_data), array(15441, 15442, 15443));
        
        // First day
        $day1 = $prepared_data[15441];
        $this->assertEqual(array_keys($day1), array(5215, 5217, 5239, 5241));
        $this->assertEqual($day1[5215], 2);
        $this->assertEqual($day1[5217], 1);
        $this->assertEqual($day1[5239], 0.5);
        $this->assertEqual($day1[5241], 14);
        
        $day2 = $prepared_data[15442];
        $this->assertEqual(array_keys($day2), array(5215, 5217, 5239, 5241));
        $this->assertEqual($day2[5215], 2);
        $this->assertEqual($day2[5217], 1);
        $this->assertEqual($day2[5239], 0.5);
        $this->assertEqual($day2[5241], 14);
        
        $day3 = $prepared_data[15443];
        $this->assertEqual(array_keys($day3), array(5215, 5217, 5239, 5241));
        $this->assertEqual($day3[5215], 1);
        $this->assertEqual($day3[5217], 1, "No data, keep old value");
        $this->assertEqual($day3[5239], 0.5, "No data, keep old value");
        $this->assertEqual($day3[5241], 13);
    }
    
    public function itdoesntMakeAnErrorWhenRemainingEffortsAreNotSet() {
        $data = new Tracker_Chart_BurndownTest_FakeData();
        $burndown = new Tracker_Chart_Burndown($data);
        $burndown->prepareDataForGraph(array());
        $expected = array_fill(0, 11, 0);
        $this->assertEqual($expected, $burndown->getGraphDataRemainingEffort());
    }
    
    public function itShouldProvideEmptyNormalizedDataWhenStartDateIsBeforeTheFirstDate() {
        $data = new Tracker_Chart_BurndownTest_FakeData();
        $data->remaining_effort = array(
            15311 => array(
                5215 => null,
                5217 => null
            ),
            15443 => array(
                5215 => '1.0000',
                5217 => null,
                5239 => null,
                5241 => '13.0000'
            ),
            15441 => array(
                5215 => '2.0000',
                5217 => '1.0000',
                5239 => '0.5000',
                5241 => '14.0000')
        );
        $data->min_day = 15311;
        $data->max_day = 15443;
        $data->artifact_ids = array(5215, 5217, 5239, 5241);
        
        $burndown = new Tracker_Chart_Burndown($data);
        $burndown->setStartDateInDays(15305);
        
        
        // No notices should be thrown
        $burndown->getComputedData();
    }
    
    public function itShouldTakeIntoAccountWhenValueFallToZero() {
        $data = new Tracker_Chart_BurndownTest_FakeData();
        $data->remaining_effort = array(
            15441 => array(
                5215 => '2.0000',
                5217 => '0.0000',
                5239 => '0.5000',
                5241 => '14.0000'),
            15443 => array(
                5215 => '0.0000',
                5217 => null,
                5239 => null,
                5241 => '13.0000'
            ),
        );
        $data->min_day = 15441;
        $data->max_day = 15443;
        $data->artifact_ids = array(5215, 5217, 5239, 5241);
        
        $burndown = new Tracker_Chart_Burndown($data);
        $burndown->setStartDateInDays(15441);
        
        $prepared_data = $burndown->getComputedData();
        $this->assertEqual($prepared_data[15443][5215], 0, "When the last value is 0, returns 0 (".$prepared_data[15443][5215]." returned)");
        $this->assertEqual($prepared_data[15443][5217], 0, "When value is null, keep previous value");
        $this->assertEqual($prepared_data[15443][5239], 0.5, "When value is null, keep previous value");
        $this->assertEqual($prepared_data[15443][5241], 13.0, "When value decrease, keep value");
    }
}

class Tracker_Chart_Burndown_FormatDataForDisplayPerDay extends TuleapTestCase {
    
    public function setUp() {
        $remaining_effort = array(
            15440 =>array(
            ),
            15441 => array(
                5215 => 2,
                5217 => 1,
                5239 => 2,
                5241 => 15
            ),
            15442 => array(
                5215 => 2,
                5217 => 1,
                5239 => 2.5,
                5241 => 15.5
            ),
            15443 => array(
                5215 => 1,
                5217 => 1,
                5239 => 0,
                5241 => 13
            ),
        );
        
        $data = new Tracker_Chart_BurndownTest_FakeData();
        $this->burndown = new Tracker_Chart_Burndown($data);
        $this->burndown->setDuration(4);
        $this->burndown->prepareDataForGraph($remaining_effort);
    }
    
    public function itShouldComputeRemainingEffortForDisplay() {
        $remaining_effort_by_day = $this->burndown->getGraphDataRemainingEffort();
        $this->assertIdentical($remaining_effort_by_day[0], 20.0);
        $this->assertIdentical($remaining_effort_by_day[1], 20.0);
        $this->assertIdentical($remaining_effort_by_day[2], 21.0);
        $this->assertIdentical($remaining_effort_by_day[3], 15);
        $this->assertIdentical($remaining_effort_by_day[4], null);
    }
    
    public function itShouldComputeIdealBurndownForDisplay() {
        $ideal_burndown_by_day = $this->burndown->getGraphDataIdealBurndown();
        $this->assertIdentical($ideal_burndown_by_day[0], 20.0);
        $this->assertIdentical($ideal_burndown_by_day[1], 15.0);
        $this->assertIdentical($ideal_burndown_by_day[2], 10.0);
        $this->assertIdentical($ideal_burndown_by_day[3], 5.0);
        $this->assertIdentical($ideal_burndown_by_day[4], 0.0);
    }
    
    /*
     * Test doesn't work on CI server, probably because of a Timezone issue in
     * day -> date conversion.
    function itShouldComputeHumanReadableDaysForDisplay() {
        $human_readable_dates_by_day = $this->burndown->getGraphDataHumanDates();
        $this->assertEqual($human_readable_dates_by_day[0], 'Apr-10');
        $this->assertEqual($human_readable_dates_by_day[1], 'Apr-11');
        $this->assertEqual($human_readable_dates_by_day[2], 'Apr-12');
        $this->assertEqual($human_readable_dates_by_day[3], 'Apr-13');
    }
     */
}

?>
