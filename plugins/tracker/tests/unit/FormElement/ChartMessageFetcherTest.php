<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElement_Chart_Field_Exception;
use Tracker_HierarchyFactory;
use Tuleap\Event\Dispatchable;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;
use Tuleap\Tracker\FormElement\Event\ExternalTrackerChartConfigurationWarningMessage;
use Tuleap\Tracker\FormElement\Field\Integer\IntegerField;
use Tuleap\Tracker\Test\Builders\ChartFieldUsageTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[DisableReturnValueGenerationForTestDoubles]
final class ChartMessageFetcherTest extends TestCase
{
    private IntegerField $field;
    private ChartConfigurationFieldRetriever&MockObject $configuration_field_retriever;
    private Tracker_HierarchyFactory&MockObject $hierarchy_factory;

    private Field\Date\DateField $start_date_field;
    private Field\Date\DateField $end_date_field;
    private IntegerField $duration_field;
    private IntegerField $capacity_field;
    private EventDispatcherStub $event_manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->hierarchy_factory             = $this->createMock(Tracker_HierarchyFactory::class);
        $this->configuration_field_retriever = $this->createMock(ChartConfigurationFieldRetriever::class);
        $this->event_manager                 = EventDispatcherStub::withIdentityCallback();

        $tracker     = TrackerTestBuilder::aTracker()->withId(123)->build();
        $this->field = IntegerFieldBuilder::anIntField(685)->inTracker($tracker)->build();

        $this->start_date_field = DateFieldBuilder::aDateField(985)->build();
        $this->end_date_field   = DateFieldBuilder::aDateField(986)->build();
        $this->duration_field   = IntegerFieldBuilder::anIntField(987)->build();
        $this->capacity_field   = IntegerFieldBuilder::anIntField(988)->build();
    }

    private function buildChartMessageFetcher(): ChartMessageFetcher
    {
        return new ChartMessageFetcher(
            $this->hierarchy_factory,
            $this->configuration_field_retriever,
            $this->event_manager,
            ProvideCurrentUserStub::buildCurrentUserByDefault(),
        );
    }

    private function assertWarningsAreRetrievedForFieldsWithNames(
        ChartFieldUsage $chart_configuration,
        string $first_field_name,
        string ...$other_field_name,
    ): void {
        $warnings     = $this->buildChartMessageFetcher()->fetchWarnings($this->field, $chart_configuration)->warnings;
        $fields_names = [$first_field_name, ...$other_field_name];

        self::assertNotEmpty($warnings);

        foreach ($fields_names as $warning_index => $field_name) {
            self::assertSame($field_name, $warnings[$warning_index]->message);
        }
    }

    public function testAWarningIsAddedWhenStartDateFieldIsMissingInTrackerWhileItsNeeded(): void
    {
        $this->configuration_field_retriever->method('getStartDateField')->willThrowException(new Tracker_FormElement_Chart_Field_Exception('start_date'));

        $this->assertWarningsAreRetrievedForFieldsWithNames(
            ChartFieldUsageTestBuilder::aChart()->usingStartDate()->build(),
            'start_date'
        );
    }

    public function testAWarningIsAddedWhenDurationAndEndDateFieldsAreMissingInTrackerWhileOneOfThemIsNeeded(): void
    {
        $this->configuration_field_retriever->method('getEndDateField')->willThrowException(new Tracker_FormElement_Chart_Field_Exception('end_date'));
        $this->configuration_field_retriever->method('getDurationField')->willThrowException(new Tracker_FormElement_Chart_Field_Exception('duration'));

        $this->assertWarningsAreRetrievedForFieldsWithNames(
            ChartFieldUsageTestBuilder::aChart()->usingDuration()->build(),
            'duration',
            'end_date'
        );
    }

    public function testNoWarningIsAddedWhenOnlyDurationFieldIsDefined(): void
    {
        $this->configuration_field_retriever->method('getEndDateField')->willThrowException(new Tracker_FormElement_Chart_Field_Exception('end_date'));
        $this->configuration_field_retriever->method('getDurationField')->willReturn($this->duration_field);

        $this->assertNoWarning(ChartFieldUsageTestBuilder::aChart()->usingDuration()->build());
    }

    public function testNoWarningIsAddedWhenOnlyEndDateFieldIsDefined(): void
    {
        $this->configuration_field_retriever->method('getEndDateField')->willReturn($this->end_date_field);
        $this->configuration_field_retriever->method('getDurationField')->willThrowException(new Tracker_FormElement_Chart_Field_Exception('duration'));

        $this->assertNoWarning(ChartFieldUsageTestBuilder::aChart()->usingDuration()->build());
    }

    public function testAWarningIsAddedWhenCapacityFieldIsMissingInTrackerWhileItsNeeded(): void
    {
        $this->configuration_field_retriever->method('getCapacityField')->willThrowException(new Tracker_FormElement_Chart_Field_Exception('capacity'));

        $this->assertWarningsAreRetrievedForFieldsWithNames(
            ChartFieldUsageTestBuilder::aChart()->usingCapacity()->build(),
            'capacity'
        );
    }

    public function testItRendersAWarningForAnyTrackerChildThatHasNoEffortField(): void
    {
        $chart_configuration = ChartFieldUsageTestBuilder::aChart()->usingRemainingEffort()->build();

        $tracker_id = 123;
        $bugs       = TrackerTestBuilder::aTracker()->withId(124)->withName('Bugs')->build();
        $chores     = TrackerTestBuilder::aTracker()->withId(125)->withName('Chores')->build();

        $this->hierarchy_factory->method('getChildren')->with($tracker_id)->willReturn([$bugs, $chores]);

        $this->configuration_field_retriever->method('doesRemainingEffortFieldExists')
            ->willReturnCallback(static fn(Tracker $tracker) => $tracker === $chores);

        $warnings = $this->buildChartMessageFetcher()->fetchWarnings($this->field, $chart_configuration)->warnings;

        self::assertCount(1, $warnings);
        self::assertStringContainsString('remaining_effort', $warnings[0]->message);

        assert($warnings[0] instanceof ChartConfigurationWarningWithLinks);

        self::assertCount(1, $warnings[0]->links);
        self::assertSame('Bugs', $warnings[0]->links[0]->label);
    }

    public function testExternalWarningsAreRetrieved(): void
    {
        $external_warning    = ChartConfigurationWarning::fromMessage('Warning message from outside tracker plugin.');
        $this->event_manager = EventDispatcherStub::withCallback(static function (Dispatchable $event) use ($external_warning) {
            if ($event instanceof ExternalTrackerChartConfigurationWarningMessage) {
                $event->warnings->addWarning($external_warning);
            }
            return $event;
        });

        $warnings = $this->buildChartMessageFetcher()->fetchWarnings($this->field, ChartFieldUsageTestBuilder::aChart()->build())->warnings;

        self::assertCount(1, $warnings);
        self::assertSame($external_warning, $warnings[0]);
    }

    public function testNoWarningWhenConfigurationIsOk(): void
    {
        $chart_configuration = ChartFieldUsageTestBuilder::aChart()
            ->usingStartDate()
            ->usingDuration()
            ->usingCapacity()
            ->usingRemainingEffort()
            ->build();

        $this->hierarchy_factory->method('getChildren')->willReturn([TrackerTestBuilder::aTracker()->build()]);
        $this->configuration_field_retriever->method('doesRemainingEffortFieldExists')->willReturnCallback(static fn() => true);
        $this->configuration_field_retriever->method('getStartDateField')->willReturn($this->start_date_field);
        $this->configuration_field_retriever->method('getDurationField')->willReturn($this->duration_field);
        $this->configuration_field_retriever->method('getCapacityField')->willReturn($this->capacity_field);

        $this->assertNoWarning($chart_configuration);
    }

    protected function assertNoWarning(ChartFieldUsage $chart_configuration): void
    {
        self::assertEmpty($this->buildChartMessageFetcher()->fetchWarnings($this->field, $chart_configuration)->warnings);
    }
}
