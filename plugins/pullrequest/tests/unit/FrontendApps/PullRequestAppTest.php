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

use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class PullRequestAppTest extends TestCase
{
    public function testItIsTheOverviewAppWhenTheRequestHasATabParameterOverview(): void
    {
        $app_to_load = PullRequestApp::fromRequest(
            HTTPRequestBuilder::get()->withParam("tab", "overview")->build()
        );

        self::assertSame(PullRequestApp::OVERVIEW_APP, $app_to_load);
    }

    public function testItIsTheHomePageAppWhenTheRequestHasATabParameterHomePage(): void
    {
        $app_to_load = PullRequestApp::fromRequest(
            HTTPRequestBuilder::get()->withParam("tab", "homepage")->build()
        );

        self::assertSame(PullRequestApp::HOMEPAGE_APP, $app_to_load);
    }

    public function testItIsTheLegacyAngularAppWhenTheRequestHasNoTabParameter(): void
    {
        $app_to_load = PullRequestApp::fromRequest(
            HTTPRequestBuilder::get()->build()
        );

        self::assertSame(PullRequestApp::LEGACY_ANGULAR_APP, $app_to_load);
    }

    public function testItIsTheLegacyAngularAppWhenTheRequestHasAWrongTabParameter(): void
    {
        $app_to_load = PullRequestApp::fromRequest(
            HTTPRequestBuilder::get()->withParam("tab", "whatever")->build()
        );

        self::assertSame(PullRequestApp::LEGACY_ANGULAR_APP, $app_to_load);
    }
}
