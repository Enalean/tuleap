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

namespace Tuleap\Tracker\ServiceHomepage;

use Tuleap\Option\Option;
use Tuleap\Tracker\Tooltip\TooltipStatsPresenter;
use Tuleap\Tracker\Tooltip\TrackerStats;

final readonly class HomepagePresenterBuilder
{
    public function __construct(
        private \TrackerFactory $tracker_factory,
        private \Tracker_Migration_MigrationManager $migration_manager,
    ) {
    }

    public function build(\Project $project, \PFUser $user, bool $is_tracker_admin): HomepagePresenter
    {
        $trackers = $this->tracker_factory->getTrackersByGroupId((int) $project->getID());

        $tracker_presenters = [];
        foreach ($trackers as $tracker) {
            if (! $this->canTrackerBeDisplayed($tracker, $user)) {
                continue;
            }

            $tracker_stats        = $this->getStats($tracker, $user);
            $tooltip_presenter    = $tracker_stats->map(static fn($stats) => new TooltipStatsPresenter(
                $tracker->getId(),
                $tracker->hasSemanticsStatus(),
                $stats,
                $user
            ));
            $tracker_presenters[] = new HomepageTrackerPresenter($tracker, $tracker_stats, $tooltip_presenter);
        }
        return new HomepagePresenter($project, $is_tracker_admin, $tracker_presenters);
    }

    private function canTrackerBeDisplayed(\Tuleap\Tracker\Tracker $tracker, \PFUser $user): bool
    {
        return $tracker->userCanView($user) && ! $this->migration_manager->isTrackerUnderMigration($tracker);
    }

    /**
     * @return Option<TrackerStats>
     */
    private function getStats(\Tuleap\Tracker\Tracker $tracker, \PFUser $user): Option
    {
        $user_has_full_access = $tracker->userHasFullAccess($user);
        if (! $user_has_full_access) {
            return Option::nothing(TrackerStats::class);
        }
        return Option::fromNullable($tracker->getStats());
    }
}
