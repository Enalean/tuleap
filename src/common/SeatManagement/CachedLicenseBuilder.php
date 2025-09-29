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

use Override;

final class CachedLicenseBuilder implements BuildLicense
{
    private static ?self $instance   = null;
    private ?License $license_cached = null;

    public function __construct(private readonly BuildLicense $license_builder)
    {
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self(new LicenseBuilder(new PublicKeyPresenceChecker()));
        }

        return self::$instance;
    }

    #[Override]
    public function build(): License
    {
        if ($this->license_cached === null) {
            $this->license_cached = $this->license_builder->build();
        }

        return $this->license_cached;
    }
}
