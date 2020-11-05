<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_Semantic_Title;
use Tuleap\ScaledAgile\Adapter\Program\TitleFieldAdapter;
use Tuleap\ScaledAgile\Adapter\TrackerDataAdapter;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TitleFieldAdapterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var TitleFieldAdapter
     */
    private $adapter;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_Semantic_TitleFactory
     */
    private $semantic_title_factory;

    protected function setUp(): void
    {
        $this->semantic_title_factory = \Mockery::mock(\Tracker_Semantic_TitleFactory::class);
        $this->adapter                = new TitleFieldAdapter($this->semantic_title_factory);
    }

    public function testItThrowsWhenNoTitleIsFound(): void
    {
        $source_tracker = TrackerDataAdapter::build(TrackerTestBuilder::aTracker()->withId(123)->build());
        $semantic_title = new Tracker_Semantic_Title($source_tracker->getFullTracker());
        $this->semantic_title_factory->shouldReceive('getByTracker')->with($source_tracker->getFullTracker())->andReturn($semantic_title);

        $this->expectException(FieldRetrievalException::class);
        $this->adapter->build($source_tracker);
    }

    public function testItThrowsWhenTitleIsNotAString(): void
    {
        $source_tracker = TrackerDataAdapter::build(TrackerTestBuilder::aTracker()->withId(123)->build());
        $field          = new \Tracker_FormElement_Field_Text(
            1,
            $source_tracker->getTrackerId(),
            null,
            "title",
            "Title",
            "",
            true,
            null,
            true,
            true,
            1
        );

        $source_tracker = TrackerDataAdapter::build(TrackerTestBuilder::aTracker()->withId(123)->build());
        $semantic_title = \Mockery::mock(Tracker_Semantic_Title::class);
        $semantic_title->shouldReceive('getField')->andReturn($field);
        $this->semantic_title_factory->shouldReceive('getByTracker')->with($source_tracker->getFullTracker())->andReturn(
            $semantic_title
        );

        $this->expectException(TitleFieldHasIncorrectTypeException::class);
        $this->adapter->build($source_tracker);
    }

    public function testItBuildTitleField(): void
    {
        $source_tracker = TrackerDataAdapter::build(TrackerTestBuilder::aTracker()->withId(123)->build());
        $field          = new \Tracker_FormElement_Field_String(
            1,
            $source_tracker->getTrackerId(),
            null,
            "title",
            "Title",
            "",
            true,
            null,
            true,
            true,
            1
        );

        $source_tracker = TrackerDataAdapter::build(TrackerTestBuilder::aTracker()->withId(123)->build());
        $semantic_title = \Mockery::mock(Tracker_Semantic_Title::class);
        $semantic_title->shouldReceive('getField')->andReturn($field);
        $this->semantic_title_factory->shouldReceive('getByTracker')->with($source_tracker->getFullTracker())->andReturn(
            $semantic_title
        );

        $title_field_data = new FieldData($field);

        $this->assertEquals($title_field_data, $this->adapter->build($source_tracker));
    }
}
