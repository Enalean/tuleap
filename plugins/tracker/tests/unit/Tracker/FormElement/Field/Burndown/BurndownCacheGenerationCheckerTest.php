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
use TimePeriodWithoutWeekEnd;
use Tuleap\Tracker\FormElement\ChartCachedDaysComparator;
use Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever;
use Tuleap\Tracker\FormElement\ChartConfigurationValueChecker;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedFieldDao;

class BurndownCacheGenerationCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ChartConfigurationFieldRetriever
     */
    private $field_retriever;
    /**
     * @var \SystemEvent
     */
    private $event_manager;
    /**
     * @var BurndownCacheGenerator
     */
    private $cache_generator;
    /**
     * @var BurndownRemainingEffortAdderForREST
     */
    private $remaining_effort_adder;
    /**
     * @var ChartCachedDaysComparator
     */
    private $cached_days_comparator;
    /**
     * @var ComputedFieldDao
     */
    private $computed_dao;
    /**
     * @var ChartConfigurationValueChecker
     */
    private $value_checker;
    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var \Tuleap\Tracker\Artifact\Artifact
     */
    private $artifact;

    /**
     * @var BurndownCacheGenerationChecker
     */
    private $cache_checker;

    protected function setUp(): void
    {
        parent::setUp();

        $logger = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $logger->shouldReceive('debug');
        $this->cache_generator        = Mockery::mock(BurndownCacheGenerator::class);
        $this->event_manager          = Mockery::mock(\SystemEventManager::class);
        $this->field_retriever        = Mockery::mock(ChartConfigurationFieldRetriever::class);
        $this->value_checker          = Mockery::mock(ChartConfigurationValueChecker::class);
        $this->computed_dao           = Mockery::mock(ComputedFieldDao::class);
        $this->cached_days_comparator = Mockery::mock(ChartCachedDaysComparator::class);
        $this->remaining_effort_adder = Mockery::mock(BurndownRemainingEffortAdderForREST::class);
        $this->cache_checker          = new BurndownCacheGenerationChecker(
            $logger,
            $this->cache_generator,
            $this->event_manager,
            $this->field_retriever,
            $this->value_checker,
            $this->computed_dao,
            $this->cached_days_comparator,
            $this->remaining_effort_adder
        );

        $this->artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->artifact->shouldReceive('getId')->andReturn(1);
        $this->user = Mockery::mock(\PFUser::class);
    }

    public function testNoNeedToCalculateCacheWhenUserCantReadRemainingEffortField()
    {
        $this->remaining_effort_adder->shouldReceive('addRemainingEffortDataForREST');

        $this->value_checker->shouldReceive('doesUserCanReadRemainingEffort')->withArgs([$this->artifact, $this->user])
            ->andReturn(false);
        $this->event_manager->shouldReceive('areThereMultipleEventsQueuedMatchingFirstParameter')->andReturn(false);

        $this->cache_generator->shouldReceive('forceBurndownCacheGeneration')->never();

        $start_date  = 1543404090;
        $duration    = 10;
        $time_period = TimePeriodWithoutWeekEnd::buildFromDuration($start_date, $duration);

        $capacity = 5;
        $this->assertFalse(
            $this->cache_checker->isBurndownUnderCalculationBasedOnServerTimezone(
                $this->artifact,
                $this->user,
                $time_period,
                $capacity
            )
        );
    }

    public function testNoNeedToCalculateCacheWhenNoStartDateIsDefined()
    {
        $this->remaining_effort_adder->shouldReceive('addRemainingEffortDataForREST');

        $this->value_checker->shouldReceive('doesUserCanReadRemainingEffort')->withArgs([$this->artifact, $this->user])
            ->andReturn(true);
        $this->value_checker->shouldReceive('hasStartDate')->withArgs([$this->artifact, $this->user])
            ->andReturn(false);

        $this->event_manager->shouldReceive('areThereMultipleEventsQueuedMatchingFirstParameter')->andReturn(false);

        $this->cache_generator->shouldReceive('forceBurndownCacheGeneration')->never();

        $start_date  = 1543404090;
        $duration    = 10;
        $time_period = TimePeriodWithoutWeekEnd::buildFromDuration($start_date, $duration);

        $capacity = 5;
        $this->assertFalse(
            $this->cache_checker->isBurndownUnderCalculationBasedOnServerTimezone(
                $this->artifact,
                $this->user,
                $time_period,
                $capacity
            )
        );
    }

    public function testCalculateCacheShouldBeLaunchedWhenMissingRemainingEffortAndCacheGenerationIsNotAlreadyAsked()
    {
        $this->value_checker->shouldReceive('doesUserCanReadRemainingEffort')->withArgs([$this->artifact, $this->user])
            ->andReturn(true);
        $this->value_checker->shouldReceive('hasStartDate')->withArgs([$this->artifact, $this->user])
            ->andReturn(true);

        $this->computed_dao->shouldReceive('getCachedDays');
        $this->cached_days_comparator->shouldReceive('isNumberOfCachedDaysExpected')->andReturn(false);
        $this->remaining_effort_adder->shouldReceive('addRemainingEffortDataForREST');

        $remaining_effort_field = Mockery::mock(\Tracker_FormElement_Field_Computed::class);
        $remaining_effort_field->shouldReceive('getId')->andReturn(10);
        $this->field_retriever->shouldReceive('getBurndownRemainingEffortField')->andReturn($remaining_effort_field);

        $this->event_manager->shouldReceive('areThereMultipleEventsQueuedMatchingFirstParameter')->andReturn(false);

        $this->cache_generator->shouldReceive('forceBurndownCacheGeneration')->once();

        $start_date  = 1543404090;
        $duration    = 10;
        $time_period = TimePeriodWithoutWeekEnd::buildFromDuration($start_date, $duration);

        $capacity = 5;
        $this->assertTrue(
            $this->cache_checker->isBurndownUnderCalculationBasedOnServerTimezone(
                $this->artifact,
                $this->user,
                $time_period,
                $capacity
            )
        );
    }

    public function testDoNoStackEventCallWhenMissingRemainingEffortAndCacheGenerationIsAlreadyAsked()
    {
        $this->value_checker->shouldReceive('doesUserCanReadRemainingEffort')->withArgs([$this->artifact, $this->user])
            ->andReturn(true);
        $this->value_checker->shouldReceive('hasStartDate')->withArgs([$this->artifact, $this->user])
            ->andReturn(true);

        $this->computed_dao->shouldReceive('getCachedDays');
        $this->cached_days_comparator->shouldReceive('isNumberOfCachedDaysExpected')->andReturn(true);

        $remaining_effort_field = Mockery::mock(\Tracker_FormElement_Field_Computed::class);
        $remaining_effort_field->shouldReceive('getId')->andReturn(10);
        $this->field_retriever->shouldReceive('getBurndownRemainingEffortField')->andReturn($remaining_effort_field);

        $this->remaining_effort_adder->shouldReceive('addRemainingEffortDataForREST');
        $this->event_manager->shouldReceive('areThereMultipleEventsQueuedMatchingFirstParameter')->andReturn(true);
        $this->cache_generator->shouldReceive('forceBurndownCacheGeneration')->never();

        $start_date  = 1543404090;
        $duration    = 10;
        $time_period = TimePeriodWithoutWeekEnd::buildFromDuration($start_date, $duration);

        $capacity = 5;
        $this->assertTrue(
            $this->cache_checker->isBurndownUnderCalculationBasedOnServerTimezone(
                $this->artifact,
                $this->user,
                $time_period,
                $capacity
            )
        );
    }
}
