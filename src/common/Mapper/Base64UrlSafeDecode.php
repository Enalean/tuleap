<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Mapper;

use Attribute;
use CuyZ\Valinor\Mapper\AsConverter;
use Psl\Encoding\Base64\Variant;
use function Psl\Encoding\Base64\decode as base64_decode;

#[AsConverter]
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Base64UrlSafeDecode
{
    /**
     * @template T
     * @param callable(mixed): T $next
     * @return T
     */
    public function map(string $value, callable $next): mixed
    {
        $decoded = base64_decode($value, Variant::UrlSafe, false);

        return $next($decoded);
    }
}
