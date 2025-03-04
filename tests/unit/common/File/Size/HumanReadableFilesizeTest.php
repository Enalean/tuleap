<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\File\Size;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class HumanReadableFilesizeTest extends TestCase
{
    public function testConvert(): void
    {
        self::assertEquals('0 B', HumanReadableFilesize::convert(0));
        self::assertEquals('1 B', HumanReadableFilesize::convert(1));
        self::assertEquals('123 B', HumanReadableFilesize::convert(123));
        self::assertEquals('1 kB', HumanReadableFilesize::convert(1234));
        self::assertEquals('12 kB', HumanReadableFilesize::convert(12345));
        self::assertEquals('120 kB', HumanReadableFilesize::convert(123456));
        self::assertEquals('1 MB', HumanReadableFilesize::convert(1234567));
        self::assertEquals('11 MB', HumanReadableFilesize::convert(12345678));
        self::assertEquals('117 MB', HumanReadableFilesize::convert(123456789));
        self::assertEquals('1 GB', HumanReadableFilesize::convert(1234567890));
        self::assertEquals('11 GB', HumanReadableFilesize::convert(12345678901));
        self::assertEquals('114 GB', HumanReadableFilesize::convert(123456789012));
        self::assertEquals('1 TB', HumanReadableFilesize::convert(1234567890123));
        self::assertEquals('11 TB', HumanReadableFilesize::convert(12345678901234));
        self::assertEquals('112 TB', HumanReadableFilesize::convert(123456789012345));
        self::assertEquals('1 PB', HumanReadableFilesize::convert(1234567890123456));
        self::assertEquals('10 PB', HumanReadableFilesize::convert(12345678901234567));
        self::assertEquals('109 PB', HumanReadableFilesize::convert(123456789012345678));
        self::assertEquals('1096 PB', HumanReadableFilesize::convert(1234567890123456789));
    }
}
