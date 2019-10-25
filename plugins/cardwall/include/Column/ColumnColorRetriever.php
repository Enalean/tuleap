<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Cardwall\Column;

class ColumnColorRetriever
{
    public static function getHeaderColorNameOrRGB(array $row): string
    {
        if ($row['tlp_color_name']) {
            return $row['tlp_color_name'];
        }

        $r = $row['bg_red'];
        $g = $row['bg_green'];
        $b = $row['bg_blue'];
        if ($r !== null && $g !== null && $b !== null) {
            return "rgb($r, $g, $b)";
        }

        return \Cardwall_OnTop_Config_ColumnFactory::DEFAULT_HEADER_COLOR;
    }

    public static function getHeaderColorNameOrHex(array $row): string
    {
        if ($row['tlp_color_name']) {
            return $row['tlp_color_name'];
        }

        $r = $row['bg_red'];
        $g = $row['bg_green'];
        $b = $row['bg_blue'];
        if ($r !== null && $g !== null && $b !== null) {
            return \ColorHelper::RGBToHexa($r, $g, $b);
        }

        return '';
    }
}
