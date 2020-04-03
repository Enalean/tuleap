<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
use Tracker_FormElement_Field_ComputedDao;
use Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever;

class BurndownRemainingEffortAdderForRESTTest extends TestCase
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
     * @var BurndownRemainingEffortAdderForREST
     */
    private $adder;

    /**
     * @var ChartConfigurationFieldRetriever
     */
    private $field_retriever;
    /**
     * @var Tracker_FormElement_Field_ComputedDao
     */
    private $computed_cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->field_retriever = Mockery::mock(ChartConfigurationFieldRetriever::class);
        $this->computed_cache  = Mockery::mock(Tracker_FormElement_Field_ComputedDao::class);
        $this->adder           = new BurndownRemainingEffortAdderForREST($this->field_retriever, $this->computed_cache);

        $this->artifact = Mockery::mock(Tracker_Artifact::class);
        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->user     = Mockery::mock(PFUser::class);
        $this->user->shouldReceive('toRow');

        $language = Mockery::mock(\BaseLanguage::class);
        $language->shouldReceive('getLanguageFromAcceptLanguage');
        $GLOBALS['Language'] = $language;
        $GLOBALS['Language']->shouldReceive('getText');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['Language']);
        parent::tearDown();
    }

    public function testItDoesNotDoAnyAdditionWhenBurndownDoesNotHaveARemainingEffortField()
    {
        $time_period = Mockery::mock(TimePeriodWithoutWeekEnd::class);
        $capacity    = 10;

        $burndown_data = new Tracker_Chart_Data_Burndown($time_period, $capacity);

        $this->field_retriever->shouldReceive('getBurndownRemainingEffortField')->andReturn(null);
        $time_period->shouldReceive('getStartDate')->never();

        $this->adder->addRemainingEffortDataForREST($burndown_data, $this->artifact, $this->user);

        $this->assertEmpty($burndown_data->getRemainingEffortsAtDate());
    }

    public function testItDoesNotDoAnyAdditionWhenStartDateIsInFuture()
    {
        $date_in_future = strtotime('+1 month');
        $capacity       = 5;
        $duration       = 20;
        $time_period    = TimePeriodWithoutWeekEnd::buildFromDuration($date_in_future, $duration);

        $burndown_data = new Tracker_Chart_Data_Burndown($time_period, $capacity);

        $remaining_effort_field = Mockery::mock(\Tracker_FormElement_Field_Computed::class);
        $remaining_effort_field->shouldReceive('getId')->andReturn(1);
        $this->field_retriever->shouldReceive('getBurndownRemainingEffortField')->andReturn($remaining_effort_field);

        $this->computed_cache->shouldReceive("searchCachedDays")->andReturns([]);
        $remaining_effort_field->shouldReceive("getComputedValue")->never();

        $this->adder->addRemainingEffortDataForREST($burndown_data, $this->artifact, $this->user);

        $this->assertEmpty($burndown_data->getRemainingEffortsAtDate());
    }

    public function testItDoesNotDoAnyAdditionWhenNoChachedDays()
    {
        $field_id               = 1;
        $duration               = 5;
        $old_start_date         = strtotime('-3 month');
        $remaining_effort_field = Mockery::mock(\Tracker_FormElement_Field_Computed::class);

        $time_period   = TimePeriodWithoutWeekEnd::buildFromDuration($old_start_date, 5);
        $burndown_data = new Tracker_Chart_Data_Burndown($time_period, $duration);

        $remaining_effort_field->shouldReceive('getId')->andReturn($field_id);

        $this->field_retriever->shouldReceive('getBurndownRemainingEffortField')->andReturn($remaining_effort_field);

        $this->computed_cache->shouldReceive("searchCachedDays")->andReturns([]);

        $remaining_effort_field->shouldReceive('getCachedValue')->never();
        $remaining_effort_field->shouldReceive('getComputedValue')->never();

        $this->adder->addRemainingEffortDataForREST($burndown_data, $this->artifact, $this->user);

        $this->assertEmpty($burndown_data->getRemainingEffortsAtDate());
    }

    public function testItAddCachedValuesForAlreadyPastDays()
    {
        $field_id               = 1;
        $duration               = 5;
        $old_start_date         = strtotime('-3 month');
        $remaining_effort_field = Mockery::mock(\Tracker_FormElement_Field_Computed::class);

        $time_period   = TimePeriodWithoutWeekEnd::buildFromDuration($old_start_date, 5);
        $burndown_data = new Tracker_Chart_Data_Burndown($time_period, $duration);

        $remaining_effort_field->shouldReceive('getId')->andReturn($field_id);

        $this->field_retriever->shouldReceive('getBurndownRemainingEffortField')->andReturn($remaining_effort_field);

        $this->computed_cache->shouldReceive("searchCachedDays")->andReturns(
            [
                [
                    "artifact_id" => $this->artifact->getId(),
                    "field_id"    => $field_id,
                    "timestamp"   => strtotime('+1 day', $old_start_date),
                    "value"        => 10
                ],
                [
                    "artifact_id" => $this->artifact->getId(),
                    "field_id"    => $field_id,
                    "timestamp"   => strtotime('+2 day', $old_start_date),
                    "value"       => 10

                ],
                [
                    "artifact_id" => $this->artifact->getId(),
                    "field_id"    => $field_id,
                    "timestamp"   => strtotime('+3 day', $old_start_date),
                    "value"       => 10

                ],
                [
                    "artifact_id" => $this->artifact->getId(),
                    "field_id"    => $field_id,
                    "timestamp"   => strtotime('+4 day', $old_start_date),
                    "value"       => 10

                ],
                [
                    "artifact_id" => $this->artifact->getId(),
                    "field_id"    => $field_id,
                    "timestamp"   => strtotime('+5 day', $old_start_date),
                    "value"       => 10

                ],
                [
                    "artifact_id" => $this->artifact->getId(),
                    "field_id"    => $field_id,
                    "timestamp"   => strtotime('+6 day', $old_start_date),
                    "value"       => 10

                ]
            ]
        );
        $remaining_effort_field->shouldReceive('getCachedValue');
        $remaining_effort_field->shouldReceive('getComputedValue')->never();

        $this->adder->addRemainingEffortDataForREST($burndown_data, $this->artifact, $this->user);

        $this->assertEquals(count($burndown_data->getRemainingEffort()), 6);
    }

    public function testItAddTodayComputedValueForTheCurrentDay()
    {
        $field_id               = 1;
        $duration               = 5;
        $recent_start_date      = strtotime('-3 days');
        $remaining_effort_field = Mockery::mock(\Tracker_FormElement_Field_Computed::class);

        $time_period   = TimePeriodWithoutWeekEnd::buildFromDuration($recent_start_date, 5);
        $burndown_data = new Tracker_Chart_Data_Burndown($time_period, $duration);

        $this->field_retriever->shouldReceive('getBurndownRemainingEffortField')->andReturn($remaining_effort_field);
        $remaining_effort_field->shouldReceive('getId')->andReturn($field_id);

        $this->computed_cache->shouldReceive("searchCachedDays")->andReturns(
            [
                [
                    "artifact_id" => $this->artifact->getId(),
                    "field_id"    => $field_id,
                    "timestamp"   => strtotime('+1 day', $recent_start_date),
                    "value"       => 10
                ],
                [
                    "artifact_id" => $this->artifact->getId(),
                    "field_id"    => $field_id,
                    "timestamp"   => strtotime('+2 day', $recent_start_date),
                    "value"       => 10

                ],
                [
                    "artifact_id" => $this->artifact->getId(),
                    "field_id"    => $field_id,
                    "timestamp"   => strtotime('+3 day', $recent_start_date),
                    "value"       => 10

                ]
            ]
        );

        $remaining_effort_field->shouldReceive('getCachedValue');
        $remaining_effort_field->shouldReceive('getComputedValue')->once();

        $this->adder->addRemainingEffortDataForREST($burndown_data, $this->artifact, $this->user);
    }
}
