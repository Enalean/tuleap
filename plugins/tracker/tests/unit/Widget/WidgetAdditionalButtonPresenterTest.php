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

use PHPUnit\Framework\Attributes\TestWith;
use Tuleap\Dashboard\Project\ProjectDashboardController;
use Tuleap\Dashboard\User\UserDashboardController;
use Tuleap\ServerHostname;
use Tuleap\Test\Builders\ProjectTestBuilder;
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

    #[TestWith([UserDashboardController::LEGACY_DASHBOARD_TYPE])]
    #[TestWith([UserDashboardController::DASHBOARD_TYPE])]
    public function testItBuildsAnUrlForTrackerRedirectionOnUserDashboard(string $owner_type): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(17)->withName('bug')->withShortName('bug')->build();

        $widget             = $this->createMock(Widget::class);
        $widget->owner_type = $owner_type;
        $widget->expects($this->once())->method('getDashboardId')->willReturn(123);

        $presenter = new WidgetAdditionalButtonPresenter($tracker, false, $widget);

        $base_url = ServerHostname::HTTPSUrl();
        self::assertSame('Add a new bug', $presenter->new_artifact);
        self::assertSame(
            $base_url . '/plugins/tracker/?tracker=17&func=new-artifact&my-dashboard-id=123',
            $presenter->url_artifact_submit
        );
    }

    #[TestWith([ProjectDashboardController::LEGACY_DASHBOARD_TYPE])]
    #[TestWith([ProjectDashboardController::DASHBOARD_TYPE])]
    public function testItBuildsAnUrlForTrackerRedirectionOnProjectDashboard(string $owner_type): void
    {
        $project = ProjectTestBuilder::aProject()->withId(103)->withUnixName('myproject')->build();
        $tracker = TrackerTestBuilder::aTracker()->withId(17)->withName('bug')->withShortName('bug')->withProject($project)->build();

        $widget             = $this->createMock(Widget::class);
        $widget->owner_type = $owner_type;
        $widget->expects($this->once())->method('getDashboardId')->willReturn(123);

        $presenter = new WidgetAdditionalButtonPresenter($tracker, false, $widget);

        $base_url = ServerHostname::HTTPSUrl();
        self::assertSame('Add a new bug', $presenter->new_artifact);
        self::assertSame(
            $base_url . '/plugins/tracker/?tracker=17&func=new-artifact&project-dashboard-id=123',
            $presenter->url_artifact_submit
        );
    }
}
