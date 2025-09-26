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

use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Chart_Data_Burndown;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedField;
use Tuleap\Date\DatePeriodWithOpenDays;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedFieldDao;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class BurndownRemainingEffortAdderForRESTTest extends TestCase
{
    private PFUser $user;
    private Artifact $artifact;
    private BurndownRemainingEffortAdderForREST $adder;
    private ChartConfigurationFieldRetriever&MockObject $field_retriever;
    private ComputedFieldDao&MockObject $computed_cache;

    #[\Override]
    protected function setUp(): void
    {
        $this->field_retriever = $this->createMock(ChartConfigurationFieldRetriever::class);
        $this->computed_cache  = $this->createMock(ComputedFieldDao::class);
        $this->adder           = new BurndownRemainingEffortAdderForREST($this->field_retriever, $this->computed_cache);

        $this->artifact = ArtifactTestBuilder::anArtifact(101)->build();
        $this->user     = UserTestBuilder::buildWithDefaults();
    }

    public function testItDoesNotDoAnyAdditionWhenBurndownDoesNotHaveARemainingEffortField(): void
    {
        $date_period = $this->createMock(DatePeriodWithOpenDays::class);
        $capacity    = 10;

        $burndown_data = new Tracker_Chart_Data_Burndown($date_period, $capacity);

        $this->field_retriever->method('getBurndownRemainingEffortField')->willReturn(null);
        $date_period->expects($this->never())->method('getStartDate');

        $this->adder->addRemainingEffortDataForREST($burndown_data, $this->artifact, $this->user);

        self::assertEmpty($burndown_data->getRemainingEffortsAtDate());
    }

    public function testItDoesNotDoAnyAdditionWhenStartDateIsInFuture(): void
    {
        $date_in_future = strtotime('+1 month');
        $capacity       = 5;
        $duration       = 20;
        $date_period    = DatePeriodWithOpenDays::buildFromDuration($date_in_future, $duration);

        $burndown_data = new Tracker_Chart_Data_Burndown($date_period, $capacity);

        $remaining_effort_field = $this->createMock(ComputedField::class);
        $remaining_effort_field->method('getId')->willReturn(1);
        $this->field_retriever->method('getBurndownRemainingEffortField')->willReturn($remaining_effort_field);

        $this->computed_cache->method('searchCachedDays')->willReturn([]);
        $remaining_effort_field->expects($this->never())->method('getComputedValue');

        $this->adder->addRemainingEffortDataForREST($burndown_data, $this->artifact, $this->user);

        self::assertEmpty($burndown_data->getRemainingEffortsAtDate());
    }

    public function testItDoesNotDoAnyAdditionWhenNoChachedDays(): void
    {
        $field_id               = 1;
        $duration               = 5;
        $old_start_date         = strtotime('-3 month');
        $remaining_effort_field = $this->createMock(ComputedField::class);

        $date_period   = DatePeriodWithOpenDays::buildFromDuration($old_start_date, 5);
        $burndown_data = new Tracker_Chart_Data_Burndown($date_period, $duration);

        $remaining_effort_field->method('getId')->willReturn($field_id);

        $this->field_retriever->method('getBurndownRemainingEffortField')->willReturn($remaining_effort_field);

        $this->computed_cache->method('searchCachedDays')->willReturn([]);

        $remaining_effort_field->expects($this->never())->method('getCachedValue');
        $remaining_effort_field->expects($this->never())->method('getComputedValue');

        $this->adder->addRemainingEffortDataForREST($burndown_data, $this->artifact, $this->user);

        self::assertEmpty($burndown_data->getRemainingEffortsAtDate());
    }

    public function testItAddCachedValuesForAlreadyPastDays(): void
    {
        $field_id               = 1;
        $duration               = 5;
        $old_start_date         = strtotime('-3 month');
        $remaining_effort_field = $this->createMock(ComputedField::class);

        $date_period   = DatePeriodWithOpenDays::buildFromDuration($old_start_date, 5);
        $burndown_data = new Tracker_Chart_Data_Burndown($date_period, $duration);

        $remaining_effort_field->method('getId')->willReturn($field_id);

        $this->field_retriever->method('getBurndownRemainingEffortField')->willReturn($remaining_effort_field);

        $this->computed_cache->method('searchCachedDays')->willReturn([
            [
                'artifact_id' => $this->artifact->getId(),
                'field_id'    => $field_id,
                'timestamp'   => strtotime('+1 day', $old_start_date),
                'value'       => 10,
            ],
            [
                'artifact_id' => $this->artifact->getId(),
                'field_id'    => $field_id,
                'timestamp'   => strtotime('+2 day', $old_start_date),
                'value'       => 10,

            ],
            [
                'artifact_id' => $this->artifact->getId(),
                'field_id'    => $field_id,
                'timestamp'   => strtotime('+3 day', $old_start_date),
                'value'       => 10,

            ],
            [
                'artifact_id' => $this->artifact->getId(),
                'field_id'    => $field_id,
                'timestamp'   => strtotime('+4 day', $old_start_date),
                'value'       => 10,

            ],
            [
                'artifact_id' => $this->artifact->getId(),
                'field_id'    => $field_id,
                'timestamp'   => strtotime('+5 day', $old_start_date),
                'value'       => 10,

            ],
            [
                'artifact_id' => $this->artifact->getId(),
                'field_id'    => $field_id,
                'timestamp'   => strtotime('+6 day', $old_start_date),
                'value'       => 10,

            ],
        ]);
        $remaining_effort_field->method('getCachedValue');
        $remaining_effort_field->expects($this->never())->method('getComputedValue');

        $this->adder->addRemainingEffortDataForREST($burndown_data, $this->artifact, $this->user);

        self::assertCount(6, $burndown_data->getRemainingEffort());
    }

    public function testItAddTodayComputedValueForTheCurrentDay(): void
    {
        $field_id               = 1;
        $duration               = 5;
        $recent_start_date      = strtotime('-3 days');
        $remaining_effort_field = $this->createMock(ComputedField::class);

        $date_period   = DatePeriodWithOpenDays::buildFromDuration($recent_start_date, 5);
        $burndown_data = new Tracker_Chart_Data_Burndown($date_period, $duration);

        $this->field_retriever->method('getBurndownRemainingEffortField')->willReturn($remaining_effort_field);
        $remaining_effort_field->method('getId')->willReturn($field_id);

        $this->computed_cache->method('searchCachedDays')->willReturn([
            [
                'artifact_id' => $this->artifact->getId(),
                'field_id'    => $field_id,
                'timestamp'   => strtotime('+1 day', $recent_start_date),
                'value'       => 10,
            ],
            [
                'artifact_id' => $this->artifact->getId(),
                'field_id'    => $field_id,
                'timestamp'   => strtotime('+2 day', $recent_start_date),
                'value'       => 10,

            ],
            [
                'artifact_id' => $this->artifact->getId(),
                'field_id'    => $field_id,
                'timestamp'   => strtotime('+3 day', $recent_start_date),
                'value'       => 10,

            ],
        ]);

        $remaining_effort_field->method('getCachedValue');
        $remaining_effort_field->expects($this->once())->method('getComputedValue');

        $this->adder->addRemainingEffortDataForREST($burndown_data, $this->artifact, $this->user);
    }
}
