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
use Tuleap\Config\ValueValidator;
use Tuleap\NeverThrow\Fault;
use Tuleap\Option\Option;

final class CIDRRangesValidator implements ValueValidator
{
    private function __construct()
    {
    }

    #[\Override]
    public static function buildSelf(): ValueValidator
    {
        return new self();
    }

    #[\Override]
    public function checkIsValid(string $value): void
    {
        $ranges = \Psl\Vec\filter(\Psl\Str\Byte\split($value, ','));
        foreach ($ranges as $range) {
            $this->validateCIDR($range)->apply(
                static function (Fault $fault): never {
                    throw new InvalidConfigKeyValueException((string) $fault);
                }
            );
        }
    }

    /**
     * @psalm-return Option<Fault>
     */
    private function validateCIDR(string $possible_cidr): Option
    {
        $cidr_parts = \Psl\Str\Byte\split($possible_cidr, '/', 2);

        if (! isset($cidr_parts[1]) || ! ctype_digit($cidr_parts[1])) {
            return Option::fromValue(Fault::fromMessage(sprintf('%s is not a valid CIDR notation', $possible_cidr)));
        }

        $ip_address = $cidr_parts[0] ?? '';
        $netmask    = (int) $cidr_parts[1];

        if ($netmask <= 32 && filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return Option::nothing(Fault::class);
        }

        if ($netmask <= 128 && filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return Option::nothing(Fault::class);
        }

        return Option::fromValue(Fault::fromMessage(sprintf('%s is not a valid CIDR notation (malformed IP address or range too big)', $possible_cidr)));
    }
}
