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

namespace Tuleap\Layout;

use PFUser;
use ThemeVariantColor;

class ThemeVariation
{
    private $color;

    private $is_condensed_mode;

    public function __construct(ThemeVariantColor $color, PFUser $current_user)
    {
        $this->color             = $color;
        $this->is_condensed_mode = $current_user->getPreference(
            PFUser::PREFERENCE_DISPLAY_DENSITY
        ) === PFUser::DISPLAY_DENSITY_CONDENSED;
    }

    public function getFileColorCondensedSuffix()
    {
        $condensed_suffix = '';
        if ($this->is_condensed_mode) {
            $condensed_suffix = '-condensed';
        }
        return '-' . $this->color->getName() . $condensed_suffix;
    }
}
