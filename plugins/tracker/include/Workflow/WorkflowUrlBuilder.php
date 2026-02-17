<?php
/**
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow;

use Tuleap\Tracker\Tracker;
use Workflow;

final class WorkflowUrlBuilder
{
    private static function buildUrl(Tracker $tracker, string $func): string
    {
        return \trackerPlugin::TRACKER_BASE_URL . '/?' . http_build_query([
            'tracker' => $tracker->getId(),
            'func'    => $func,
        ]);
    }

    public static function buildGlobalRulesUrl(Tracker $tracker): string
    {
        return self::buildUrl($tracker, Workflow::FUNC_ADMIN_RULES);
    }

    public static function buildFieldDependenciesUrl(Tracker $tracker): string
    {
        return self::buildUrl($tracker, Workflow::FUNC_ADMIN_DEPENDENCIES);
    }

    public static function buildTriggersUrl(Tracker $tracker): string
    {
        return self::buildUrl($tracker, Workflow::FUNC_ADMIN_CROSS_TRACKER_TRIGGERS);
    }

    public static function buildTransitionsUrl(Tracker $tracker): string
    {
        return self::buildUrl($tracker, Workflow::FUNC_ADMIN_TRANSITIONS);
    }
}
