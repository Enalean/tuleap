<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Config;

use Tuleap\Test\PHPUnit\TestCase;
use function PHPUnit\Framework\assertEquals;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ConfigKeyLegacyBoolTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('getSerializationTests')]
    public function testSerialization(string $expected, mixed $value): void
    {
        assertEquals($expected . PHP_EOL, (new ConfigKeyLegacyBool())->getSerializedRepresentation('key', $value));
    }

    public static function getSerializationTests(): iterable
    {
        return [
            'true with string' => [
                'expected' => '$key = \'1\';',
                'value' => '1',
            ],
            'false with string' => [
                'expected' => '$key = \'0\';',
                'value' => '0',
            ],
            'true with int' => [
                'expected' => '$key = \'1\';',
                'value' => 1,
            ],
            'false with int' => [
                'expected' => '$key = \'0\';',
                'value' => 0,
            ],
            'true with bool' => [
                'expected' => '$key = \'1\';',
                'value' => true,
            ],
            'false with bool' => [
                'expected' => '$key = \'0\';',
                'value' => false,
            ],
        ];
    }

    public function testNonBoolStringRaiseException(): void
    {
        $this->expectException(\LogicException::class);
        (new ConfigKeyLegacyBool())->getSerializedRepresentation('key', 'bar');
    }

    public function testNonBoolIntRaiseException(): void
    {
        $this->expectException(\LogicException::class);
        (new ConfigKeyLegacyBool())->getSerializedRepresentation('key', 2);
    }
}
