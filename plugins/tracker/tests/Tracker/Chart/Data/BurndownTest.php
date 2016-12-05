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
require_once 'common/date/TimePeriodWithWeekEnd.class.php';

class Tracker_Chart_Data_BurndownTest extends TuleapTestCase {
    private $start_date;
    private $time_period;

    public function setUp() {
        parent::setUp();
        $this->start_date  = mktime(0, 0, 0, 7, 4, 2011);
        $this->time_period = new TimePeriodWithWeekEnd($this->start_date, 5);
    }

    public function itAddsRemainingEffort() {
        $time_period   = new TimePeriodWithWeekEnd($this->start_date, 2);
        $burndown_data = new Tracker_Chart_Data_Burndown($time_period);
        $burndown_data->addEffortAt(0, 14);
        $burndown_data->addEffortAt(1, 13);
        $burndown_data->addEffortAt(2, 12);

        $this->assertEqual($burndown_data->getRemainingEffort(), array(14, 13, 12));
    }

    public function itCompletesMissingRemainingEffortWithLastValue() {
        $burndown_data = new Tracker_Chart_Data_Burndown($this->time_period);
        $burndown_data->addEffortAt(0, 14);

        $this->assertEqual($burndown_data->getRemainingEffort(), array(14, 14, 14, 14, 14, 14));
    }


    public function testWhenRemainingEffortValuesDoesntStartInTheSameTimeThanStartDate() {
        $burndown_data = new Tracker_Chart_Data_Burndown($this->time_period);
        $burndown_data->addEffortAt(0, null);
        $burndown_data->addEffortAt(1, null);
        $burndown_data->addEffortAt(2, 14);
        $burndown_data->addEffortAt(3, 13);
        $burndown_data->addEffortAt(4, 12);
        $burndown_data->addEffortAt(5, 11);

        $this->assertEqual($burndown_data->getRemainingEffort(), array(14, 14, 14, 13, 12, 11));
    }

    public function testWhenRemainingEffortValuesDoesntStartInTheSameTimeThanStartDate2() {
        $start_date       = strtotime('-2 day', $_SERVER['REQUEST_TIME']);
        $duration         = 5;
        $time_period      = new TimePeriodWithWeekEnd($start_date, $duration);
        $burndown_data    = new Tracker_Chart_Data_Burndown($time_period);
        $burndown_data->addEffortAt(0, null);
        $burndown_data->addEffortAt(1, null);
        $burndown_data->addEffortAt(2, 14);

        $this->assertEqual($burndown_data->getRemainingEffort(), array(14, 14, 14, null, null, null));
    }

    public function itShouldNotResetPreviousValuesWhenPushingNullAfterHavingPushAnActualNumber() {
        $start_date       = strtotime('-4 day', $_SERVER['REQUEST_TIME']);
        $duration         = 5;
        $time_period      = new TimePeriodWithWeekEnd($start_date, $duration);
        $burndown_data    = new Tracker_Chart_Data_Burndown($time_period);
        $burndown_data->addEffortAt(0, null);
        $burndown_data->addEffortAt(1, null);
        $burndown_data->addEffortAt(2, 14);
        $burndown_data->addEffortAt(3, 7);
        $burndown_data->addEffortAt(4, null);

        $this->assertEqual($burndown_data->getRemainingEffort(), array(14, 14, 14, 7, null, null));
    }

    public function itDoesNotCompleteRemainingEffortValuesInTheFuture() {
        $start_date       = strtotime('-1 day', $_SERVER['REQUEST_TIME']);
        $duration         = 5;
        $time_period      = new TimePeriodWithWeekEnd($start_date, $duration);
        $burndown_data    = new Tracker_Chart_Data_Burndown($time_period);
        $burndown_data->addEffortAt(0, 14);

        $this->assertEqual($burndown_data->getRemainingEffort(), array(14, 14, null, null, null, null));
    }

    public function itDoesNotCompleteRemainingEffortValuesInTheFuture2() {
        $start_date    = strtotime('-2 day', $_SERVER['REQUEST_TIME']);
        $duration      = 5;
        $time_period   = new TimePeriodWithWeekEnd($start_date, $duration);
        $burndown_data = new Tracker_Chart_Data_Burndown($time_period);
        $burndown_data->addEffortAt(0, 14);
        $burndown_data->addEffortAt(1, 13);

        $this->assertEqual($burndown_data->getRemainingEffort(), array(14, 13, 13, null, null, null));
    }

    public function itReturnsRemainingEffortsWithNonLinearDayOffsets() {
        $time_period = mock('TimePeriod');
        stub($time_period)->getStartDate()->returns($this->start_date);
        stub($time_period)->getDayOffsets()->returns(array(0, 1, 4, 5, 6, 7));
        $burndown_data = new Tracker_Chart_Data_Burndown($time_period);
        $burndown_data->addEffortAt(0, 14);
        $burndown_data->addEffortAt(1, 13);
        $burndown_data->addEffortAt(4, 10);
        $burndown_data->addEffortAt(5, 5);
        $burndown_data->addEffortAt(6, 2);
        $burndown_data->addEffortAt(7, 0);

        $this->assertEqual($burndown_data->getRemainingEffort(), array(14, 13, 10, 5, 2, 0));
    }

    public function itComputesIdealBurndownWhenAddingRemainingEffort() {
        $burndown_data = new Tracker_Chart_Data_Burndown($this->time_period);
        $burndown_data->addEffortAt(0, 5);

        $this->assertEqual($burndown_data->getIdealEffort(), array(5, 4, 3, 2, 1, 0));
    }

    public function testBurndownWillUseCapacityIfSet() {
        $capacity = 100;
        $burndown_data = new Tracker_Chart_Data_Burndown($this->time_period, $capacity);

        $this->assertEqual($burndown_data->getIdealEffort(), array(100, 80, 60, 40, 20, 0));
    }

    public function testBurndownWillGivePriorityToCapacity() {
        $capacity = 100;
        $burndown_data = new Tracker_Chart_Data_Burndown($this->time_period, $capacity);
        $burndown_data->addEffortAt(0, 5);

        $this->assertEqual($burndown_data->getIdealEffort(), array(100, 80, 60, 40, 20, 0));
    }

    public function testBurndownWillIgnoreNullCapacity() {
        $capacity = null;
        $burndown_data = new Tracker_Chart_Data_Burndown($this->time_period, $capacity);
        $burndown_data->addEffortAt(0, 5);

        $this->assertEqual($burndown_data->getIdealEffort(), array(5, 4, 3, 2, 1, 0));
    }

    public function testBurndownWillIgnoreZeroCapacity() {
        $capacity = 0;
        $burndown_data = new Tracker_Chart_Data_Burndown($this->time_period, $capacity);
        $burndown_data->addEffortAt(0, 5);

        $this->assertEqual($burndown_data->getIdealEffort(), array(5, 4, 3, 2, 1, 0));
    }

    public function itReturnsAnEmptyArrayWhenBurndownIsUnderCalculation() {
        $capacity = 7;
        $burndown_data = new Tracker_Chart_Data_Burndown($this->time_period, $capacity);
        $burndown_data->addEffortAt(0, 5);
        $burndown_data->addEffortAt(1, 4);
        $burndown_data->addEffortAt(2, 3);
        $burndown_data->addEffortAt(3, 1);

        $burndown_data->setIsBeingCalculated(true);

        $results = json_decode($burndown_data->getJsonRepresentation());

        $expected_points = array();

        $this->assertEqual($results->duration, 5);
        $this->assertEqual($results->capacity, 7);
        $this->assertEqual($results->points, $expected_points);
    }

    public function itReturnsBurndownDataInJson() {
        $capacity = 7;
        $burndown_data = new Tracker_Chart_Data_Burndown($this->time_period, $capacity);
        $burndown_data->addEffortAt(0, 5);
        $burndown_data->addEffortAt(1, 4);
        $burndown_data->addEffortAt(2, 3);
        $burndown_data->addEffortAt(3, 1);

        $results = json_decode($burndown_data->getJsonRepresentation());

        $expected_points = array(5,4,3,1,1,1);

        $this->assertEqual($results->duration, 5);
        $this->assertEqual($results->capacity, 7);
        $this->assertEqual($results->points, $expected_points);
    }

    public function itReturnsBurndownDataInJsonAndDealWithNullValues() {
        $capacity = 7;
        $burndown_data = new Tracker_Chart_Data_Burndown($this->time_period, $capacity);
        $burndown_data->addEffortAt(0, 5);
        $burndown_data->addEffortAt(1, 4);
        $burndown_data->addEffortAt(2, null);
        $burndown_data->addEffortAt(3, null);

        $results = json_decode($burndown_data->getJsonRepresentation());

        $expected_points = array(5,4);

        $this->assertEqual($results->duration, 5);
        $this->assertEqual($results->capacity, 7);
        $this->assertEqual($results->points, $expected_points);
    }
}

class Tracker_Chart_Data_EmptyBurndownTest extends TuleapTestCase {
    private $start_date;
    private $time_period;

    public function setUp() {
        parent::setUp();
        $this->start_date  = mktime(0, 0, 0, 7, 4, 2011);
        $this->time_period = new TimePeriodWithWeekEnd($this->start_date, 2);
    }
    
    public function itHasNoRemainingEffort() {
        $burndown_data = new Tracker_Chart_Data_Burndown($this->time_period);
        $this->assertIdentical($burndown_data->getRemainingEffort(), array(null, null, null));
    }

    public function itReturnsValidRemainingEffortWhenOnlyAddingNull() {
        $burndown_data = new Tracker_Chart_Data_Burndown($this->time_period);
        $burndown_data->addEffortAt(0, null);
        $burndown_data->addEffortAt(1, null);
        $burndown_data->addEffortAt(2, null);
        $this->assertIdentical($burndown_data->getRemainingEffort(), array(null, null, null));
    }
    
    public function itHasNoIdealBurndown() {
        $burndown_data = new Tracker_Chart_Data_Burndown($this->time_period);
        $this->assertIdentical($burndown_data->getIdealEffort(), array(0, 0, 0));
    }
}

?>
