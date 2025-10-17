<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

enum LicenseKind: string
{
    case EXPERT    = 'expert';
    case TCP       = 'tcp';
    case MY_TULEAP = 'mytuleap';
    case PARTNER   = 'partner';

    public static function fromKind(string $kind_name): self
    {
        $valid_kind = self::tryFrom($kind_name);
        if ($valid_kind === null) {
            return self::default();
        }
        return $valid_kind;
    }

    public static function default(): self
    {
        return self::EXPERT;
    }
}
