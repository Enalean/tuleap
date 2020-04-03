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

namespace Tuleap\Tracker;

use InvalidArgumentException;

final class TrackerColor
{
    private const DEFAULT_COLOR_NAME = 'inca-silver';
    public const COLOR_NAMES         = [
        self::DEFAULT_COLOR_NAME,
        'chrome-silver',
        'firemist-silver',
        'red-wine',
        'fiesta-red',
        'coral-pink',
        'teddy-brown',
        'clockwork-orange',
        'graffiti-yellow',
        'army-green',
        'neon-green',
        'acid-green',
        'sherwood-green',
        'ocean-turquoise',
        'surf-green',
        'deep-blue',
        'lake-placid-blue',
        'daphne-blue',
        'plum-crazy',
        'ultra-violet',
        'lilac-purple',
        'panther-pink',
        'peggy-pink',
        'flamingo-pink',
    ];
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
        'flamingo_pink'
    ];

    /**
     * @var string
     */
    private $color_name;

    /**
     * @psalm-param value-of<self::COLOR_NAMES> $color_name
     */
    private function __construct(string $color_name)
    {
        $this->color_name = $color_name;
    }

    public static function fromName(string $color_name): self
    {
        if (! in_array($color_name, self::COLOR_NAMES, true)) {
            throw self::createException(self::COLOR_NAMES, $color_name);
        }

        return new self($color_name);
    }

    public static function fromNotStandardizedName(string $color_name): self
    {
        $valid_not_standardized_color_names = array_merge(self::COLOR_NAMES, self::NOT_STANDARDIZED_NAMES);

        if (! in_array($color_name, $valid_not_standardized_color_names, true)) {
            throw self::createException($valid_not_standardized_color_names, $color_name);
        }

        return self::fromName(str_replace('_', '-', $color_name));
    }

    public static function default(): self
    {
        return new self(self::DEFAULT_COLOR_NAME);
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

    public function getName(): string
    {
        return $this->color_name;
    }
}
