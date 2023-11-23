<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

use Tuleap\Test\Builders\UserTestBuilder;

final class ThemeVariationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testCondensedModeThemeVariation(): void
    {
        $color = ThemeVariantColor::buildFromName('orange');
        $user  = UserTestBuilder::anActiveUser()->build();
        $user->setPreference(\PFUser::PREFERENCE_DISPLAY_DENSITY, \PFUser::DISPLAY_DENSITY_CONDENSED);

        $theme_variation = new ThemeVariation($color, $user);
        self::assertEquals('-orange', $theme_variation->getFileColorSuffix());
        self::assertEquals('-orange-condensed', $theme_variation->getFileColorCondensedSuffix());
    }

    public function testComfortableModeThemeVariation(): void
    {
        $color = ThemeVariantColor::buildFromName('orange');
        $user  = UserTestBuilder::aUser()->build();
        $user->setPreference(\PFUser::PREFERENCE_DISPLAY_DENSITY, false);

        $theme_variation = new ThemeVariation($color, $user);
        self::assertEquals('-orange', $theme_variation->getFileColorSuffix());
        self::assertEquals('-orange', $theme_variation->getFileColorCondensedSuffix());
    }
}
