<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Baseline\Adapter;

use PFUser;
use Tracker_Artifact_Changeset;

class SemanticValueAdapter
{
    /** @var SemanticFieldRepository */
    private $semantic_field_repository;

    public function __construct(SemanticFieldRepository $semantic_field_repository)
    {
        $this->semantic_field_repository = $semantic_field_repository;
    }

    /**
     * Find value of title field for a given artifact at a given date time (i.e. given change set).
     */
    public function findTitle(Tracker_Artifact_Changeset $changeset, PFUser $current_user): ?string
    {
        $tracker     = $changeset->getTracker();
        $title_field = $this->semantic_field_repository->findTitleByTracker($tracker);
        if ($title_field === null || ! $title_field->userCanRead($current_user)) {
            return null;
        }

        $changed_value = $changeset->getValue($title_field);
        if ($changed_value === null) {
            return null;
        }
        return $changed_value->getValue();
    }

    /**
     * Find value of description field for a given artifact at a given date time (i.e. given change set).
     */
    public function findDescription(Tracker_Artifact_Changeset $changeset, PFUser $current_user): ?string
    {
        $tracker           = $changeset->getTracker();
        $description_field = $this->semantic_field_repository->findDescriptionByTracker($tracker);
        if ($description_field === null || ! $description_field->userCanRead($current_user)) {
            return null;
        }

        $changed_value = $changeset->getValue($description_field);
        if ($changed_value === null) {
            return null;
        }
        return $changed_value->getValue();
    }

    /**
     * Find value of initial effort field for a given artifact at a given date time (i.e. given change set).
     */
    public function findInitialEffort(Tracker_Artifact_Changeset $changeset, PFUser $current_user): ?int
    {
        $tracker              = $changeset->getTracker();
        $initial_effort_field = $this->semantic_field_repository->findInitialEffortByTracker($tracker);
        if ($initial_effort_field === null || ! $initial_effort_field->userCanRead($current_user)) {
            return null;
        }

        $changed_value = $changeset->getValue($initial_effort_field);
        if ($changed_value === null) {
            return null;
        }
        return (int) $changed_value->getValue();
    }

    /**
     * Find value of status field for a given artifact at a given date time (i.e. given change set).
     */
    public function findStatus(Tracker_Artifact_Changeset $changeset, PFUser $current_user): ?string
    {
        $tracker      = $changeset->getTracker();
        $status_field = $this->semantic_field_repository->findStatusByTracker($tracker);
        if ($status_field === null || ! $status_field->userCanRead($current_user)) {
            return null;
        }

        return $status_field->getFirstValueFor($changeset);
    }
}
