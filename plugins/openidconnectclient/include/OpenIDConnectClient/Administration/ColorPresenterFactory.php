<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Administration;

use Tuleap\OpenIDConnectClient\Provider\Provider;

class ColorPresenterFactory
{

    private $available_colors = [
        'inca_silver',
        'chrome_silver',
        'fiesta_red',
        'teddy_brown',
        'clockwork_orange',
        'red_wine',
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

    public function getColorsPresenters()
    {
        $colors_presenters = [];

        foreach ($this->available_colors as $color) {
            $colors_presenters[] = new ColorPresenter($color, false);
        }

        return $colors_presenters;
    }

    public function getColorsPresentersForProvider(Provider $provider)
    {
        $colors_presenters = [];

        foreach ($this->available_colors as $color) {
            $is_color_selected = false;

            if ($provider->getColor() === $color) {
                $is_color_selected = true;
            }

            $colors_presenters[] = new ColorPresenter($color, $is_color_selected);
        }

        return $colors_presenters;
    }
}
