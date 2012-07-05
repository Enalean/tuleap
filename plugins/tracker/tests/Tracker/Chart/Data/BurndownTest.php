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

require_once dirname(__FILE__).'/../../../../include/Tracker/Chart/Data/Burndown.class.php';

class Tracker_Chart_Data_BurndownTest extends TuleapTestCase {
    private $start_date;
    private $time_period;

    public function setUp() {
        parent::setUp();
        $this->start_date  = mktime(0, 0, 0, 7, 4, 2011);
        $this->time_period = new Tracker_Chart_Data_BurndownTimePeriod($this->start_date, 5);
    }

    public function itAddsRemainingEffort() {
        $time_period   = new Tracker_Chart_Data_BurndownTimePeriod($this->start_date, 2);
        $burndown_data = new Tracker_Chart_Data_Burndown($time_period);
        $burndown_data->addRemainingEffort(14);
        $burndown_data->addRemainingEffort(13);
        $burndown_data->addRemainingEffort(12);

        $this->assertEqual($burndown_data->getRemainingEffort(), array(14, 13, 12));
    }

    public function itCompletesMissingRemainingEffortWithLastValue() {
        $burndown_data = new Tracker_Chart_Data_Burndown($this->time_period);
        $burndown_data->addRemainingEffort(14);

        $this->assertEqual($burndown_data->getRemainingEffort(), array(14, 14, 14, 14, 14, 14));
    }


    public function testWhenRemainingEffortValuesDoesntStartInTheSameTimeThanStartDate() {
        $burndown_data = new Tracker_Chart_Data_Burndown($this->time_period);
        $burndown_data->addRemainingEffort(null);
        $burndown_data->addRemainingEffort(null);
        $burndown_data->addRemainingEffort(14);
        $burndown_data->addRemainingEffort(13);
        $burndown_data->addRemainingEffort(12);
        $burndown_data->addRemainingEffort(11);

        $this->assertEqual($burndown_data->getRemainingEffort(), array(14, 14, 14, 13, 12, 11));
    }

    public function testWhenRemainingEffortValuesDoesntStartInTheSameTimeThanStartDate2() {
        $start_date    = strtotime('-2 day', $_SERVER['REQUEST_TIME']);
        $duration      = 5;
        $time_period   = new Tracker_Chart_Data_BurndownTimePeriod($start_date, $duration);
        $burndown_data = new Tracker_Chart_Data_Burndown($time_period);
        $burndown_data->addRemainingEffort(null);
        $burndown_data->addRemainingEffort(null);
        $burndown_data->addRemainingEffort(14);

        $this->assertEqual($burndown_data->getRemainingEffort(), array(14, 14, 14, null, null, null));
    }
    
    public function itDoesNotCompleteRemainingEffortValuesInTheFuture() {
        $start_date    = strtotime('-1 day', $_SERVER['REQUEST_TIME']);
        $duration      = 5;
        $time_period   = new Tracker_Chart_Data_BurndownTimePeriod($start_date, $duration);
        $burndown_data = new Tracker_Chart_Data_Burndown($time_period);
        $burndown_data->addRemainingEffort(14);

        $this->assertEqual($burndown_data->getRemainingEffort(), array(14, 14, null, null, null, null));
    }

    public function itDoesNotCompleteRemainingEffortValuesInTheFuture2() {
        $start_date    = strtotime('-2 day', $_SERVER['REQUEST_TIME']);
        $duration      = 5;
        $time_period   = new Tracker_Chart_Data_BurndownTimePeriod($start_date, $duration);
        $burndown_data = new Tracker_Chart_Data_Burndown($time_period);
        $burndown_data->addRemainingEffort(14);
        $burndown_data->addRemainingEffort(13);

        $this->assertEqual($burndown_data->getRemainingEffort(), array(14, 13, 13, null, null, null));
    }

    public function itComputesIdealBurndownWhenAddingRemainingEffort() {
        $burndown_data = new Tracker_Chart_Data_Burndown($this->time_period);
        $burndown_data->addRemainingEffort(5);

        $this->assertEqual($burndown_data->getIdealEffort(), array(5, 4, 3, 2, 1, 0));
    }
}

class Tracker_Chart_Data_EmptyBurndownTest extends TuleapTestCase {
    private $start_date;
    private $time_period;

    public function setUp() {
        parent::setUp();
        $this->start_date  = mktime(0, 0, 0, 7, 4, 2011);
        $this->time_period = new Tracker_Chart_Data_BurndownTimePeriod($this->start_date, 2);
    }
    
    public function itHasNoRemainingEffort() {
        $burndown_data = new Tracker_Chart_Data_Burndown($this->time_period);
        $this->assertEqual($burndown_data->getRemainingEffort(), array(null, null, null));
    }

    public function itReturnsValidRemainingEffortWhenOnlyAddingNull() {
        $burndown_data = new Tracker_Chart_Data_Burndown($this->time_period);
        $burndown_data->addRemainingEffort(null);
        $burndown_data->addRemainingEffort(null);
        $burndown_data->addRemainingEffort(null);
        $this->assertEqual($burndown_data->getRemainingEffort(), array(null, null, null));
    }
}

?>
