<?php
/**
 * Copyright (c) Enalean SAS, 2011 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet
 *
 * ForgeUpgrade is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * ForgeUpgrade is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with ForgeUpgrade. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\ForgeUpgrade;

use ArrayIterator;
use SplFileInfo;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BucketFilterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testNameCorrect(): void
    {
        $file = $this->createMock(SplFileInfo::class);
        $file->method('getPathname')->willReturn('201004231055_add_system_event_table.php');

        $filter = new BucketFilter(new ArrayIterator([$file]));
        $filter->rewind();
        $this->assertTrue($filter->valid());
    }

    public function testBadNameWrongExtension(): void
    {
        $file = $this->createMock(SplFileInfo::class);
        $file->method('getPathname')->willReturn('201004231055_add_system_event_table.pl');

        $filter = new BucketFilter(new ArrayIterator([$file]));
        $filter->rewind();
        $this->assertFalse($filter->valid());
    }

    public function testBadNameWrongSeparator(): void
    {
        $file = $this->createMock(SplFileInfo::class);
        $file->method('getPathname')->willReturn('201004231055-add_system_event_table.php');

        $filter = new BucketFilter(new ArrayIterator([$file]));
        $filter->rewind();
        $this->assertFalse($filter->valid());
    }
}
