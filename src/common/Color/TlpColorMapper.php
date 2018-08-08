<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Color;

use ColorHelper;

class TlpColorMapper
{
    const COLOR_MAP = [
        'inca-silver'       => '#5d5d5d',
        'chrome-silver'     => '#8f8f8f',
        'firemist-silver'   => '#c5c5c5',
        'red-wine'          => '#a10202',
        'fiesta-red'        => '#f02727',
        'coral-pink'        => '#ff8a8a',
        'teddy-brown'       => '#9a600d',
        'clockwork-orange'  => '#f18e06',
        'graffiti-yellow'   => '#ffd300',
        'army-green'        => '#5f8347',
        'neon-green'        => '#6abf1d',
        'acid-green'        => '#b9e45d',
        'sherwood-green'    => '#009465',
        'ocean-turquoise'   => '#28c4a0',
        'surf-green'        => '#75e4bf',
        'deep-blue'         => '#0480bf',
        'lake-placid-blue'  => '#1ebade',
        'daphne-blue'       => '#87dbef',
        'plum-crazy'        => '#7c2db3',
        'ultra-violet'      => '#a44ee1',
        'lilac-purple'      => '#ce91fa',
        'panther-pink'      => '#c42887',
        'peggy-pink'        => '#e560b2',
        'flamingo-pink'     => '#edacd4'
    ];

    public static function getRGBColor($tlp_color_name)
    {
        if (array_key_exists($tlp_color_name, self::COLOR_MAP)) {
            return ColorHelper::HexatoRGB(self::COLOR_MAP[$tlp_color_name]);
        }

        return [ null, null, null ];
    }
}
