<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\CrossTracker;

use Project;
use Tracker;
use Tracker_FormElement_Field;

final readonly class CrossTrackerExpertReport implements CrossTrackerReport
{
    public function __construct(
        private int $id,
        private string $expert_query,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getExpertQuery(): string
    {
        return $this->expert_query;
    }

    public function isExpert(): bool
    {
        return true;
    }

    /**
     * @return Project[]
     */
    public function getProjects(): array
    {
        $projects = [];
        foreach ($this->getTrackers() as $tracker) {
            $project                     = $tracker->getProject();
            $projects[$project->getID()] = $project;
        }
        return array_values($projects);
    }

    /**
     * @return Tracker[]
     */
    public function getTrackers(): array
    {
        return [];
    }

    /**
     * @return Tracker_FormElement_Field[]
     */
    public function getColumnFields(): array
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
     * @return Tracker_FormElement_Field[]
     */
    public function getSearchFields(): array
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
}
