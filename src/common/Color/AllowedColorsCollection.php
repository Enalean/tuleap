<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

class AllowedColorsCollection
{
    /** @var array<array{secondary: string, text: string}> */
    private $colors = [
        'inca-silver'      => ['secondary' => '#cacaca', 'text' => '#525252'],
        'chrome-silver'    => ['secondary' => '#dcdcdc', 'text' => '#5f5f5f'],
        'firemist-silver'  => ['secondary' => '#f3f3f3', 'text' => '#6f6f6f'],
        'red-wine'         => ['secondary' => '#f7a9a9', 'text' => '#842f2f'],
        'fiesta-red'       => ['secondary' => '#f9d1d1', 'text' => '#b70d0d'],
        'coral-pink'       => ['secondary' => '#fff2f2', 'text' => '#bf4747'],
        'teddy-brown'      => ['secondary' => '#e2c59b', 'text' => '#774a0a'],
        'clockwork-orange' => ['secondary' => '#ffddae', 'text' => '#945600'],
        'graffiti-yellow'  => ['secondary' => '#fff7d0', 'text' => '#8a6c00'],
        'army-green'       => ['secondary' => '#b4d49f', 'text' => '#385f1e'],
        'neon-green'       => ['secondary' => '#d8efc4', 'text' => '#137900'],
        'acid-green'       => ['secondary' => '#f3fdde', 'text' => '#567d00'],
        'sherwood-green'   => ['secondary' => '#a1dcc9', 'text' => '#006545'],
        'ocean-turquoise'  => ['secondary' => '#cbf5ea', 'text' => '#00775c'],
        'surf-green'       => ['secondary' => '#eefdf8', 'text' => '#1b805e'],
        'deep-blue'        => ['secondary' => '#acd8ef', 'text' => '#005f90'],
        'lake-placid-blue' => ['secondary' => '#d4f7ff', 'text' => '#007792'],
        'daphne-blue'      => ['secondary' => '#eaf9fd', 'text' => '#007a96'],
        'plum-crazy'       => ['secondary' => '#d2abec', 'text' => '#6a14a7'],
        'ultra-violet'     => ['secondary' => '#edd4ff', 'text' => '#8b21d6'],
        'lilac-purple'     => ['secondary' => '#f6eaff', 'text' => '#8e4cbd'],
        'panther-pink'     => ['secondary' => '#f9b8e0', 'text' => '#9a1d69'],
        'peggy-pink'       => ['secondary' => '#ffdcf2', 'text' => '#c5007a'],
    ];

    public const DEFAULT_COLOR = 'chrome-silver';

    /**
     * @return string[]
     */
    public function getColorNames()
    {
        return array_keys($this->colors);
    }

    public function getColors(): array
    {
        return $this->colors;
    }

    public function isColorAllowed($color)
    {
        return isset($this->colors[$color]);
    }
}
