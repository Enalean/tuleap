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

namespace Tuleap\Color;

use InvalidArgumentException;

enum ColorName: string
{
    case INCA_SILVER      = 'inca-silver';
    case CHROME_SILVER    = 'chrome-silver';
    case FIREMIST_SILVER  = 'firemist-silver';
    case RED_WINE         = 'red-wine';
    case FIESTA_RED       = 'fiesta-red';
    case CORAL_PINK       = 'coral-pink';
    case TEDDY_BROWN      = 'teddy-brown';
    case CLOCKWORK_ORANGE = 'clockwork-orange';
    case GRAFFITI_YELLOW  = 'graffiti-yellow';
    case ARMY_GREEN       = 'army-green';
    case NEON_GREEN       = 'neon-green';
    case ACID_GREEN       = 'acid-green';
    case SHERWOOD_GREEN   = 'sherwood-green';
    case OCEAN_TURQUOISE  = 'ocean-turquoise';
    case SURF_GREEN       = 'surf-green';
    case DEEP_BLUE        = 'deep-blue';
    case LAKE_PLACID_BLUE = 'lake-placid-blue';
    case DAPHNE_BLUE      = 'daphne-blue';
    case PLUM_CRAZY       = 'plum-crazy';
    case ULTRA_VIOLET     = 'ultra-violet';
    case LILAC_PURPLE     = 'lilac-purple';
    case PANTHER_PINK     = 'panther-pink';
    case PEGGY_PINK       = 'peggy-pink';
    case FLAMINGO_PINK    = 'flamingo-pink';

    private const NOT_STANDARDIZED_NAMES = [
        'inca_silver',
        'chrome_silver',
        'teddy_brown',
        'red_wine',
        'fiesta_red',
        'clockwork_orange',
        'acid_green',
        'army_green',
        'sherwood_green',
        'ocean_turquoise',
        'daphne_blue',
        'lake_placid_blue',
        'deep_blue',
        'plum_crazy',
        'peggy_pink',
        'flamingo_pink',
    ];

    public static function fromName(string $color_name): self
    {
        $valid_color = self::tryFrom($color_name);
        if ($valid_color === null) {
            throw self::createException(self::listValues(), $color_name);
        }
        return $valid_color;
    }

    public static function fromNotStandardizedName(string $color_name): self
    {
        $valid_not_standardized_color_names = array_merge(self::listValues(), self::NOT_STANDARDIZED_NAMES);

        if (! in_array($color_name, $valid_not_standardized_color_names, true)) {
            throw self::createException($valid_not_standardized_color_names, $color_name);
        }

        return self::fromName(str_replace('_', '-', $color_name));
    }

    public static function default(): self
    {
        return self::INCA_SILVER;
    }

    /**
     * @param string[] $valid_colors
     */
    private static function createException(array $valid_colors, string $given_color_name): InvalidArgumentException
    {
        return new InvalidArgumentException(
            sprintf(
                'Color %s is not an element of the valid colors: %s',
                $given_color_name,
                implode(', ', $valid_colors)
            )
        );
    }

    /**
     * @return non-empty-list<string>
     */
    public static function listValues(): array
    {
        return array_map(static fn(self $color) => $color->value, self::cases());
    }
}
