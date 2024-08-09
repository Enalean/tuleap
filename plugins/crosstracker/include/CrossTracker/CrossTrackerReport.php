<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

use Project;
use Tracker;
use Tracker_FormElement_Field;

class CrossTrackerReport
{
    /**
     * @var Tracker[]|null
     */
    private ?array $valid_trackers;
    /**
     * @var Tracker[]|null
     */
    private ?array $invalid_trackers;

    /**
     * @param Tracker[] $trackers
     */
    public function __construct(
        private readonly int $id,
        private readonly string $expert_query,
        private readonly array $trackers,
        private readonly bool $expert,
    ) {
        $this->valid_trackers   = null;
        $this->invalid_trackers = null;
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
        return $this->expert;
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
        if ($this->valid_trackers === null) {
            $this->populateValidityTrackers();
        }
        return $this->valid_trackers;
    }

    /**
     * @return Tracker[]
     */
    public function getInvalidTrackers(): array
    {
        if ($this->invalid_trackers === null) {
            $this->populateValidityTrackers();
        }
        return $this->invalid_trackers;
    }

    /**
     * @psalm-assert !null $this->valid_trackers
     * @psalm-assert !null $this->invalid_trackers
     */
    private function populateValidityTrackers(): void
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

    /**
     * @return int[]
     */
    public function getTrackerIds(): array
    {
        return array_map(function (Tracker $tracker) {
            return $tracker->getId();
        }, $this->trackers);
    }
}
