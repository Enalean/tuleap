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

namespace Tuleap\Taskboard\Tracker;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class MappedFieldsCollectionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItReturnsTheMappedFieldForTracker(): void
    {
        $field = \Mockery::mock(\Tracker_FormElement_Field_Selectbox::class);

        $collection = new MappedFieldsCollection();
        $collection->put(12, $field);

        $this->assertSame($field, $collection->get(12));
    }

    public function testItThrowExceptionIfNoMappedFieldForTracker(): void
    {
        $collection = new MappedFieldsCollection();
        $collection->put(12, \Mockery::mock(\Tracker_FormElement_Field_Selectbox::class));

        $this->expectException(\OutOfBoundsException::class);

        $collection->get(42);
    }

    public function testItDeterminesIfKeyIsPartOfCollection(): void
    {
        $collection = new MappedFieldsCollection();
        $collection->put(12, \Mockery::mock(\Tracker_FormElement_Field_Selectbox::class));

        $this->assertTrue($collection->hasKey(12));
        $this->assertFalse($collection->hasKey(42));
    }
}
