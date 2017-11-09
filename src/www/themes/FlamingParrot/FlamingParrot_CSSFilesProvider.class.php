<?php
/**
 * Copyright (c) Enalean, 2014 - 2017. All Rights Reserved.
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
 * I retrieve the CSS File regarding the FlamingParrot
 * variant used or available
 */

use Tuleap\Layout\IncludeAssets;

class FlamingParrot_CSSFilesProvider
{
    /**
     * @var ThemeVariant
     */
    private $theme_variant;
    /**
     * @var IncludeAssets
     */
    private $include_assets;

    public function __construct(ThemeVariant $theme_variant, IncludeAssets $include_assets)
    {
        $this->theme_variant  = $theme_variant;
        $this->include_assets = $include_assets;
    }

    public function getCSSFileForVariant($variant_name)
    {
        return $this->include_assets->getFileURL($variant_name . '.css');
    }

    public function getCSSFilesForAllAvailableVariants()
    {
        $available_variants = $this->theme_variant->getAllowedVariants();

        $css_files = array();
        foreach ($available_variants as $variant) {
            $css_files[] = $this->include_assets->getFileURL($variant . '.css');
        }

        return $css_files;
    }
}
