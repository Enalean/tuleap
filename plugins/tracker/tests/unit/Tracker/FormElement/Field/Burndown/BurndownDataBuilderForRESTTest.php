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

use DateTime;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Date\DatePeriodWithoutWeekEnd;
use Tuleap\REST\JsonCast;
use Tuleap\TimezoneRetriever;
use Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever;
use Tuleap\Tracker\FormElement\ChartConfigurationValueRetriever;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedFieldDao;

class BurndownDataBuilderForRESTTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var string
     */
    private $original_timezone;

    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var \Tuleap\Tracker\Artifact\Artifact
     */
    private $artifact;

    /**
     * @var BurndownDataBuilderForREST
     */
    private $burndown_data_builder_for_d3;
    /**
     * @var ComputedFieldDao
     */
    private $computed_cache;
    /**
     * @var BurndownCommonDataBuilder
     */
    private $common_data_builder;
    /**
     * @var int
     */
    private $filed_id;


    protected function setUp(): void
    {
        parent::setUp();

        $timezone_retriever      = new TimezoneRetriever();
        $this->original_timezone = $timezone_retriever::getServerTimezone();

        $logger = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $logger->shouldReceive("debug");
        $logger->shouldReceive("info");

        $field_retriever = Mockery::mock(ChartConfigurationFieldRetriever::class);
        $field_retriever->shouldReceive('doesCapacityFieldExist')->andReturn(false);

        $field = Mockery::mock(\Tracker_FormElement_Field_Computed::class);
        $field_retriever->shouldReceive('getBurndownRemainingEffortField')->andReturn($field);
        $field->shouldReceive('getCachedValue')->andReturn(1);
        $this->filed_id = 10;
        $field->shouldReceive('getId')->andReturn($this->filed_id);

        $cache_checker = Mockery::mock(BurndownCacheGenerationChecker::class);
        $cache_checker->shouldReceive('isBurndownUnderCalculationBasedOnServerTimezone')->andReturn(false);

        $this->computed_cache               = Mockery::mock(ComputedFieldDao::class);
        $this->common_data_builder          = new BurndownCommonDataBuilder(
            $logger,
            $field_retriever,
            Mockery::mock(ChartConfigurationValueRetriever::class),
            $cache_checker
        );
        $this->burndown_data_builder_for_d3 = new BurndownDataBuilderForREST(
            $logger,
            new BurndownRemainingEffortAdderForREST($field_retriever, $this->computed_cache),
            $this->common_data_builder
        );

        $this->artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->artifact->shouldReceive('getId')->andReturn(101);
        $this->artifact->shouldReceive('getTracker')->andReturn(Mockery::mock(\Tracker::class));
        $this->user = Mockery::mock(\PFUser::class);
        $this->user->shouldReceive("toRow");
        $this->user->shouldReceive("isAnonymous")->andReturn(false);

        $language = Mockery::mock(\BaseLanguage::class);
        $language->shouldReceive('getLanguageFromAcceptLanguage');
        $GLOBALS['Language'] = $language;
        $GLOBALS['Language']->shouldReceive('getText');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->original_timezone);
        unset($GLOBALS['Language']);
        parent::tearDown();
    }

    public function testStartDateDoesNotShiftForUsersLocatedInUTCNegative()
    {
        $this->user->shouldReceive("getTimezone")->andReturn('America/Los_Angeles');

        $start_date = strtotime('2018-11-01');
        $duration   = 5;

        $date_period = DatePeriodWithoutWeekEnd::buildFromDuration($start_date, $duration);

        $this->computed_cache->shouldReceive("searchCachedDays")->andReturns([]);

        $user_burndown_data = $this->burndown_data_builder_for_d3->build($this->artifact, $this->user, $date_period);

        $shifted_start_date = 1541026800;
        $this->assertEquals($user_burndown_data->getDatePeriod()->getStartDate(), $shifted_start_date);
    }

    public function testStartDateDoesNotShiftForUsersLocatedInUTCPositive()
    {
        $this->user->shouldReceive("getTimezone")->andReturn('Asia/Tokyo');

        $start_date = strtotime('2018-11-01');
        $duration   = 5;

        $date_period = DatePeriodWithoutWeekEnd::buildFromDuration($start_date, $duration);

        $this->computed_cache->shouldReceive("searchCachedDays")->andReturns([]);

        $user_burndown_data = $this->burndown_data_builder_for_d3->build($this->artifact, $this->user, $date_period);

        $shifted_start_date = 1541026800;
        $this->assertEquals($user_burndown_data->getDatePeriod()->getStartDate(), $shifted_start_date);
    }

    public function testRemainingEffortAreNotShiftedUsersLocatedInUTCNegative()
    {
        $this->user->shouldReceive("getTimezone")->andReturn('America/Los_Angeles');

        $start_date = strtotime('2018-11-01');
        $second_day = strtotime('2018-11-02');
        $third_day  = strtotime('2018-11-03');

        $duration = 2;

        $this->computed_cache->shouldReceive("searchCachedDays")->andReturns(
            [
                [
                    "artifact_id" => $this->artifact->getId(),
                    "field_id"    => $this->filed_id,
                    "timestamp"   => $start_date,
                    "value"       => 10,
                ], [
                    "artifact_id" => $this->artifact->getId(),
                    "field_id"    => $this->filed_id,
                    "timestamp"   => $second_day,
                    "value"       => 10,
                ], [
                    "artifact_id" => $this->artifact->getId(),
                    "field_id"    => $this->filed_id,
                    "timestamp"   => $third_day,
                    "value"       => 10,
                ],
            ]
        );

        $date_period = DatePeriodWithoutWeekEnd::buildFromDuration($start_date, $duration);

        $user_burndown_data = $this->burndown_data_builder_for_d3->build($this->artifact, $this->user, $date_period);

        $this->assertEquals($user_burndown_data->getRESTRepresentation()->points_with_date[0]->date, JsonCast::toDate($start_date));
        $this->assertEquals($user_burndown_data->getRESTRepresentation()->points_with_date[1]->date, JsonCast::toDate($second_day));
        $this->assertEquals($user_burndown_data->getRESTRepresentation()->points_with_date[2]->date, JsonCast::toDate($third_day));
    }

    public function testRemainingEffortAreNotShiftedUsersLocatedInUTCPositive()
    {
        $this->user->shouldReceive("getTimezone")->andReturn('Asia/Tokyo');

        $start_date = strtotime('2018-11-01');
        $second_day = strtotime('2018-11-02');
        $third_day  = strtotime('2018-11-03');

        $this->computed_cache->shouldReceive("searchCachedDays")->andReturns(
            [
                [
                    "artifact_id" => $this->artifact->getId(),
                    "field_id"    => $this->filed_id,
                    "timestamp"   => $start_date,
                    "value"       => 10,
                ], [
                    "artifact_id" => $this->artifact->getId(),
                    "field_id"    => $this->filed_id,
                    "timestamp"   => $second_day,
                    "value"       => 10,
                ], [
                    "artifact_id" => $this->artifact->getId(),
                    "field_id"    => $this->filed_id,
                    "timestamp"   => $third_day,
                    "value"       => 10,
                ],
            ]
        );

        $duration = 2;

        $date_period = DatePeriodWithoutWeekEnd::buildFromDuration($start_date, $duration);

        $user_burndown_data = $this->burndown_data_builder_for_d3->build($this->artifact, $this->user, $date_period);

        $this->assertEquals($user_burndown_data->getRESTRepresentation()->points_with_date[0]->date, JsonCast::toDate($start_date));
        $this->assertEquals($user_burndown_data->getRESTRepresentation()->points_with_date[1]->date, JsonCast::toDate($second_day));
        $this->assertEquals($user_burndown_data->getRESTRepresentation()->points_with_date[2]->date, JsonCast::toDate($third_day));
    }

    public function testItReturnsAnEmptyArrayWhenTimePeriodIsInFuture()
    {
        $this->user->shouldReceive("getTimezone")->andReturn('Europe/London');

        $this->computed_cache->shouldReceive("searchCachedDays")->andReturns([]);

        $duration = 2;

        $start_date  = new DateTime('+1d');
        $date_period = DatePeriodWithoutWeekEnd::buildFromDuration($start_date->getTimestamp(), $duration);

        $user_burndown_data = $this->burndown_data_builder_for_d3->build($this->artifact, $this->user, $date_period);
        $this->assertSame([0 => null], $user_burndown_data->getRemainingEffort());
    }
}
