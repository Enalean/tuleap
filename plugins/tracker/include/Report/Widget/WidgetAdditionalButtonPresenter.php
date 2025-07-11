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

namespace Tuleap\Tracker\Report\Widget;

use Tuleap\Dashboard\Project\ProjectDashboardController;
use Tuleap\Dashboard\User\UserDashboardController;

final class WidgetAdditionalButtonPresenter
{
    public string $new_artifact;
    public string $url_artifact_submit;

    public function __construct(\Tuleap\Tracker\Tracker $tracker, public bool $is_a_table_renderer, \Widget $widget)
    {
        $this->new_artifact        = sprintf(
            dgettext('tuleap-tracker', 'Add a new %s'),
            $tracker->getItemName()
        );
        $this->url_artifact_submit = \Tuleap\ServerHostname::HTTPSUrl() .
            '/plugins/tracker/?tracker=' . urlencode((string) $tracker->getId()) . '&func=new-artifact';

        if ($widget->owner_type === UserDashboardController::DASHBOARD_TYPE || $widget->owner_type === UserDashboardController::LEGACY_DASHBOARD_TYPE) {
            $this->url_artifact_submit .= '&my-dashboard-id=' . urlencode((string) $widget->getDashboardId());
        }

        if ($widget->owner_type === ProjectDashboardController::DASHBOARD_TYPE || $widget->owner_type === ProjectDashboardController::LEGACY_DASHBOARD_TYPE) {
            $this->url_artifact_submit .= '&project-dashboard-id=' . urlencode((string) $widget->getDashboardId());
        }
    }
}
