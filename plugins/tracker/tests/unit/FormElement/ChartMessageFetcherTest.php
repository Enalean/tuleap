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

use EventManager;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElement_Chart_Field_Exception;
use Tracker_HierarchyFactory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;
use Tuleap\Tracker\FormElement\Field\Integer\IntegerField;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[DisableReturnValueGenerationForTestDoubles]
final class ChartMessageFetcherTest extends TestCase
{
    private ChartMessageFetcher $message_fetcher;
    private IntegerField $field;
    private ChartConfigurationFieldRetriever&MockObject $configuration_field_retriever;
    private Tracker_HierarchyFactory&MockObject $hierarchy_factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->hierarchy_factory             = $this->createMock(Tracker_HierarchyFactory::class);
        $this->configuration_field_retriever = $this->createMock(ChartConfigurationFieldRetriever::class);
        $event_manager                       = $this->createStub(EventManager::class);
        $this->message_fetcher               = new ChartMessageFetcher(
            $this->hierarchy_factory,
            $this->configuration_field_retriever,
            $event_manager,
            ProvideCurrentUserStub::buildCurrentUserByDefault(),
        );
        $event_manager->method('processEvent');

        $tracker     = TrackerTestBuilder::aTracker()->withId(123)->build();
        $this->field = IntegerFieldBuilder::anIntField(685)->inTracker($tracker)->build();
    }

    public function testItDisplaysWarningsWhenFieldsAreMissingInChartConfiguration(): void
    {
        $chart_configuration = new ChartFieldUsage(true, true, false, false, false);

        $this->configuration_field_retriever->method('getStartDateField')->willThrowException(new Tracker_FormElement_Chart_Field_Exception());
        $this->configuration_field_retriever->method('getEndDateField')->willThrowException(new Tracker_FormElement_Chart_Field_Exception());
        $this->configuration_field_retriever->method('getDurationField')->willThrowException(new Tracker_FormElement_Chart_Field_Exception());

        $expected_warning = '<ul class="feedback_warning">';

        self::assertStringContainsString(
            $expected_warning,
            $this->message_fetcher->fetchWarnings($this->field, $chart_configuration)
        );
    }

    public function testItDoesNotDisplayAnyErrorsWhenNoFieldsAreMissingInChartConfiguration(): void
    {
        $chart_configuration = new ChartFieldUsage(true, true, false, false, false);

        $start_date_field = DateFieldBuilder::aDateField(985)->build();

        $this->configuration_field_retriever->method('getStartDateField')->willReturn($start_date_field);
        $duration_field = IntegerFieldBuilder::anIntField(3543)->build();

        $this->configuration_field_retriever->method('getDurationField')->willReturn($duration_field);

        self::assertNull($this->message_fetcher->fetchWarnings($this->field, $chart_configuration));
    }

    public function testItRendersAWarningForAnyTrackerChildThatHasNoEffortField(): void
    {
        $chart_configuration = new ChartFieldUsage(false, false, false, false, true);

        $bugs   = TrackerTestBuilder::aTracker()->withId(124)->withName('Bugs')->build();
        $chores = TrackerTestBuilder::aTracker()->withId(125)->withName('Chores')->build();

        $tracker_id = 123;
        $this->hierarchy_factory->method('getChildren')->with($tracker_id)->willReturn([$bugs, $chores]);

        $this->configuration_field_retriever->method('doesRemainingEffortFieldExists')
            ->willReturnCallback(static fn(Tracker $tracker) => $tracker === $chores);

        $html = $this->message_fetcher->fetchWarnings($this->field, $chart_configuration);

        self::assertStringNotContainsString('Bugs', $html);
        self::assertStringContainsString('Chores', $html);
    }
}
