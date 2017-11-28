<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

    /**
     * @var Tracker[]
     */
    private $trackers;

    public function __construct($id, array $trackers)
    {
        $this->id       = $id;
        $this->trackers = $trackers;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \Project[]
     */
    public function getProjects()
    {
        $projects = array();
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
        return $this->trackers;
    }

    /**
     * @return \Tracker_FormElement_Field[]
     */
    public function getColumnFields()
    {
        $fields = array();
        foreach ($this->getTrackers() as $tracker) {
            $title_field       = $tracker->getTitleField();
            $status_field      = $tracker->getStatusField();
            $assigned_to_field = $tracker->getContributorField();
            foreach (array($title_field, $status_field, $assigned_to_field) as $field) {
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
        $fields = array();
        foreach ($this->getTrackers() as $tracker) {
            $field = $tracker->getStatusField();
            if ($field !== null) {
                $fields[$field->getId()] = $field;
            }
        }
        return array_values($fields);
    }
}
