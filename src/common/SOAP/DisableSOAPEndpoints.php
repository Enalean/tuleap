<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\SOAP;

use Tuleap\Config\FeatureFlagConfigKey;

final class DisableSOAPEndpoints
{
    #[FeatureFlagConfigKey("Feature flag to enable the deprecated SOAP API")]
    public const FEATURE_FLAG_ENABLE_SOAP = 'enable_deprecated_soap_api';

    public static function checkIfSOAPEndpointsCanBeUsed(): void
    {
        if (\ForgeConfig::getFeatureFlag(self::FEATURE_FLAG_ENABLE_SOAP)) {
            return;
        }

        $theme_manager = new \ThemeManager(
            new \Tuleap\BurningParrotCompatiblePageDetector(
                new \Tuleap\Request\CurrentPage(),
                new \User_ForgeUserGroupPermissionsManager(new \User_ForgeUserGroupPermissionsDao()),
            ),
        );
        $theme         = $theme_manager->getBurningParrot(\UserManager::instance()->getCurrentUserWithLoggedInInformation());
        if ($theme === null) {
            throw new \RuntimeException("Could not load BurningParrot theme");
        }
        $error_renderer = new \Tuleap\Layout\ErrorRendering();
        $error_renderer->rendersError(
            $theme,
            \HTTPRequest::instance(),
            404,
            _('Not found'),
            _('The SOAP API is no more accessible, please use the REST API instead')
        );
        die();
    }
}
