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

namespace Tuleap\SeatManagement;

use DateTimeImmutable;
use Tuleap\Option\Option;

/**
 * @psalm-immutable
 */
final readonly class License
{
    /**
     * @param Option<DateTimeImmutable> $expiration_date
     */
    private function __construct(
        public bool $is_enterprise_edition,
        public Option $expiration_date,
        public bool $has_valid_signature,
    ) {
    }

    public static function buildEnterpriseEdition(?DateTimeImmutable $expiration_date): self
    {
        return new self(true, Option::fromNullable($expiration_date), true);
    }

    public static function buildInvalidEnterpriseEdition(): self
    {
        return new self(true, Option::nothing(DateTimeImmutable::class), false);
    }

    public static function buildCommunityEdition(): self
    {
        return new self(false, Option::nothing(DateTimeImmutable::class), true);
    }
}
