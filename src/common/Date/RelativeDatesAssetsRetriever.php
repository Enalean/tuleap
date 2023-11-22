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

namespace Tuleap\Date;

use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;

final class RelativeDatesAssetsRetriever
{
    private const SCRIPT_NAME = 'tlp-relative-date.js';

    public static function retrieveAssetsUrl(): string
    {
        return self::getCoreAssets()->getFileURL(self::SCRIPT_NAME);
    }

    public static function getAsJavascriptAssets(): JavascriptAsset
    {
        return new JavascriptAsset(
            self::getCoreAssets(),
            self::SCRIPT_NAME
        );
    }

    private static function getCoreAssets(): IncludeAssets
    {
        return new \Tuleap\Layout\IncludeCoreAssets();
    }
}
