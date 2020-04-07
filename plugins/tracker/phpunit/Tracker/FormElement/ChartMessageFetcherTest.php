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

namespace Tuleap\Tracker\FormElement;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;
use UserManager;

require_once __DIR__ . '/../../bootstrap.php';

class ChartMessageFetcherTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var \Tracker
     */
    private $tracker;

    /**
     * @var ChartMessageFetcher
     */
    private $message_fetcher;

    private $field;

    private $configuration_field_retriever;

    private $hierarchy_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hierarchy_factory             = \Mockery::mock(\Tracker_HierarchyFactory::class);
        $this->configuration_field_retriever = \Mockery::mock(ChartConfigurationFieldRetriever::class);
        $user_manager                        = Mockery::mock(UserManager::class);
        $this->message_fetcher               = new ChartMessageFetcher(
            $this->hierarchy_factory,
            $this->configuration_field_retriever,
            \Mockery::spy(\EventManager::class),
            $user_manager
        );

        $this->tracker = \Mockery::mock(\Tracker::class);
        $this->field   = \Mockery::mock(\Tracker_FormElement_Field::class);

        $this->field->shouldReceive('getTracker')->andReturn($this->tracker);

        $user_manager->shouldReceive('getCurrentUser')->andReturn(Mockery::mock(PFUser::class));
    }

    public function testItDisplaysWarningsWhenFieldsAreMissingInChartConfiguration()
    {
        $chart_configuration = new ChartFieldUsage(true, true, false, false, false);

        $this->configuration_field_retriever->shouldReceive('getStartDateField')->andThrow(\Tracker_FormElement_Chart_Field_Exception::class);
        $this->configuration_field_retriever->shouldReceive('getEndDateField')->andThrow(\Tracker_FormElement_Chart_Field_Exception::class);
        $this->configuration_field_retriever->shouldReceive('getDurationField')->andThrow(\Tracker_FormElement_Chart_Field_Exception::class);

        $expected_warning = '<ul class="feedback_warning">';

        $this->assertStringContainsString(
            $expected_warning,
            $this->message_fetcher->fetchWarnings($this->field, $chart_configuration)
        );
    }

    public function testItDoesNotDisplayAnyErrorsWhenNoFieldsAreMissingInChartConfiguration()
    {
        $chart_configuration = new ChartFieldUsage(true, true, false, false, false);

        $this->mockStartDateField();
        $this->mockDurationField();

        $this->assertNull(
            $this->message_fetcher->fetchWarnings($this->field, $chart_configuration)
        );
    }

    public function testItRendersAWarningForAnyTrackerChildThatHasNoEffortField()
    {
        $chart_configuration = new ChartFieldUsage(false, false, false, false, true);

        $bugs = \Mockery::mock(\Tracker::class);
        $bugs->shouldReceive('getId')->andReturn(124);
        $bugs->shouldReceive('getName')->andReturn('Bugs');

        $chores = \Mockery::mock(\Tracker::class);
        $chores->shouldReceive('getId')->andReturn(125);
        $chores->shouldReceive('getName')->andReturn('Chores');

        $tracker_id = 123;
        $this->tracker->shouldReceive('getId')->andReturn($tracker_id);
        $this->hierarchy_factory->shouldReceive('getChildren')->with($tracker_id)->andReturn([$bugs, $chores]);

        $this->configuration_field_retriever->shouldReceive('doesRemainingEffortFieldExists')->with($bugs)->andReturn(false);
        $this->configuration_field_retriever->shouldReceive('doesRemainingEffortFieldExists')->with($chores)->andReturn(true);

        $html = $this->message_fetcher->fetchWarnings($this->field, $chart_configuration);

        $this->assertStringNotContainsString('Bugs', $html);
        $this->assertStringContainsString('Chores', $html);
    }

    private function mockStartDateField(): void
    {
        $start_date_field = \Mockery::mock(\Tracker_FormElement_Field_Date::class);

        $this->configuration_field_retriever->shouldReceive('getStartDateField')->andReturn(
            $start_date_field
        );
    }

    private function mockDurationField(): void
    {
        $duration_field = \Mockery::mock(\Tracker_FormElement_Field_Integer::class);

        $this->configuration_field_retriever->shouldReceive('getDurationField')->andReturn(
            $duration_field
        );
    }
}
