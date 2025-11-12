<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Theme\BurningParrot;

use Event;
use EventManager;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\CssAssetWithDensityVariants;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAssetGeneric;
use Tuleap\Layout\ThemeVariation;

final readonly class BurningParrotStylesheetsBuilder
{
    /**
     * @param JavascriptAssetGeneric[] $javascript_assets
     */
    public function __construct(
        private ThemeVariation $theme_variation,
        private CssAssetCollection $css_assets,
        private array $javascript_assets,
    ) {
    }

    public function getStylesheets(): array
    {
        $tlp_assets       = new IncludeAssets(__DIR__ . '/../../../scripts/tlp/frontend-assets', '/assets/core/tlp');
        $core_assets      = new \Tuleap\Layout\IncludeCoreAssets();
        $theme_css_assets = new CssAssetCollection(
            [
                new CssAssetWithoutVariantDeclinaisons($tlp_assets, 'tlp'),
                new CssAssetWithDensityVariants($tlp_assets, 'tlp-vars'),
                new CssAssetWithoutVariantDeclinaisons($core_assets, 'BurningParrot/burning-parrot'),
                new CssAssetWithoutVariantDeclinaisons($core_assets, 'common-theme/project-sidebar'),
            ]
        );
        $all_css_assets   = $theme_css_assets->merge($this->css_assets);
        foreach ($this->javascript_assets as $javascript_asset) {
            $all_css_assets = $all_css_assets->merge($javascript_asset->getAssociatedCSSAssets());
        }

        $stylesheets = [];
        foreach ($all_css_assets->getDeduplicatedAssets() as $css_asset) {
            $stylesheets[] = $css_asset->getFileURL($this->theme_variation);
        }

        EventManager::instance()->processEvent(
            Event::BURNING_PARROT_GET_STYLESHEETS,
            [
                'stylesheets'     => &$stylesheets,
                'theme_variation' => $this->theme_variation,
            ]
        );

        return $stylesheets;
    }
}
