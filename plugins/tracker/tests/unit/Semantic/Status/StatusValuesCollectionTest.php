<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Status;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class StatusValuesCollectionTest extends TestCase
{
    private StatusValuesCollection $status_values_collection;

    #[\Override]
    protected function setUp(): void
    {
        $this->status_values_collection = new StatusValuesCollection([111, 112, 113, 114]);
    }

    public function testStatusValuesCollectionGetters(): void
    {
        $this->assertContains(111, $this->status_values_collection->getValueIds());
        $this->assertContains(112, $this->status_values_collection->getValueIds());
        $this->assertContains(113, $this->status_values_collection->getValueIds());
        $this->assertContains(114, $this->status_values_collection->getValueIds());
        $this->assertEquals(111, $this->status_values_collection->getFirstValue());
    }

    public function testStatusValuesCollectionRemoveValue(): void
    {
        $this->status_values_collection->removeValue(111);
        $this->assertNotContains(111, $this->status_values_collection->getValueIds());
        $this->assertContains(112, $this->status_values_collection->getValueIds());
        $this->assertContains(113, $this->status_values_collection->getValueIds());
        $this->assertContains(114, $this->status_values_collection->getValueIds());
        $this->assertEquals(112, $this->status_values_collection->getFirstValue());
    }
}
