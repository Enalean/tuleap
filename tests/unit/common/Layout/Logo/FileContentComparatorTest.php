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

namespace Tuleap\Layout\Logo;

class FileContentComparatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testDoesFilesHaveTheSameContent(): void
    {
        $comparator = new FileContentComparator();

        self::assertTrue($comparator->doesFilesHaveTheSameContent(
            [
                hash_file('sha256', __DIR__ . "/../../../../../src/www/themes/common/images/organization_logo.png"),
                hash_file('sha256', __DIR__ . "/../../../../../src/www/themes/BurningParrot/images/organization_logo_small.png"),
            ],
            __DIR__ . "/../../../../../src/www/themes/common/images/organization_logo.png",
        ));
        self::assertFalse($comparator->doesFilesHaveTheSameContent(
            [
                hash_file('sha256', __DIR__ . "/../../../../../src/www/themes/common/images/organization_logo.png"),
            ],
            __DIR__ . "/../../../../../src/www/themes/BurningParrot/images/organization_logo_small.png",
        ));
    }

    public function testRaiseExceptionIfTargetPathDoesNotExist(): void
    {
        $comparator = new FileContentComparator();

        self::expectException(\RuntimeException::class);

        $comparator->doesFilesHaveTheSameContent([], "does not exists");
    }
}
