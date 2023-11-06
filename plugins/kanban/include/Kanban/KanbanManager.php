<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Kanban;

use PFUser;
use Tracker;
use TrackerFactory;

class KanbanManager
{
    public function __construct(
        private readonly KanbanDao $dao,
        private readonly TrackerFactory $tracker_factory,
    ) {
    }

    public function doesKanbanExistForTracker(Tracker $tracker): bool
    {
        return $this->dao->getKanbanByTrackerId($tracker->getId()) !== null;
    }

    public function createKanban(string $kanban_name, bool $is_promoted, int $tracker_id): int
    {
        return $this->dao->create($kanban_name, $is_promoted, $tracker_id);
    }

    public function duplicateKanbans(array $tracker_mapping, array $field_mapping, array $report_mapping): void
    {
        $this->dao->duplicateKanbans($tracker_mapping, $field_mapping, $report_mapping);
    }

    /**
     * @return list<array{id: int, name: string, used: bool}>
     */
    public function getTrackersWithKanbanUsage(int $project_id, PFUser $user): array
    {
        $trackers     = [];
        $all_trackers = $this->tracker_factory->getTrackersByProjectIdUserCanView($project_id, $user);

        foreach ($all_trackers as $tracker) {
            $tracker_representation         = [];
            $tracker_representation['id']   = $tracker->getId();
            $tracker_representation['name'] = $tracker->getName();

            if ($this->doesKanbanExistForTracker($tracker)) {
                $tracker_representation['used'] = true;
                $trackers[]                     = $tracker_representation;
                continue;
            }

            $tracker_representation['used'] = false;
            $trackers[]                     = $tracker_representation;
        }

        return $trackers;
    }
}
