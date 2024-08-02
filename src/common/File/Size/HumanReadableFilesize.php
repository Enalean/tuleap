<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\File\Size;

final class HumanReadableFilesize
{
    public static function convert(int $bytes): string
    {
        $display_units = [_('%d B'), _('%d kB'), _('%d MB'), _('%d GB'), _('%d TB'), _('%d PB')];

        if ($bytes > 0) {
            $unit = (int) floor(log($bytes) / log(1024));
            if ($unit < 0) {
                $unit = 0;
            } elseif ($unit > 5) {
                $unit = 5;
            }
            $displayed_size = round($bytes / (1024 ** floor($unit)), 2);
        } else {
            $unit           = 0;
            $displayed_size = 0;
        }

        return sprintf($display_units[$unit], $displayed_size);
    }
}
