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

namespace Tuleap\Git\GitPHP;

use Tuleap\Test\PHPUnit\TestCase;

final class BlobDataReaderTest extends TestCase
{
    /**
     * @dataProvider provideStringInVariousEncodings
     */
    public function testGetDataStringInUTF8(string $string): void
    {
        $blob = $this->createMock(Blob::class);
        $blob->method('GetData')->willReturn($string);

        $data_reader = new BlobDataReader();

        self::assertTrue(mb_check_encoding($data_reader->getDataStringInUTF8($blob), 'UTF-8'));
    }

    /**
     * @dataProvider provideStringInVariousEncodings
     */
    public function testGetDataLinesInUTF8(string $string): void
    {
        $blob = $this->createMock(Blob::class);
        $blob->method('GetData')->willReturn($string);

        $data_reader = new BlobDataReader();

        $lines = $data_reader->getDataLinesInUTF8($blob);
        self::assertCount(2, $lines);
        foreach ($lines as $line) {
            self::assertTrue(mb_check_encoding($line, 'UTF-8'));
        }
    }

    /**
     * @dataProvider provideStringInVariousEncodings
     */
    public function testConvertToUTF8(string $string): void
    {
        $data_reader = new BlobDataReader();

        self::assertTrue(mb_check_encoding($data_reader->convertToUTF8($string), 'UTF-8'));
    }

    public static function provideStringInVariousEncodings(): iterable
    {
        $lyrics = <<<EOS
        Tes lèvres entrouvertes et puis cette blessure
        Où l'amour et la mort se mêlent "Soniador"
        EOS;

        yield 'UTF8' => [$lyrics];
        yield 'ISO-8859-1' => [mb_convert_encoding($lyrics, 'ISO-8859-1', 'UTF-8')];
    }
}
