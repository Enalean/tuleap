<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\TextualReport;

use PFUser;
use ThemeVariant;
use ThemeVariantColor;
use Tuleap\Layout\CssAssetWithDensityVariants;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\ThemeVariation;

class SinglePagePresenterBuilder
{
    public const HARD_LIMIT = 1000;

    /**
     * @var ArtifactsPresentersBuilder
     */
    private $presenters_builder;

    public function __construct(ArtifactsPresentersBuilder $presenters_builder)
    {
        $this->presenters_builder = $presenters_builder;
    }

    /**
     * @param array  $ordered_artifact_rows
     * @param string $server_url
     *
     * @return array
     */
    public function exportAsSinglePage(
        array $ordered_artifact_rows,
        PFUser $current_user,
        $server_url,
    ) {
        $nb_matching_artifacts = count($ordered_artifact_rows);
        $is_hard_limit_reached = $nb_matching_artifacts > self::HARD_LIMIT;

        $artifacts   = $this->presenters_builder->getArtifactsPresenters(
            $ordered_artifact_rows,
            $current_user,
            $server_url,
            self::HARD_LIMIT
        );
        $stylesheets = $this->getStylesheetsToEmbed($current_user);

        return [
            'artifacts'             => $artifacts,
            'is_hard_limit_reached' => $is_hard_limit_reached,
            'hard_limit'            => self::HARD_LIMIT,
            'nb_matching_artifacts' => $nb_matching_artifacts,
            'stylesheets'           => $stylesheets,
        ];
    }

    private function getStylesheetsToEmbed(PFUser $current_user): string
    {
        $theme_variant   = new ThemeVariant();
        $color           = ThemeVariantColor::buildFromVariant($theme_variant->getVariantForUser($current_user));
        $theme_variation = new ThemeVariation($color, $current_user);

        $assets = new IncludeAssets(
            __DIR__ . '/../../../../src/www/assets/core',
            __DIR__ . '/../../../../src/www/assets/core'
        );

        $css_assets = [
            new CssAssetWithDensityVariants($assets, 'tlp-vars'),
            new CssAssetWithoutVariantDeclinaisons($assets, 'tlp'),
            new CssAssetWithoutVariantDeclinaisons($assets, 'BurningParrot/burning-parrot'),
        ];

        $stylesheets = '';
        foreach ($css_assets as $css_asset) {
            $stylesheets .= file_get_contents($css_asset->getFileURL($theme_variation));
        }

        return $stylesheets;
    }
}
