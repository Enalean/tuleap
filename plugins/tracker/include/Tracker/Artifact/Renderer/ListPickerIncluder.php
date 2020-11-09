<?php
/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Renderer;

use HTTPRequest;
use Tuleap\BrowserDetection\DetectedBrowser;

final class ListPickerIncluder
{
    /**
     * Feature flag to have list pickers in lieu of <select> in artifact views
     *
     * @tlp-config-key
     */
    public const FORGE_CONFIG_KEY = 'feature_flag_use_list_pickers_in_trackers_and_modals';

    public static function includeListPickerAssets(): void
    {
        if (self::isListPickerEnabledAndBrowserNotIE11()) {
            $include_assets = new \Tuleap\Layout\IncludeAssets(
                __DIR__ . '/../../../../../../src/www/assets/trackers',
                '/assets/trackers'
            );
            $GLOBALS['HTML']->includeFooterJavascriptFile($include_assets->getFileURL('list-fields.js'));
        }
    }

    public static function isListPickerEnabledAndBrowserNotIE11(): bool
    {
        return DetectedBrowser::detectFromTuleapHTTPRequest(HTTPRequest::instance())->isIE11() ? false : (bool) \ForgeConfig::get(self::FORGE_CONFIG_KEY);
    }
}
