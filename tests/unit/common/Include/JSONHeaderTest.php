<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

namespace Tuleap;


#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class JSONHeaderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testStartsWithHeaderInfo(): void
    {
        $this->assertMatchesRegularExpression('/^X-JSON:.*/', JSONHeader::getHeaderForPrototypeJS('something'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('contentProvider')]
    public function testJsonFormat($entry, string $expected): void
    {
        self::assertSame(
            'X-JSON: {"whatever":false,"msg":' . $expected . '}',
            JSONHeader::getHeaderForPrototypeJS(['whatever' => false, 'msg' => $entry])
        );
    }

    public static function contentProvider(): array
    {
        return [
            ['toto', '"toto"'],
            ['with { ( [ )->encodesTo(simple\' quote and double " quote', '"with { ( [ )->encodesTo(simple\' quote and double \\" quote"'],
            [null, 'null'],
            [123, '123'],
            [' ', '" "'],
        ];
    }
}
