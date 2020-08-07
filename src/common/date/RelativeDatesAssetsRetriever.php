<?php
/**
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

namespace Tuleap\date;

use Tuleap\BrowserDetection\DetectedBrowser;
use Tuleap\Layout\IncludeAssets;

class RelativeDatesAssetsRetriever
{
    public static function retrieveAssetsUrl(): string
    {
        $core_assets = new IncludeAssets(__DIR__ . '/../../www/assets/core', '/assets/core');
        $detected_browser = DetectedBrowser::detectFromTuleapHTTPRequest(\HTTPRequest::instance());

        if ($detected_browser->isEdgeLegacy() || $detected_browser->isIE11()) {
            return $core_assets->getFileURL('tlp-relative-date-polyfills.js');
        }
        return $core_assets->getFileURL('tlp-relative-date.js');
    }

    public static function includeAssetsInSnippet(): void
    {
        $core_assets = new IncludeAssets(
            __DIR__ . '/../../www/assets/core',
            '/assets/core'
        );
        $detected_browser = DetectedBrowser::detectFromTuleapHTTPRequest(\HTTPRequest::instance());
        if ($detected_browser->isEdgeLegacy() || $detected_browser->isIE11()) {
            echo $core_assets->getHTMLSnippet('tlp-relative-date-polyfills.js');
        } else {
            echo $core_assets->getHTMLSnippet('tlp-relative-date.js');
        }
    }
}
