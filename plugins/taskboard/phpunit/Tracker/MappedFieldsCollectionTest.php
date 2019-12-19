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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class MappedFieldsCollectionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItReturnsTheMappedFieldForTracker(): void
    {
        $field = \Mockery::mock(\Tracker_FormElement_Field_Selectbox::class);

        $collection = new MappedFieldsCollection();
        $tracker    = Mockery::mock(\Tracker::class)->shouldReceive(['getId' => 12])->getMock();
        $collection->put($tracker, $field);

        $this->assertSame($field, $collection->get($tracker));
    }

    public function testItThrowExceptionIfNoMappedFieldForTracker(): void
    {
        $collection      = new MappedFieldsCollection();
        $tracker         = Mockery::mock(\Tracker::class)->shouldReceive(['getId' => 12])->getMock();
        $another_tracker = Mockery::mock(\Tracker::class)->shouldReceive(['getId' => 42])->getMock();
        $collection->put($tracker, \Mockery::mock(\Tracker_FormElement_Field_Selectbox::class));

        $this->expectException(\OutOfBoundsException::class);

        $collection->get($another_tracker);
    }

    public function testItDeterminesIfKeyIsPartOfCollection(): void
    {
        $collection      = new MappedFieldsCollection();
        $tracker         = Mockery::mock(\Tracker::class)->shouldReceive(['getId' => 12])->getMock();
        $another_tracker = Mockery::mock(\Tracker::class)->shouldReceive(['getId' => 42])->getMock();
        $collection->put($tracker, \Mockery::mock(\Tracker_FormElement_Field_Selectbox::class));

        $this->assertTrue($collection->hasKey($tracker));
        $this->assertFalse($collection->hasKey($another_tracker));
    }
}
