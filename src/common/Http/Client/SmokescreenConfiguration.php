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

/**
 * @psalm-immutable
 * @see https://github.com/stripe/smokescreen/blob/7c83effc9df4daf3a00a8e7215eda906693e51f6/pkg/smokescreen/config_loader.go#L23-L52
 */
final class SmokescreenConfiguration
{
    public readonly string $ip;
    public readonly bool $allow_missing_role;

    /**
     * @param string[] $allow_ranges
     * @param string[] $deny_ranges
     */
    private function __construct(
        public readonly array $allow_ranges,
        public readonly array $deny_ranges,
    ) {
        $this->ip                 = 'localhost';
        $this->allow_missing_role = true;
    }

    public static function fromForgeConfig(): self
    {
        return new self(
            self::splitNonEmptyRanges(\ForgeConfig::get(OutboundHTTPRequestSettings::ALLOW_RANGES, '')),
            self::splitNonEmptyRanges(\ForgeConfig::get(OutboundHTTPRequestSettings::DENY_RANGES, '')),
        );
    }

    private static function splitNonEmptyRanges(string $ranges): array
    {
        return \Psl\Vec\filter(\Psl\Str\Byte\split($ranges, ','));
    }
}
