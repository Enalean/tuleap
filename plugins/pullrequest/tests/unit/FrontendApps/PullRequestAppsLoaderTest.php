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

use Tuleap\Layout\ThemeVariation;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\TestLayout;
use Tuleap\Test\PHPUnit\TestCase;

final class PullRequestAppsLoaderTest extends TestCase
{
    private TestLayout $base_layout;

    protected function setUp(): void
    {
        $this->base_layout = new TestLayout(new LayoutInspector());
    }

    public function testItLoadsTheLegacyAngularApp(): void
    {
        PullRequestAppsLoader::loadPullRequestApps(
            $this->base_layout,
            PullRequestApp::LEGACY_ANGULAR_APP,
        );

        $javascript_assets = $this->base_layout->getJavascriptAssets();
        self::assertCount(2, $javascript_assets);
        self::assertStringContainsString("syntax-highlight", $javascript_assets[0]->getFileURL());
        self::assertStringContainsString("tuleap-pullrequest", $javascript_assets[1]->getFileURL());

        $css_assets = $this->base_layout->getCssAssets()->getDeduplicatedAssets();
        self::assertCount(1, $css_assets);
        self::assertStringContainsString("pull-requests-style", $css_assets[0]->getFileURL($this->createStub(ThemeVariation::class)));
    }

    public function testItLoadsTheOverviewApp(): void
    {
        PullRequestAppsLoader::loadPullRequestApps(
            $this->base_layout,
            PullRequestApp::OVERVIEW_APP,
        );

        $javascript_assets = $this->base_layout->getJavascriptAssets();
        self::assertCount(2, $javascript_assets);
        self::assertStringContainsString("syntax-highlight", $javascript_assets[0]->getFileURL());
        self::assertStringContainsString("pullrequest-overview", $javascript_assets[1]->getFileUrl());

        $css_assets = $this->base_layout->getCssAssets()->getDeduplicatedAssets();
        self::assertCount(1, $css_assets);
        self::assertStringContainsString("pull-requests-style", $css_assets[0]->getFileURL($this->createStub(ThemeVariation::class)));
    }

    public function testItLoadsTheHomePageApp(): void
    {
        PullRequestAppsLoader::loadPullRequestApps(
            $this->base_layout,
            PullRequestApp::HOMEPAGE_APP
        );

        $javascript_assets = $this->base_layout->getJavascriptAssets();
        self::assertCount(1, $javascript_assets);
        self::assertStringContainsString("pullrequest-homepage", $javascript_assets[0]->getFileUrl());

        $css_assets = $this->base_layout->getCssAssets()->getDeduplicatedAssets();
        self::assertCount(0, $css_assets);
    }
}
