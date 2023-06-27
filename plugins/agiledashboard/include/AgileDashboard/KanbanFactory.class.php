<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

use Tuleap\Kanban\KanbanDao;

class AgileDashboard_KanbanFactory
{
    public function __construct(private readonly TrackerFactory $tracker_factory, private readonly KanbanDao $dao)
    {
    }

    /**
     * @return AgileDashboard_Kanban[]
     */
    public function getListOfKanbansForProject(PFUser $user, $project_id): array
    {
        $rows    = $this->dao->getKanbansForProject($project_id);
        $kanbans = [];

        foreach ($rows as $kanban_data) {
            if ($this->isUserAllowedToAccessKanban($user, $kanban_data['tracker_id'])) {
                $kanbans[] = $this->instantiateFromRow($kanban_data);
            }
        }

        return $kanbans;
    }

    /**
     * @throws AgileDashboard_KanbanCannotAccessException
     * @throws AgileDashboard_KanbanNotFoundException
     */
    public function getKanban(PFUser $user, $kanban_id): AgileDashboard_Kanban
    {
        $row = $this->dao->getKanbanById($kanban_id);

        if (! $row) {
            throw new AgileDashboard_KanbanNotFoundException();
        }

        if (! $this->isUserAllowedToAccessKanban($user, $row['tracker_id'])) {
            throw new AgileDashboard_KanbanCannotAccessException();
        }

        return $this->instantiateFromRow($row);
    }

    public function getKanbanForXmlImport(int $kanban_id): AgileDashboard_Kanban
    {
        $row = $this->dao->getKanbanById($kanban_id);

        if (! $row) {
            throw new AgileDashboard_KanbanNotFoundException();
        }
        return $this->instantiateFromRow($row);
    }

    /**
     * @return int[]
     */
    public function getKanbanTrackerIds($project_id): array
    {
        $rows               = $this->dao->getKanbansForProject($project_id);
        $kanban_tracker_ids = [];

        foreach ($rows as $kanban_data) {
            $kanban_tracker_ids[] = $kanban_data['tracker_id'];
        }

        return $kanban_tracker_ids;
    }

    public function getKanbanIdByTrackerId($tracker_id): ?int
    {
        $kanban = $this->getKanbanByTrackerId($tracker_id);
        if ($kanban === null) {
            return null;
        }
        return $kanban->getId();
    }

    public function getKanbanByTrackerId(int $tracker_id): ?AgileDashboard_Kanban
    {
        $row = $this->dao->getKanbanByTrackerId($tracker_id);
        if ($row === null) {
            return null;
        }
        return $this->instantiateFromRow($row);
    }

    private function instantiateFromRow(array $kanban_data): AgileDashboard_Kanban
    {
        return new AgileDashboard_Kanban(
            $kanban_data['id'],
            $kanban_data['tracker_id'],
            $kanban_data['name']
        );
    }

    private function isUserAllowedToAccessKanban(PFUser $user, $tracker_id): bool
    {
        $tracker = $this->tracker_factory->getTrackerById($tracker_id);
        if (! $tracker) {
            throw new AgileDashboard_KanbanNotFoundException();
        }

        return $tracker->userCanView($user);
    }
}
