<?php
/**
 * Copyright (c) Enalean, 2012 - 2017. All Rights Reserved.
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

require_once __DIR__.'/../../../bootstrap.php';

class Tracker_Chart_Data_BurndownTest extends TuleapTestCase
{
    private $start_date;
    private $time_period;

    public function setUp()
    {
        parent::setUp();
        $this->start_date  = mktime(0, 0, 0, 7, 4, 2011);
        $this->time_period = TimePeriodWithoutWeekEnd::buildFromDuration($this->start_date, 5);
    }

    public function itAddsRemainingEffort()
    {
        $time_period   = TimePeriodWithoutWeekEnd::buildFromDuration($this->start_date, 2);
        $burndown_data = new Tracker_Chart_Data_Burndown($time_period);
        $burndown_data->addEffortAt(0, 14);
        $burndown_data->addEffortAt(1, 13);
        $burndown_data->addEffortAt(2, 12);

        $this->assertEqual($burndown_data->getRemainingEffort(), array(14, 13, 12));
    }

    public function itCompletesMissingRemainingEffortWithLastValue()
    {
        $burndown_data = new Tracker_Chart_Data_Burndown($this->time_period);
        $burndown_data->addEffortAt(0, 14);

        $this->assertEqual($burndown_data->getRemainingEffort(), array(14, null, null, null, null, null));
    }

    public function itComputesIdealBurndownWhenAddingRemainingEffort()
    {
        $burndown_data = new Tracker_Chart_Data_Burndown($this->time_period);
        $burndown_data->addEffortAt(0, 5);

        $this->assertEqual($burndown_data->getIdealEffort(), array(5, 4, 3, 2, 1, 0));
    }

    public function testBurndownWillUseCapacityIfSet()
    {
        $capacity = 100;
        $burndown_data = new Tracker_Chart_Data_Burndown($this->time_period, $capacity);

        $this->assertEqual($burndown_data->getIdealEffort(), array(100, 80, 60, 40, 20, 0));
    }

    public function testBurndownWillGivePriorityToCapacity()
    {
        $capacity = 100;
        $burndown_data = new Tracker_Chart_Data_Burndown($this->time_period, $capacity);
        $burndown_data->addEffortAt(0, 5);

        $this->assertEqual($burndown_data->getIdealEffort(), array(100, 80, 60, 40, 20, 0));
    }

    public function testBurndownWillIgnoreNullCapacity()
    {
        $capacity = null;
        $burndown_data = new Tracker_Chart_Data_Burndown($this->time_period, $capacity);
        $burndown_data->addEffortAt(0, 5);

        $this->assertEqual($burndown_data->getIdealEffort(), array(5, 4, 3, 2, 1, 0));
    }

    public function testBurndownWillIgnoreZeroCapacity()
    {
        $capacity = 0;
        $burndown_data = new Tracker_Chart_Data_Burndown($this->time_period, $capacity);
        $burndown_data->addEffortAt(0, 5);

        $this->assertEqual($burndown_data->getIdealEffort(), array(5, 4, 3, 2, 1, 0));
    }

    public function itReturnsAnEmptyArrayWhenBurndownIsUnderCalculation()
    {
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

    public function itReturnsBurndownDataInJson()
    {
        $capacity = 7;
        $burndown_data = new Tracker_Chart_Data_Burndown($this->time_period, $capacity);
        $burndown_data->addEffortAt(0, 5);
        $burndown_data->addEffortAt(1, 4);
        $burndown_data->addEffortAt(2, 3);
        $burndown_data->addEffortAt(3, 1);

        $results = json_decode($burndown_data->getJsonRepresentation());

        $expected_points = array(5,4,3,1);

        $this->assertEqual($results->duration, 5);
        $this->assertEqual($results->capacity, 7);
        $this->assertEqual($results->points, $expected_points);
    }

    public function itReturnsBurndownDataInJsonAndDealWithNullValues()
    {
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

class Tracker_Chart_Data_EmptyBurndownTest extends TuleapTestCase
{
    private $start_date;
    private $time_period;

    public function setUp()
    {
        parent::setUp();
        $this->start_date  = mktime(0, 0, 0, 7, 4, 2011);
        $this->time_period = TimePeriodWithoutWeekEnd::buildFromDuration($this->start_date, 2);
    }

    public function itHasNoRemainingEffort()
    {
        $burndown_data = new Tracker_Chart_Data_Burndown($this->time_period);
        $this->assertIdentical($burndown_data->getRemainingEffort(), array(null, null, null));
    }

    public function itReturnsValidRemainingEffortWhenOnlyAddingNull()
    {
        $burndown_data = new Tracker_Chart_Data_Burndown($this->time_period);
        $burndown_data->addEffortAt(0, null);
        $burndown_data->addEffortAt(1, null);
        $burndown_data->addEffortAt(2, null);
        $this->assertIdentical($burndown_data->getRemainingEffort(), array(null, null, null));
    }

    public function itHasNoIdealBurndown()
    {
        $burndown_data = new Tracker_Chart_Data_Burndown($this->time_period);
        $this->assertIdentical($burndown_data->getIdealEffort(), array(0, 0, 0));
    }
}
