<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\FilenamePattern;

use Tuleap\Docman\Tests\Stub\FilenamePatternRetrieverStub;

final class FilenameBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    protected function setUp(): void
    {
    }

    public function testItReturnsTheOriginalFilenameWhenThePatternIsNull(): void
    {
        $filename_builder = new FilenameBuilder(FilenamePatternRetrieverStub::buildWithNoPattern());

        $original_filename = "M2 CS.jpg";

        $update_filename = $filename_builder->buildFilename($original_filename, 101);
        self::assertSame($original_filename, $update_filename);
    }

    public function testItReturnsTheOriginalFilenameWhenThePatternIsAnEmptyString(): void
    {
        $filename_builder = new FilenameBuilder(FilenamePatternRetrieverStub::buildWithPattern(""));

        $original_filename = "M2 CS.jpg";

        $update_filename = $filename_builder->buildFilename($original_filename, 101);
        self::assertSame($original_filename, $update_filename);
    }

    public function testItReturnsThePatternAsNewFilename(): void
    {
        $filename_builder = new FilenameBuilder(FilenamePatternRetrieverStub::buildWithPattern("Mercedes"));

        $original_filename = "M2 CS.jpg";

        $update_filename = $filename_builder->buildFilename($original_filename, 101);
        self::assertSame("Mercedes", $update_filename);
    }
}
