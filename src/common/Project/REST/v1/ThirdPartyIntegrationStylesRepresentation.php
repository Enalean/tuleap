<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Project\REST\v1;

use ThemeVariant;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\ThemeVariation;

/**
 * @psalm-immutable
 */
final class ThirdPartyIntegrationStylesRepresentation
{
    private function __construct(public string $content, public string $variant_name)
    {
    }

    public static function fromUser(\PFUser $user): self
    {
        $theme_variant_color = (new ThemeVariant())->getVariantColorForUser($user);
        $tlp_vars            = new \Tuleap\Layout\CssAssetWithDensityVariants(
            new IncludeAssets(__DIR__ . '/../../../../scripts/tlp/frontend-assets', '/assets/core/tlp'),
            'tlp-vars'
        );
        $url                 = $tlp_vars->getFileURL(new ThemeVariation($theme_variant_color, $user));
        $path                = __DIR__ . '/../../../../scripts/tlp/frontend-assets/' . substr($url, strlen('/assets/core/tlp/'));

        $css_file_content = file_get_contents($path);

        if ($css_file_content === false) {
            throw new \RuntimeException("Could not read TLP vars stylesheet at $path");
        }

        return new self($css_file_content, $theme_variant_color->getName());
    }
}
