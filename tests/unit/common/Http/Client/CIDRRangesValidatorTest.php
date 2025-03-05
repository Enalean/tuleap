<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\Http\Client;

use Tuleap\Config\InvalidConfigKeyValueException;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CIDRRangesValidatorTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderValidRanges')]
    public function testValidCIDRRanges(string $range): void
    {
        $validator = CIDRRangesValidator::buildSelf();
        $this->expectNotToPerformAssertions();
        $validator->checkIsValid($range);
    }

    public static function dataProviderValidRanges(): array
    {
        return [
            'No range' => [''],
            'IPv4' => ['192.0.2.0/24'],
            'Multiple IPv4' => ['192.0.2.1/32,192.0.2.10/24'],
            'IPv6' => ['2001:db8::/32'],
            'Multiple IPv6' => ['2001:db8::1/128,2001:db8::2/128'],
            'Mixed IPv4 and IPv6' => ['192.0.2.0/24,2001:db8::1/128'],
            'All IPv4 and IPv6' => ['0.0.0.0/0,::/0'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderInvalidRanges')]
    public function testInvalidCIDRRanges(string $invalid_range): void
    {
        $validator = CIDRRangesValidator::buildSelf();
        $this->expectException(InvalidConfigKeyValueException::class);
        $validator->checkIsValid($invalid_range);
    }

    public static function dataProviderInvalidRanges(): array
    {
        return [
            'Not IPv4 range' => ['192.0.2.1'],
            'Not IPv6 range' => ['2001:db8::1'],
            'Wrong separator between ranges IPv4' => ['192.0.2.1/32, 192.0.2.10/24'],
            'IPv4 netmask too big' => ['192.0.2.1/128'],
            'IPv6 netmask too big' => ['2001:db8::1/256'],
            'Invalid IP address' => ['notanip/128'],
            'Negative netmask' => ['192.0.2.1/-1'],
        ];
    }
}
