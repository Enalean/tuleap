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

class ColorPresenterFactory
{

    /**
     * @var AllowedColorsCollection
     */
    private $allowed_colors;

    public function __construct(AllowedColorsCollection $allowed_colors)
    {
        $this->allowed_colors = $allowed_colors;
    }

    public function getColorsPresenters($current_color)
    {
        $colors_presenters = [];

        foreach ($this->allowed_colors->getColorNames() as $color) {
            $is_color_selected = false;

            if ($current_color === $color) {
                $is_color_selected = true;
            }

            $colors_presenters[] = new ColorPresenter($color, $is_color_selected);
        }

        return $colors_presenters;
    }
}
