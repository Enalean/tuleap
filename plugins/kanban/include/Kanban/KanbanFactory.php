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

declare(strict_types=1);

namespace Tuleap\Kanban;

use PFUser;
use TrackerFactory;

class KanbanFactory
{
    public function __construct(private readonly TrackerFactory $tracker_factory, private readonly KanbanDao $dao)
    {
    }

    /**
     * @return Kanban[]
     */
    public function getListOfKanbansForProject(PFUser $user, int $project_id): array
    {
        $rows    = $this->dao->getKanbansForProject($project_id);
        $kanbans = [];

        foreach ($rows as $kanban_data) {
            $kanban = $this->instantiateFromRow($kanban_data);
            if ($this->isUserAllowedToAccessKanban($user, $kanban)) {
                $kanbans[] = $kanban;
            }
        }

        return $kanbans;
    }

    /**
     * @throws KanbanCannotAccessException
     * @throws KanbanNotFoundException
     */
    public function getKanban(PFUser $user, int $kanban_id): Kanban
    {
        $row = $this->dao->getKanbanById($kanban_id);

        if (! $row) {
            throw new KanbanNotFoundException();
        }

        $kanban = $this->instantiateFromRow($row);
        if (! $this->isUserAllowedToAccessKanban($user, $kanban)) {
            throw new KanbanCannotAccessException();
        }

        return $kanban;
    }

    public function getKanbanForXmlImport(int $kanban_id): Kanban
    {
        $row = $this->dao->getKanbanById($kanban_id);

        if (! $row) {
            throw new KanbanNotFoundException();
        }
        return $this->instantiateFromRow($row);
    }

    /**
     * @return int[]
     */
    public function getKanbanTrackerIds(int $project_id): array
    {
        $rows               = $this->dao->getKanbansForProject($project_id);
        $kanban_tracker_ids = [];

        foreach ($rows as $kanban_data) {
            $kanban_tracker_ids[] = $kanban_data['tracker_id'];
        }

        return $kanban_tracker_ids;
    }

    public function getKanbanIdByTrackerId(int $tracker_id): ?int
    {
        $kanban = $this->getKanbanByTrackerId($tracker_id);
        if ($kanban === null) {
            return null;
        }
        return $kanban->getId();
    }

    public function getKanbanByTrackerId(int $tracker_id): ?Kanban
    {
        $row = $this->dao->getKanbanByTrackerId($tracker_id);
        if ($row === null) {
            return null;
        }
        return $this->instantiateFromRow($row);
    }

    /**
     * @param array{id: int, tracker_id: int, is_promoted: int, name: string, ...} $kanban_data
     */
    private function instantiateFromRow(array $kanban_data): Kanban
    {
        $tracker = $this->tracker_factory->getTrackerById($kanban_data['tracker_id']);
        if (! $tracker) {
            throw new KanbanNotFoundException();
        }

        return new Kanban(
            $kanban_data['id'],
            $tracker,
            (bool) $kanban_data['is_promoted'],
            $kanban_data['name']
        );
    }

    private function isUserAllowedToAccessKanban(PFUser $user, Kanban $kanban): bool
    {
        return $kanban->tracker->userCanView($user);
    }
}
