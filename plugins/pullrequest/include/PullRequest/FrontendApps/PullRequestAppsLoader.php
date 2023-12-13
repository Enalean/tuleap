<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\PullRequest\FrontendApps;

use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\IncludeCoreAssets;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Layout\JavascriptViteAsset;

final class PullRequestAppsLoader
{
    public static function loadPullRequestApps(
        BaseLayout $base_layout,
        PullRequestApp $app_to_load,
    ): void {
        match ($app_to_load) {
            PullRequestApp::LEGACY_ANGULAR_APP => self::includeLegacyAngularAppAssets($base_layout),
            PullRequestApp::OVERVIEW_APP => self::includeOverviewAppAssets($base_layout),
            PullRequestApp::HOMEPAGE_APP => self::includeHomepageAppAssets($base_layout),
        };
    }

    private static function includeLegacyAngularAppAssets(BaseLayout $base_layout): void
    {
        self::includeGeneralAssets($base_layout);

        $base_layout->addJavascriptAsset(
            new JavascriptAsset(
                new IncludeAssets(
                    __DIR__ . '/../../../scripts/pullrequests-app/frontend-assets',
                    '/assets/pullrequest/pullrequests-app'
                ),
                'tuleap-pullrequest.js'
            )
        );
    }

    private static function includeOverviewAppAssets(BaseLayout $base_layout): void
    {
        self::includeGeneralAssets($base_layout);

        $base_layout->addJavascriptAsset(
            new JavascriptViteAsset(
                new IncludeViteAssets(
                    __DIR__ . '/../../../scripts/pullrequest-overview/frontend-assets',
                    '/assets/pullrequest/pullrequest-overview'
                ),
                'src/index.ts'
            )
        );
    }

    private static function includeHomepageAppAssets(BaseLayout $base_layout): void
    {
        $base_layout->addJavascriptAsset(
            new JavascriptViteAsset(
                new IncludeViteAssets(
                    __DIR__ . '/../../../scripts/pullrequest-homepage/frontend-assets',
                    '/assets/pullrequest/pullrequest-homepage'
                ),
                'src/index.ts'
            )
        );
    }

    private static function includeGeneralAssets(BaseLayout $base_layout): void
    {
        $assets = new IncludeAssets(
            __DIR__ . '/../../../scripts/pullrequests-app/frontend-assets',
            '/assets/pullrequest/pullrequests-app'
        );

        $base_layout->addCssAsset(
            new CssAssetWithoutVariantDeclinaisons(
                $assets,
                'pull-requests-style'
            )
        );

        $base_layout->addJavascriptAsset(
            new JavascriptAsset(
                new IncludeCoreAssets(),
                'syntax-highlight.js'
            )
        );
    }
}
