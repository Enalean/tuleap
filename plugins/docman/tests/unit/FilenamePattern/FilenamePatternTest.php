<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\FilenamePattern;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class FilenamePatternTest extends TestCase
{
    public function testNoPattern(): void
    {
        $filename_pattern = new FilenamePattern('', false);

        self::assertEquals('', $filename_pattern->getPattern());
        self::assertFalse($filename_pattern->isEnforced());
        self::assertFalse($filename_pattern->isEnforcedAndNonEmpty());
    }

    public function testEnforcedPattern(): void
    {
        $filename_pattern = new FilenamePattern('stuff', true);

        self::assertEquals('stuff', $filename_pattern->getPattern());
        self::assertTrue($filename_pattern->isEnforced());
        self::assertTrue($filename_pattern->isEnforcedAndNonEmpty());
    }

    public function testEnforcedButEmptyPattern(): void
    {
        $filename_pattern = new FilenamePattern('', true);

        self::assertEquals('', $filename_pattern->getPattern());
        self::assertTrue($filename_pattern->isEnforced());
        self::assertFalse($filename_pattern->isEnforcedAndNonEmpty());
    }

    public function testUnenforcedPattern(): void
    {
        $filename_pattern = new FilenamePattern('stuff', false);

        self::assertEquals('stuff', $filename_pattern->getPattern());
        self::assertFalse($filename_pattern->isEnforced());
        self::assertFalse($filename_pattern->isEnforcedAndNonEmpty());
    }
}
