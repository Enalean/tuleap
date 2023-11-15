<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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

use Tuleap\Date\DatePeriodWithoutWeekEnd;

final class Tracker_Chart_Data_BurndownTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    private $start_date;
    private $date_period;

    protected function setUp(): void
    {
        $this->start_date  = mktime(0, 0, 0, 7, 4, 2011);
        $this->date_period = DatePeriodWithoutWeekEnd::buildFromDuration($this->start_date, 5);
    }

    public function testItAddsRemainingEffort(): void
    {
        $date_period   = DatePeriodWithoutWeekEnd::buildFromDuration($this->start_date, 2);
        $burndown_data = new Tracker_Chart_Data_Burndown($date_period);
        $burndown_data->addEffortAt(0, 14);
        $burndown_data->addEffortAt(1, 13);
        $burndown_data->addEffortAt(2, 12);

        $this->assertEquals([14, 13, 12], $burndown_data->getRemainingEffort());
    }

    public function testItCompletesMissingRemainingEffortWithLastValue(): void
    {
        $burndown_data = new Tracker_Chart_Data_Burndown($this->date_period);
        $burndown_data->addEffortAt(0, 14);

        $this->assertEquals([14, null, null, null, null, null], $burndown_data->getRemainingEffort());
    }

    public function testItComputesIdealBurndownWhenAddingRemainingEffort(): void
    {
        $burndown_data = new Tracker_Chart_Data_Burndown($this->date_period);
        $burndown_data->addEffortAt(0, 5);

        $this->assertEquals([5, 4, 3, 2, 1, 0], $burndown_data->getIdealEffort());
    }

    public function testIestBurndownWillUseCapacityIfSet(): void
    {
        $capacity      = 100;
        $burndown_data = new Tracker_Chart_Data_Burndown($this->date_period, $capacity);

        $this->assertEquals([100, 80, 60, 40, 20, 0], $burndown_data->getIdealEffort());
    }

    public function testIestBurndownWillGivePriorityToCapacity(): void
    {
        $capacity      = 100;
        $burndown_data = new Tracker_Chart_Data_Burndown($this->date_period, $capacity);
        $burndown_data->addEffortAt(0, 5);

        $this->assertEquals([100, 80, 60, 40, 20, 0], $burndown_data->getIdealEffort());
    }

    public function testIestBurndownWillIgnoreNullCapacity(): void
    {
        $capacity      = null;
        $burndown_data = new Tracker_Chart_Data_Burndown($this->date_period, $capacity);
        $burndown_data->addEffortAt(0, 5);

        $this->assertEquals([5, 4, 3, 2, 1, 0], $burndown_data->getIdealEffort());
    }

    public function testIestBurndownWillIgnoreZeroCapacity(): void
    {
        $capacity      = 0;
        $burndown_data = new Tracker_Chart_Data_Burndown($this->date_period, $capacity);
        $burndown_data->addEffortAt(0, 5);

        $this->assertEquals([5, 4, 3, 2, 1, 0], $burndown_data->getIdealEffort());
    }

    public function testItReturnsAnEmptyArrayWhenBurndownIsUnderCalculation(): void
    {
        $capacity      = 7;
        $burndown_data = new Tracker_Chart_Data_Burndown($this->date_period, $capacity);
        $burndown_data->addEffortAt(0, 5);
        $burndown_data->addEffortAt(1, 4);
        $burndown_data->addEffortAt(2, 3);
        $burndown_data->addEffortAt(3, 1);

        $burndown_data->setIsBeingCalculated(true);

        $results = json_decode($burndown_data->getJsonRepresentation());

        $expected_points = [];

        $this->assertEquals(5, $results->duration);
        $this->assertEquals(7, $results->capacity);
        $this->assertEquals($expected_points, $results->points);
    }

    public function testItReturnsBurndownDataInJson(): void
    {
        $capacity      = 7;
        $burndown_data = new Tracker_Chart_Data_Burndown($this->date_period, $capacity);
        $burndown_data->addEffortAt(0, 5);
        $burndown_data->addEffortAt(1, 4);
        $burndown_data->addEffortAt(2, 3);
        $burndown_data->addEffortAt(3, 1);

        $results = json_decode($burndown_data->getJsonRepresentation());

        $expected_points = [5, 4, 3, 1];

        $this->assertEquals(5, $results->duration);
        $this->assertEquals(7, $results->capacity);
        $this->assertEquals($expected_points, $results->points);
    }

    public function testItReturnsBurndownDataInJsonAndDealWithNullValues(): void
    {
        $capacity      = 7;
        $burndown_data = new Tracker_Chart_Data_Burndown($this->date_period, $capacity);
        $burndown_data->addEffortAt(0, 5);
        $burndown_data->addEffortAt(1, 4);
        $burndown_data->addEffortAt(2, null);
        $burndown_data->addEffortAt(3, null);

        $results = json_decode($burndown_data->getJsonRepresentation());

        $expected_points = [5, 4];

        $this->assertEquals(5, $results->duration);
        $this->assertEquals(7, $results->capacity);
        $this->assertEquals($expected_points, $results->points);
    }

    public function testItHasNoRemainingEffort(): void
    {
        $date_period   = DatePeriodWithoutWeekEnd::buildFromDuration($this->start_date, 2);
        $burndown_data = new Tracker_Chart_Data_Burndown($date_period);
        $this->assertSame([null, null, null], $burndown_data->getRemainingEffort());
    }

    public function testItReturnsValidRemainingEffortWhenOnlyAddingNull(): void
    {
        $date_period   = DatePeriodWithoutWeekEnd::buildFromDuration($this->start_date, 2);
        $burndown_data = new Tracker_Chart_Data_Burndown($date_period);
        $burndown_data->addEffortAt(0, null);
        $burndown_data->addEffortAt(1, null);
        $burndown_data->addEffortAt(2, null);
        $this->assertSame([null, null, null], $burndown_data->getRemainingEffort());
    }

    public function testItHasNoIdealBurndown(): void
    {
        $date_period   = DatePeriodWithoutWeekEnd::buildFromDuration($this->start_date, 2);
        $burndown_data = new Tracker_Chart_Data_Burndown($date_period);
        $this->assertSame([0, 0, 0], $burndown_data->getIdealEffort());
    }
}
