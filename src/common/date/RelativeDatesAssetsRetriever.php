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
use Tuleap\Layout\JavascriptAsset;

final class RelativeDatesAssetsRetriever
{
    public static function retrieveAssetsUrl(): string
    {
        return self::getCoreAssets()->getFileURL(self::getScriptVersionDependingOfBrowser());
    }

    public static function includeAssetsInSnippet(): void
    {
        echo self::getCoreAssets()->getHTMLSnippet(self::getScriptVersionDependingOfBrowser());
    }

    public static function getAsJavascriptAssets(): JavascriptAsset
    {
        return new JavascriptAsset(
            self::getCoreAssets(),
            self::getScriptVersionDependingOfBrowser()
        );
    }

    private static function getScriptVersionDependingOfBrowser(): string
    {
        $detected_browser = DetectedBrowser::detectFromTuleapHTTPRequest(\HTTPRequest::instance());
        if ($detected_browser->isEdgeLegacy() || $detected_browser->isIE11()) {
            return 'tlp-relative-date-polyfills.js';
        }

        return 'tlp-relative-date.js';
    }

    private static function getCoreAssets(): IncludeAssets
    {
        return new IncludeAssets(__DIR__ . '/../../www/assets/core', '/assets/core');
    }
}
