<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\Burndown;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use TimePeriodWithoutWeekEnd;
use Tracker_Artifact;
use Tracker_Chart_Data_Burndown;
use Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever;

class BurndownRemainingEffortAdderTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    /**
     * @var PFUser
     */
    private $user;
    /**
     * @var Tracker_Artifact
     */
    private $artifact;
    /**
     * @var BurndownRemainingEffortAdder
     */
    private $adder;

    /**
     * @var ChartConfigurationFieldRetriever
     */
    private $field_retriever;

    protected function setUp()
    {
        parent::setUp();

        $this->field_retriever = Mockery::mock(ChartConfigurationFieldRetriever::class);
        $this->adder           = new BurndownRemainingEffortAdder($this->field_retriever);

        $this->artifact = Mockery::mock(Tracker_Artifact::class);
        $this->user     = Mockery::mock(PFUser::class);
        $this->user->shouldReceive('toRow');

        $language = Mockery::mock(\BaseLanguage::class);
        $language->shouldReceive('getLanguageFromAcceptLanguage');
        $GLOBALS['Language'] = $language;
        $GLOBALS['Language']->shouldReceive('getText');
    }

    protected function tearDown()
    {
        unset($GLOBALS['Language']);
        parent::tearDown();
    }

    public function testItDoesNotDoAnyAdditionWhenBurndownDoesNotHaveARemainingEffortField()
    {
        $time_period   = Mockery::mock(TimePeriodWithoutWeekEnd::class);
        $burndown_data = new Tracker_Chart_Data_Burndown($time_period);

        $this->field_retriever->shouldReceive('getBurndownRemainingEffortField')->andReturn(null);
        $time_period->shouldReceive('getStartDate')->never();

        $this->adder->addRemainingEffortData($burndown_data, $time_period, $this->artifact, $this->user);
    }

    public function testItDoesNotDoAnyAdditionWhenStartDateIsInFuture()
    {
        $time_period   = Mockery::mock(TimePeriodWithoutWeekEnd::class);
        $burndown_data = new Tracker_Chart_Data_Burndown($time_period);

        $remaining_effort_field = Mockery::mock(\Tracker_FormElement_Field_Computed::class);
        $this->field_retriever->shouldReceive('getBurndownRemainingEffortField')->andReturn($remaining_effort_field);

        $date_in_future = strtotime('+1 month');
        $time_period->shouldReceive('getStartDate')->andReturn($date_in_future);

        $time_period->shouldReceive('getDuration')->never();

        $this->adder->addRemainingEffortData($burndown_data, $time_period, $this->artifact, $this->user);
    }

    public function testItAddCachedValuesForAlreadyPastDays()
    {
        $remaining_effort_field = Mockery::mock(\Tracker_FormElement_Field_Computed::class);
        $this->field_retriever->shouldReceive('getBurndownRemainingEffortField')->andReturn($remaining_effort_field);

        $old_start_date = strtotime('-3 month');
        $time_period    = new TimePeriodWithoutWeekEnd($old_start_date, 5);
        $burndown_data  = new Tracker_Chart_Data_Burndown($time_period);

        $remaining_effort_field->shouldReceive('getCachedValue');
        $remaining_effort_field->shouldReceive('getComputedValue')->never();

        $this->adder->addRemainingEffortData($burndown_data, $time_period, $this->artifact, $this->user);

        $this->assertEquals(count($burndown_data->getRemainingEffort()), 6);
    }

    public function testItAddTodayComputedValueForTheCurrentDay()
    {
        $remaining_effort_field = Mockery::mock(\Tracker_FormElement_Field_Computed::class);
        $this->field_retriever->shouldReceive('getBurndownRemainingEffortField')->andReturn($remaining_effort_field);

        $recent_start_date = strtotime('-3 days');
        $time_period       = new TimePeriodWithoutWeekEnd($recent_start_date, 5);
        $burndown_data     = new Tracker_Chart_Data_Burndown($time_period);

        $remaining_effort_field->shouldReceive('getCachedValue');
        $remaining_effort_field->shouldReceive('getComputedValue')->once();

        $this->adder->addRemainingEffortData($burndown_data, $time_period, $this->artifact, $this->user);
    }
}
