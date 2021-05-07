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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\Field;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldRetrievalException;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class DescriptionFieldAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var DescriptionFieldAdapter
     */
    private $adapter;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_FormElementFactory
     */
    private $semantic_description_factory;

    protected function setUp(): void
    {
        $this->semantic_description_factory = \Mockery::mock(\Tracker_Semantic_DescriptionFactory::class);
        $this->adapter                      = new DescriptionFieldAdapter($this->semantic_description_factory);
    }

    public function testItThrowsWhenNoDescriptionIsFound(): void
    {
        $semantic_description = \Mockery::mock(\Tracker_Semantic_Description::class);
        $semantic_description->shouldReceive('getField')->andReturnNull();
        $source_tracker = new ProgramTracker(TrackerTestBuilder::aTracker()->withId(123)->build());
        $this->semantic_description_factory->shouldReceive('getByTracker')->with($source_tracker->getFullTracker())->andReturn($semantic_description);

        $this->expectException(FieldRetrievalException::class);
        $this->adapter->build($source_tracker);
    }

    public function testItBuildDescriptionFieldData(): void
    {
        $source_tracker       = new ProgramTracker(TrackerTestBuilder::aTracker()->withId(123)->build());
        $field                = new \Tracker_FormElement_Field_Text(
            1,
            $source_tracker->getTrackerId(),
            null,
            "description",
            "Description",
            "",
            true,
            null,
            true,
            true,
            1
        );
        $semantic_description = \Mockery::mock(\Tracker_Semantic_Description::class);
        $semantic_description->shouldReceive('getField')->andReturn($field);
        $this->semantic_description_factory->shouldReceive('getByTracker')->with($source_tracker->getFullTracker())->andReturn(
            $semantic_description
        );

        $field_description_data = new Field($field);

        $this->assertEquals($field_description_data, $this->adapter->build($source_tracker));
    }
}
