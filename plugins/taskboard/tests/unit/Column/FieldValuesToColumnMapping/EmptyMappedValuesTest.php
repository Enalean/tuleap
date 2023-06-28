<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Column\FieldValuesToColumnMapping;

final class EmptyMappedValuesTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private EmptyMappedValues $empty_mapped_values;

    protected function setUp(): void
    {
        $this->empty_mapped_values = new EmptyMappedValues();
    }

    public function testGetValueIdsReturnsEmptyArray(): void
    {
        self::assertSame(0, count($this->empty_mapped_values->getValueIds()));
    }

    public function testIsEmptyReturnsTrue(): void
    {
        self::assertTrue($this->empty_mapped_values->isEmpty());
    }

    public function testGetFirstValueThrowsRuntimeException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->empty_mapped_values->getFirstValue();
    }

    public function testRemoveValueDoesNothing(): void
    {
        $this->empty_mapped_values->removeValue(12);
        self::assertEquals(new EmptyMappedValues(), $this->empty_mapped_values);
    }
}
