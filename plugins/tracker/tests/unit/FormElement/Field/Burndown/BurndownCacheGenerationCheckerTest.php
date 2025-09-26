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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\Burndown;

use DateTime;
use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use SystemEventManager;
use Tuleap\Date\DatePeriodWithOpenDays;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\BurndownCacheDateRetriever;
use Tuleap\Tracker\FormElement\ChartCachedDaysComparator;
use Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever;
use Tuleap\Tracker\FormElement\ChartConfigurationValueChecker;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedFieldDao;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ComputedFieldBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class BurndownCacheGenerationCheckerTest extends TestCase
{
    private ChartConfigurationFieldRetriever&MockObject $field_retriever;
    private SystemEventManager&MockObject $event_manager;
    private BurndownCacheGenerator&MockObject $cache_generator;
    private BurndownRemainingEffortAdderForREST&MockObject $remaining_effort_adder;
    private ComputedFieldDao&MockObject $computed_dao;
    private ChartConfigurationValueChecker&MockObject $value_checker;
    private PFUser $user;
    private Artifact $artifact;
    private BurndownCacheGenerationChecker $cache_checker;
    private BurndownCacheDateRetriever $date_retriever;

    #[\Override]
    protected function setUp(): void
    {
        $this->cache_generator        = $this->createMock(BurndownCacheGenerator::class);
        $this->event_manager          = $this->createMock(SystemEventManager::class);
        $this->field_retriever        = $this->createMock(ChartConfigurationFieldRetriever::class);
        $this->value_checker          = $this->createMock(ChartConfigurationValueChecker::class);
        $this->computed_dao           = $this->createMock(ComputedFieldDao::class);
        $this->remaining_effort_adder = $this->createMock(BurndownRemainingEffortAdderForREST::class);
        $this->date_retriever         = new BurndownCacheDateRetriever();
        $this->cache_checker          = new BurndownCacheGenerationChecker(
            new NullLogger(),
            $this->cache_generator,
            $this->event_manager,
            $this->field_retriever,
            $this->value_checker,
            $this->computed_dao,
            new ChartCachedDaysComparator(new NullLogger()),
            $this->remaining_effort_adder,
            $this->date_retriever,
        );

        $this->artifact = ArtifactTestBuilder::anArtifact(1)->build();
        $this->user     = UserTestBuilder::buildWithDefaults();
    }

    public function testNoNeedToCalculateCacheWhenUserCantReadRemainingEffortField(): void
    {
        $this->remaining_effort_adder->method('addRemainingEffortDataForREST');

        $this->value_checker->method('doesUserCanReadRemainingEffort')->with($this->artifact, $this->user)->willReturn(false);
        $this->event_manager->method('areThereMultipleEventsQueuedMatchingFirstParameter')->willReturn(false);

        $this->cache_generator->expects($this->never())->method('forceBurndownCacheGeneration');

        $start_date  = 1543404090;
        $duration    = 10;
        $date_period = DatePeriodWithOpenDays::buildFromDuration($start_date, $duration);

        $capacity = 5;
        self::assertFalse(
            $this->cache_checker->isBurndownUnderCalculationBasedOnServerTimezone(
                $this->artifact,
                $this->user,
                $date_period,
                $capacity
            )
        );
    }

    public function testNoNeedToCalculateCacheWhenNoStartDateIsDefined(): void
    {
        $this->remaining_effort_adder->method('addRemainingEffortDataForREST');

        $this->value_checker->method('doesUserCanReadRemainingEffort')->with($this->artifact, $this->user)->willReturn(true);
        $this->value_checker->method('hasStartDate')->with($this->artifact, $this->user)->willReturn(false);

        $this->event_manager->method('areThereMultipleEventsQueuedMatchingFirstParameter')->willReturn(false);

        $this->cache_generator->expects($this->never())->method('forceBurndownCacheGeneration');

        $start_date  = 1543404090;
        $duration    = 10;
        $date_period = DatePeriodWithOpenDays::buildFromDuration($start_date, $duration);

        $capacity = 5;
        self::assertFalse(
            $this->cache_checker->isBurndownUnderCalculationBasedOnServerTimezone(
                $this->artifact,
                $this->user,
                $date_period,
                $capacity
            )
        );
    }

    public function testCalculateCacheShouldBeLaunchedWhenMissingRemainingEffortAndCacheGenerationIsNotAlreadyAsked(): void
    {
        $this->value_checker->method('doesUserCanReadRemainingEffort')->with($this->artifact, $this->user)->willReturn(true);
        $this->value_checker->method('hasStartDate')->with($this->artifact, $this->user)->willReturn(true);

        $this->computed_dao->method('getCachedDays')->willReturn([]);
        $this->remaining_effort_adder->method('addRemainingEffortDataForREST');

        $remaining_effort_field = ComputedFieldBuilder::aComputedField(10)->build();
        $this->field_retriever->method('getBurndownRemainingEffortField')->willReturn($remaining_effort_field);

        $this->event_manager->method('areThereMultipleEventsQueuedMatchingFirstParameter')->willReturn(false);

        $this->cache_generator->expects($this->once())->method('forceBurndownCacheGeneration');

        $start_date  = 1543404090;
        $duration    = 10;
        $date_period = DatePeriodWithOpenDays::buildFromDuration($start_date, $duration);

        $capacity = 5;
        self::assertTrue(
            $this->cache_checker->isBurndownUnderCalculationBasedOnServerTimezone(
                $this->artifact,
                $this->user,
                $date_period,
                $capacity
            )
        );
    }

    public function testDoNoStackEventCallWhenMissingRemainingEffortAndCacheGenerationIsAlreadyAsked(): void
    {
        $start_date  = 1543404090;
        $duration    = 10;
        $date_period = DatePeriodWithOpenDays::buildFromDuration($start_date, $duration);

        $this->value_checker->method('doesUserCanReadRemainingEffort')->with($this->artifact, $this->user)->willReturn(true);
        $this->value_checker->method('hasStartDate')->with($this->artifact, $this->user)->willReturn(true);

        $this->computed_dao->method('getCachedDays')->willReturn(
            array_map(
                static fn() => ['timestamp' => 2],
                $this->date_retriever->getWorkedDaysToCacheForPeriod($date_period, new DateTime('now'))
            )
        );

        $remaining_effort_field = ComputedFieldBuilder::aComputedField(10)->build();
        $this->field_retriever->method('getBurndownRemainingEffortField')->willReturn($remaining_effort_field);

        $this->remaining_effort_adder->method('addRemainingEffortDataForREST');
        $this->event_manager->method('areThereMultipleEventsQueuedMatchingFirstParameter')->willReturn(true);
        $this->cache_generator->expects($this->never())->method('forceBurndownCacheGeneration');

        $capacity = 5;
        self::assertTrue(
            $this->cache_checker->isBurndownUnderCalculationBasedOnServerTimezone(
                $this->artifact,
                $this->user,
                $date_period,
                $capacity
            )
        );
    }
}
