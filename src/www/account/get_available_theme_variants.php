<?php
/**
 * Copyright (c) Enalean, 2014 - 2017. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

use Tuleap\Layout\IncludeAssets;

require_once __DIR__ . '/../include/pre.php';

$available_theme_variants = array(
    'selected' => false,
    'values'   => array()
);

if ($request->get('theme') === 'FlamingParrot') {
    require_once '../themes/FlamingParrot/FlamingParrot_CSSFilesProvider.class.php';

    $theme_variant                      = new ThemeVariant();
    $core_flaming_parrot_include_assets = new IncludeAssets(
        ForgeConfig::get('tuleap_dir') . '/src/www/themes/FlamingParrot/assets',
        '/themes/FlamingParrot/assets'
    );

    $css_file_selector = new FlamingParrot_CSSFilesProvider($theme_variant, $core_flaming_parrot_include_assets);

    $available_theme_variants['selected']  = $theme_variant->getVariantForUser($request->getCurrentUser());
    $available_theme_variants['values']    = $theme_variant->getAllowedVariants();
    $available_theme_variants['css_files'] = $css_file_selector->getCSSFilesForAllAvailableVariants();
}

$GLOBALS['Response']->sendJSON($available_theme_variants);
