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

namespace Tuleap\Tracker\Artifact\Changeset;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_FormElement_Field_File;
use Tracker_FormElement_Field_Integer;
use Tracker_FormElement_Field_Text;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Artifact\Artifact;

class FieldsToBeSavedInSpecificOrderRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetFiles(): void
    {
        $tracker = Mockery::mock(Tracker::class);

        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);

        $text_field = Mockery::mock(Tracker_FormElement_Field_Text::class);
        $file_field = Mockery::mock(Tracker_FormElement_Field_File::class);
        $int_field = Mockery::mock(Tracker_FormElement_Field_Integer::class);

        $factory = Mockery::mock(Tracker_FormElementFactory::class);
        $factory->shouldReceive('getUsedFields')->andReturn([$text_field, $file_field, $int_field]);
        $factory->shouldReceive('isFieldAFileField')->with($text_field)->andReturn(false);
        $factory->shouldReceive('isFieldAFileField')->with($file_field)->andReturn(true);
        $factory->shouldReceive('isFieldAFileField')->with($int_field)->andReturn(false);

        $retriever = new FieldsToBeSavedInSpecificOrderRetriever($factory);
        $this->assertEquals([$file_field, $text_field, $int_field], $retriever->getFields($artifact));
    }
}
