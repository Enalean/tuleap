<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\User\Account\Appearance;

use ThemeVariantColor;

class ThemeColorPresenterBuilder
{
    /**
     * @var \ThemeVariant
     */
    private $theme_variant;

    public function __construct(\ThemeVariant $theme_variant)
    {
        $this->theme_variant = $theme_variant;
    }

    /**
     * @return ThemeColorPresenter[]
     */
    public function getColorPresenterCollection(\PFUser $user): array
    {
        $user_variant = $this->theme_variant->getVariantForUser($user);

        $presenters = [];
        foreach ($this->theme_variant->getAllowedVariants() as $variant) {
            $color        = ThemeVariantColor::buildFromVariant($variant);
            $is_selected  = $user_variant === $variant;
            $presenters[] = new ThemeColorPresenter($color, $is_selected);
        }

        usort($presenters, static function (ThemeColorPresenter $a, ThemeColorPresenter $b): int {
            return strnatcasecmp($a->text, $b->text);
        });

        return $presenters;
    }
}
