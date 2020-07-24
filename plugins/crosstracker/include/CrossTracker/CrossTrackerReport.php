<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\CrossTracker;

use Tracker;

class CrossTrackerReport
{
    /**
     * @var int
     */
    private $id;

    /** @var string */
    private $expert_query;

    /**
     * @var Tracker[]
     */
    private $trackers;
    /**
     * @var Tracker[]|null
     */
    private $valid_trackers;
    /**
     * @var Tracker[]|null
     */
    private $invalid_trackers;

    public function __construct($id, $expert_query, array $trackers)
    {
        $this->id           = $id;
        $this->expert_query = $expert_query;
        $this->trackers     = $trackers;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /** @return string */
    public function getExpertQuery()
    {
        return $this->expert_query;
    }

    /**
     * @return \Project[]
     */
    public function getProjects()
    {
        $projects = [];
        foreach ($this->getTrackers() as $tracker) {
            $project = $tracker->getProject();
            $projects[$project->getID()] = $project;
        }
        return array_values($projects);
    }

    /**
     * @return Tracker[]
     */
    public function getTrackers()
    {
        if ($this->valid_trackers === null) {
            $this->populateValidityTrackers();
        }
        return $this->valid_trackers;
    }

    /**
     * @return Tracker[]
     */
    public function getInvalidTrackers()
    {
        if ($this->invalid_trackers === null) {
            $this->populateValidityTrackers();
        }
        return $this->invalid_trackers;
    }

    private function populateValidityTrackers()
    {
        $this->valid_trackers   = [];
        $this->invalid_trackers = [];
        foreach ($this->trackers as $tracker) {
            $project = $tracker->getProject();
            if ($project === null || ! $project->isActive()) {
                $this->invalid_trackers[] = $tracker;
            } else {
                $this->valid_trackers[] = $tracker;
            }
        }
    }

    /**
     * @return \Tracker_FormElement_Field[]
     */
    public function getColumnFields()
    {
        $fields = [];
        foreach ($this->getTrackers() as $tracker) {
            $title_field       = $tracker->getTitleField();
            $status_field      = $tracker->getStatusField();
            $assigned_to_field = $tracker->getContributorField();
            foreach ([$title_field, $status_field, $assigned_to_field] as $field) {
                if ($field !== null) {
                    $fields[$field->getId()] = $field;
                }
            }
        }
        return array_values($fields);
    }

    /**
     * @return \Tracker_FormElement_Field[]
     */
    public function getSearchFields()
    {
        $fields = [];
        foreach ($this->getTrackers() as $tracker) {
            $field = $tracker->getStatusField();
            if ($field !== null) {
                $fields[$field->getId()] = $field;
            }
        }
        return array_values($fields);
    }

    /**
     * @return int[]
     */
    public function getTrackerIds()
    {
        return array_map(function (Tracker $tracker) {
            return $tracker->getId();
        }, $this->trackers);
    }
}
