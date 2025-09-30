<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Project;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MappingRegistryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testGetUgroupMapping(): void
    {
        $registry = new MappingRegistry([1 => 2]);

        self::assertEquals([1 => 2], $registry->getUgroupMapping());
    }

    public function testHasCustomMapping(): void
    {
        $registry = new MappingRegistry([1 => 2]);
        self::assertFalse($registry->hasCustomMapping('whatever'));

        $registry->setCustomMapping('whatever', []);
        self::assertTrue($registry->hasCustomMapping('whatever'));
    }

    public function testGetCustomMapping(): void
    {
        $registry = new MappingRegistry([1 => 2]);
        $registry->setCustomMapping('whatever', [3 => 4]);

        self::assertEquals([3 => 4], $registry->getCustomMapping('whatever'));
    }

    public function testGetCustomMappingThrowExceptionIfKeyNotFound(): void
    {
        $registry = new MappingRegistry([1 => 2]);

        $this->expectException(\RuntimeException::class);
        $registry->getCustomMapping('whatever');
    }

    public function testCustomMappingAsArrayObject(): void
    {
        $registry = new MappingRegistry([1 => 2]);
        $registry->setCustomMapping('whatever', new \ArrayObject([3 => 4]));
        $registry->getCustomMapping('whatever')[5] = 6;

        self::assertEquals([3 => 4, 5 => 6], $registry->getCustomMapping('whatever')->getArrayCopy());
    }
}
