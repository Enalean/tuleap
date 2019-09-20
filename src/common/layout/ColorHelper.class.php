<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

/**
 * ColorHelper
 * All the color manupilation related functions
 */
class ColorHelper
{

    /** @return string like '#efdabc' */
    public static function RGBToHexa($r, $g, $b)
    {
        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }

    /** @return string like '#efdabc' from a string like 'rgb(123,12,1)' */
    public static function CssRGBToHexa($rgb)
    {
        preg_match_all('/\d{1,3}/', $rgb, $out);
        return self::RGBToHexa($out[0][0], $out[0][1], $out[0][2]);
    }

    /** @return array like {232, 123, 312} */
    public static function HexatoRGB($hex)
    {
        $delta = strlen($hex) == 4 ? 1 : 2;
        return array(
            hexdec(substr($hex, 1 + 0 * $delta, $delta)),
            hexdec(substr($hex, 1 + 1 * $delta, $delta)),
            hexdec(substr($hex, 1 + 2 * $delta, $delta)),
        );
    }
}
