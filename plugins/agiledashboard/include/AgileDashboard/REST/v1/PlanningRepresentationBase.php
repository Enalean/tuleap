<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\AgileDashboard\REST\v1;

use Tuleap\Project\REST\ProjectReference;
use Tuleap\Tracker\REST\TrackerReference;

/**
 * Basic representation of a planning
 *
 * @psalm-immutable
 */
readonly class PlanningRepresentationBase
{
    public const string ROUTE = 'plannings';

    public function __construct(
        public int $id,
        public string $uri,
        public string $label,
        public ProjectReference $project,
        public TrackerReference $milestone_tracker,
        public array $backlog_trackers,
        public string $milestones_uri,
    ) {
    }
}
