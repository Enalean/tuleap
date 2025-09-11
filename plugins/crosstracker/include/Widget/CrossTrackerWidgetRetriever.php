<?php
/**
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


namespace Tuleap\CrossTracker\Widget;

use Tuleap\Option\Option;

/**
 * psalm-immutable
 */
final class CrossTrackerWidgetRetriever implements RetrieveCrossTrackerWidget
{
    private array $cache;
    public function __construct(public readonly CrossTrackerWidgetDao $dao)
    {
        $this->cache = [];
    }

    /**
     * @return Option<ProjectCrossTrackerWidget>|Option<UserCrossTrackerWidget>
     */
    #[\Override]
    public function retrieveWidgetById(int $widget_id): Option
    {
        if (isset($this->cache[$widget_id])) {
            return $this->cache[$widget_id];
        }

        $this->cache[$widget_id] = $this->dao->searchCrossTrackerWidgetDashboardById($widget_id);

        return $this->cache[$widget_id];
    }
}
