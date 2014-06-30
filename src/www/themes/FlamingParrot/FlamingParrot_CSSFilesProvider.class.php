<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

/*
 * I retrive the CSS File regarding the FlamingParrot
 * variant used or available
 */

class FlamingParrot_CSSFilesProvider {

    const FULL_PATH = '/themes/FlamingParrot/css/';

    /** @var array */
    private $color_style_files = array(
            'FlamingParrot_Orange'       => 'style-orange.css',
            'FlamingParrot_Blue'         => 'style-blue.css',
            'FlamingParrot_BlueGrey'     => 'style-bluegrey.css',
            'FlamingParrot_DarkBeige'    => 'style-darkbeige.css',
            'FlamingParrot_DarkBlue'     => 'style-darkblue.css',
            'FlamingParrot_DarkBlueGrey' => 'style-darkbluegrey.css',
            'FlamingParrot_DarkGreen'    => 'style-darkgreen.css',
            'FlamingParrot_DarkOrange'   => 'style-darkorange.css',
            'FlamingParrot_DarkPurple'   => 'style-darkpurple.css',
            'FlamingParrot_Green'        => 'style-green.css',
            'FlamingParrot_Purple'       => 'style-purple.css',
            'FlamingParrot_Beige'        => 'style-beige.css',
    );

    /**
     * @var ThemeVariant
     */
    private $theme_variant;

    public function __construct(ThemeVariant $theme_variant) {
        $this->theme_variant = $theme_variant;
    }

    public function getCSSFileForVariant($variant_name) {
        return $this->color_style_files[$variant_name];
    }

    public function getCSSFilesForAllAvailableVariants() {
        $available_variants = $this->theme_variant->getAllowedVariants();

        $css_files = array();
        foreach ($available_variants as $variant) {
            $css_files[] = self::FULL_PATH . $this->color_style_files[$variant];
        }

        return $css_files;
    }
}
