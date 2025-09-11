<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query\Advanced;

use Tuleap\CrossTracker\Widget\ProjectCrossTrackerWidget;
use Tuleap\CrossTracker\Widget\RetrieveCrossTrackerWidget;
use Tuleap\CrossTracker\Widget\UserCrossTrackerWidget;

final readonly class WidgetInProjectChecker
{
    public function __construct(private RetrieveCrossTrackerWidget $cross_tracker_widget_retriever)
    {
    }

    public function isWidgetInProjectDashboard(int $widget_id): bool
    {
        $widget_option = $this->cross_tracker_widget_retriever->retrieveWidgetById($widget_id);

        return $widget_option->match(
            fn(ProjectCrossTrackerWidget|UserCrossTrackerWidget $widget) => $widget instanceof ProjectCrossTrackerWidget,
            fn() => false
        );
    }
}
