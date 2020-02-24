<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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
class AgileDashboard_KanbanManager
{

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var AgileDashboard_KanbanDao
     */
    private $dao;

    public function __construct(
        AgileDashboard_KanbanDao $dao,
        TrackerFactory $tracker_factory
    ) {
        $this->dao               = $dao;
        $this->tracker_factory   = $tracker_factory;
    }

    public function doesKanbanExistForTracker(Tracker $tracker)
    {
        return $this->dao->getKanbanByTrackerId($tracker->getId())->count() > 0;
    }

    public function createKanban($kanban_name, $tracker_id)
    {
        return $this->dao->create($kanban_name, $tracker_id);
    }

    public function duplicateKanbans(array $tracker_mapping, array $field_mapping, array $report_mapping)
    {
        $this->dao->duplicateKanbans($tracker_mapping, $field_mapping, $report_mapping);
    }

    public function getTrackersWithKanbanUsage($project_id, PFUser $user)
    {
        $trackers     = array();
        $all_trackers = $this->tracker_factory->getTrackersByGroupIdUserCanView($project_id, $user);

        foreach ($all_trackers as $tracker) {
            $tracker_representation         = array();
            $tracker_representation['id']   = $tracker->getId();
            $tracker_representation['name'] = $tracker->getName();

            if ($this->doesKanbanExistForTracker($tracker)) {
                $tracker_representation['used'] = true;
                $trackers[] = $tracker_representation;
                continue;
            }

            $tracker_representation['used'] = false;
            $trackers[] = $tracker_representation;
        }

        return $trackers;
    }

    /**
     * @return Tracker[]
     */
    public function getTrackersUsedAsKanban(Project $project)
    {
        $trackers = array();
        foreach ($this->dao->getKanbansForProject($project->getId()) as $row) {
            $tracker = $this->tracker_factory->getTrackerById($row['tracker_id']);
            if ($tracker) {
                $trackers[] = $tracker;
            }
        }

        return $trackers;
    }
}
