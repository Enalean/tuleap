<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class AgileDashboard_KanbanItemManager
{

    /**
     * @var AgileDashboard_KanbanItemDao
     */
    private $item_dao;

    public function __construct(AgileDashboard_KanbanItemDao $item_dao)
    {
        $this->item_dao = $item_dao;
    }

    public function isKanbanItemInBacklog(Tracker_Artifact $artifact)
    {
        $row = $this->item_dao->isKanbanItemInBacklog($artifact->getTrackerId(), $artifact->getId())->getRow();

        if (! $row) {
            return false;
        }

        return true;
    }

    public function isKanbanItemInArchive(Tracker_Artifact $artifact)
    {
        $row = $this->item_dao->isKanbanItemInArchive($artifact->getTrackerId(), $artifact->getId())->getRow();

        if (! $row) {
            return false;
        }

        return true;
    }

    public function getColumnIdOfKanbanItem(Tracker_Artifact $artifact)
    {
        $row = $this->item_dao->getColumnIdOfKanbanItem($artifact->getTrackerId(), $artifact->getId())->getRow();

        if (! $row) {
            return null;
        }

        return (int) $row['bindvalue_id'];
    }

    public function getKanbanItemIndexInBacklog(Tracker_Artifact $artifact)
    {
        $column_item_ids = array();
        foreach ($this->item_dao->getKanbanBacklogItemIds($artifact->getTrackerId()) as $row) {
            array_push($column_item_ids, $row['id']);
        }
        return array_search($artifact->getId(), $column_item_ids);
    }

    public function getKanbanItemIndexInArchive(Tracker_Artifact $artifact)
    {
        $column_item_ids = array();
        foreach ($this->item_dao->getKanbanArchiveItemIds($artifact->getTrackerId()) as $row) {
            array_push($column_item_ids, $row['id']);
        }
        return array_search($artifact->getId(), $column_item_ids);
    }

    public function getKanbanItemIndexInColumn(Tracker_Artifact $artifact, $column)
    {
        $column_item_ids = array();
        foreach ($this->item_dao->getItemsInColumn($artifact->getTrackerId(), $column) as $row) {
            array_push($column_item_ids, $row['id']);
        }
        return array_search($artifact->getId(), $column_item_ids);
    }

    public function getIndexOfKanbanItem(Tracker_Artifact $artifact, $column)
    {
        if ($column === 'backlog') {
            return $this->getKanbanItemIndexInBacklog($artifact);
        } elseif ($column === 'archive') {
            return $this->getKanbanItemIndexInArchive($artifact);
        } else {
            return $this->getKanbanItemIndexInColumn($artifact, $column);
        }
    }
}
