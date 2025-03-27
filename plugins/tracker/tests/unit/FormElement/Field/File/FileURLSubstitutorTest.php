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

namespace Tuleap\Tracker\FormElement\Field\File;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Test\PHPUnit\TestCase;

#[DisableReturnValueGenerationForTestDoubles]
final class FileURLSubstitutorTest extends TestCase
{
    public function testEmptyString(): void
    {
        self::assertEquals(
            '',
            (new FileURLSubstitutor())->substituteURLsInHTML('', new CreatedFileURLMapping())
        );
    }

    public function testEmptyMapping(): void
    {
        self::assertEquals(
            '<img src="/path/to/file.png">',
            (new FileURLSubstitutor())->substituteURLsInHTML(
                '<img src="/path/to/file.png">',
                new CreatedFileURLMapping()
            )
        );
    }

    public function testSubstitution(): void
    {
        $url_mapping = new CreatedFileURLMapping();
        $url_mapping->add('/path/to/file1.png', '/new/path/to/file1.png');
        $url_mapping->add('/path/to/file2.png', '/new/path/to/file2.png');

        self::assertEquals(
            '<ul><li><img src="/new/path/to/file1.png"></li><li><img src="/new/path/to/file2.png"></li></ul>',
            (new FileURLSubstitutor())->substituteURLsInHTML(
                '<ul><li><img src="/path/to/file1.png"></li><li><img src="/path/to/file2.png"></li></ul>',
                $url_mapping
            )
        );
    }

    public function testNoSubstitutionForUnknownPath(): void
    {
        $url_mapping = new CreatedFileURLMapping();
        $url_mapping->add('dummy', 'dummy');

        self::assertEquals(
            '<ul><li><img src="/path/to/file1.png"></li><li><img src="/path/to/file2.png"></li></ul>',
            (new FileURLSubstitutor())->substituteURLsInHTML(
                '<ul><li><img src="/path/to/file1.png"></li><li><img src="/path/to/file2.png"></li></ul>',
                $url_mapping
            )
        );
    }

    public function testNoSubstitutionForInvalidHTML(): void
    {
        $url_mapping = $this->createMock(CreatedFileURLMapping::class);
        $url_mapping->expects($this->once())->method('isEmpty')->willReturn(false);
        $url_mapping->expects(self::never())->method('get')->with('/path/to/file1.png');

        self::assertEquals(
            '<p><img src="/path/to/file1.png"',
            (new FileURLSubstitutor())->substituteURLsInHTML(
                '<p><img src="/path/to/file1.png"',
                $url_mapping
            )
        );
    }
}
