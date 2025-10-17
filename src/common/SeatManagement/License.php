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
        public DateTimeImmutable $start_date,
        public Option $expiration_date,
        public array $restrictions,
        public bool $has_valid_signature,
        public LicenseKind $license_kind,
    ) {
    }

    public static function buildEnterpriseEdition(LicenseContent $license_content): self
    {
        return new self(
            true,
            $license_content->nbf,
            Option::fromNullable($license_content->exp),
            $license_content->restrictions,
            true,
            $license_content->license_information !== null
                ? LicenseKind::fromKind($license_content->license_information->kind)
                : LicenseKind::default(),
        );
    }

    public static function buildInfiniteEnterpriseEdition(): self
    {
        return new self(
            true,
            new DateTimeImmutable(),
            Option::nothing(DateTimeImmutable::class),
            [],
            true,
            LicenseKind::default(),
        );
    }

    public static function buildInvalidEnterpriseEdition(): self
    {
        return new self(true, new DateTimeImmutable(), Option::nothing(DateTimeImmutable::class), [], false, LicenseKind::default());
    }

    public static function buildCommunityEdition(): self
    {
        return new self(false, new DateTimeImmutable(), Option::nothing(DateTimeImmutable::class), [], true, LicenseKind::default());
    }
}
