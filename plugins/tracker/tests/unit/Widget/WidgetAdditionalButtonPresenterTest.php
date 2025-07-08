<?php
/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Widget;

use Tuleap\Dashboard\User\UserDashboardController;
use Tuleap\ServerHostname;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Report\Widget\WidgetAdditionalButtonPresenter;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Widget;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WidgetAdditionalButtonPresenterTest extends TestCase
{
    public function testItBuildsAnUrlForAddingArtifactInMyDashboard(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(42)->withName('Task')->withShortName('Task')->build();

        $widget             = $this->createMock(Widget::class);
        $widget->owner_type = UserDashboardController::DASHBOARD_TYPE;
        $widget->expects($this->once())->method('getDashboardId')->willReturn(123);

        $presenter = new WidgetAdditionalButtonPresenter($tracker, true, $widget);

        $base_url = ServerHostname::HTTPSUrl();
        self::assertSame('Add a new Task', $presenter->new_artifact);
        self::assertSame(
            $base_url . '/plugins/tracker/?tracker=42&func=new-artifact&my-dashboard-id=123',
            $presenter->url_artifact_submit
        );
    }

    public function testItBuildsAnUrlForRegularTrackerRedirection(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(17)->withName('bug')->withShortName('bug')->build();

        $widget             = $this->createMock(Widget::class);
        $widget->owner_type = UserDashboardController::LEGACY_DASHBOARD_TYPE;
        $widget->expects($this->once())->method('getDashboardId')->willReturn(123);

        $presenter = new WidgetAdditionalButtonPresenter($tracker, false, $widget);

        $base_url = ServerHostname::HTTPSUrl();
        self::assertSame('Add a new bug', $presenter->new_artifact);
        self::assertSame(
            $base_url . '/plugins/tracker/?tracker=17&func=new-artifact&my-dashboard-id=123',
            $presenter->url_artifact_submit
        );
    }

    public function testConstructWithLegacyDashboard(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(8)->withName('epic')->withShortName('epic')->build();

        $widget             = $this->createMock(Widget::class);
        $widget->owner_type = UserDashboardController::LEGACY_DASHBOARD_TYPE;
        $widget->expects($this->once())->method('getDashboardId')->willReturn(456);

        $presenter = new WidgetAdditionalButtonPresenter($tracker, true, $widget);

        $base_url = ServerHostname::HTTPSUrl();
        self::assertSame('Add a new epic', $presenter->new_artifact);
        self::assertSame(
            $base_url . '/plugins/tracker/?tracker=8&func=new-artifact&my-dashboard-id=456',
            $presenter->url_artifact_submit
        );
    }
}
