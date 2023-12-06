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

namespace Tuleap\Enalean\LicenseManager;
final class QuotaLicenseCalculator
{
    private const PERCENTAGE_QUOTA_EXCEEDING_SOON = 0.2;

    private function __construct()
    {
    }

    public static function isQuotaExceeded(int $nb_used_users, int $nb_max_users): bool
    {
        return $nb_used_users > $nb_max_users;
    }

    public static function isQuotaExceedingSoon(int $nb_used_users, int $nb_max_users): bool
    {
        if ($nb_max_users <= 0) {
            return true;
        }
        return (1 - $nb_used_users / $nb_max_users) < self::PERCENTAGE_QUOTA_EXCEEDING_SOON;
    }
}
