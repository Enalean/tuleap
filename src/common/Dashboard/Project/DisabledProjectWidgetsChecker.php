<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Dashboard\Project;

use Tuleap\Dashboard\Dashboard;
use Widget;

class DisabledProjectWidgetsChecker
{
    /**
     * @var DisabledProjectWidgetsDao
     */
    private $dao;

    public function __construct(DisabledProjectWidgetsDao $dao)
    {
        $this->dao = $dao;
    }

    public function checkWidgetIsDisabledFromDashboard(Widget $widget, Dashboard $dashboard): bool
    {
        if (get_class($dashboard) !== ProjectDashboard::class) {
            return false;
        }

        return $this->dao->isWidgetDisabled((string) $widget->getId());
    }

    public function isWidgetDisabled(Widget $widget, string $dashboard_type): bool
    {
        if (
            $dashboard_type !== ProjectDashboardController::DASHBOARD_TYPE &&
            $dashboard_type !== ProjectDashboardController::LEGACY_DASHBOARD_TYPE
        ) {
            return false;
        }

        return $this->dao->isWidgetDisabled((string) $widget->getId());
    }
}
