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

    /**
     * @var ThemeVariant
     */
    private $theme_variant;

    public function __construct(ThemeVariant $theme_variant) {
        $this->theme_variant = $theme_variant;
    }

    public function getCSSFileForVariant($variant_name) {
        return $variant_name . '.css';
    }

    public function getCSSFilesForAllAvailableVariants() {
        $available_variants = $this->theme_variant->getAllowedVariants();

        $css_files = array();
        foreach ($available_variants as $variant) {
            $css_files[] = self::FULL_PATH . $variant . '.css';
        }

        return $css_files;
    }
}
