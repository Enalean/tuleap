<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Baseline;

use DateTime;
use Project;
use Tracker;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;

class BaselineService
{
    /** @var FieldRepository */
    private $field_repository;

    /** @var Permissions */
    private $permissions;

    /** @var ChangesetRepository */
    private $changeset_repository;

    /** @var BaselineRepository */
    private $baseline_repository;

    /** @var CurrentUserProvider */
    private $current_user_provider;

    /** @var Clock */
    private $clock;

    public function __construct(
        FieldRepository $field_repository,
        Permissions $permissions,
        ChangesetRepository $changeset_repository,
        BaselineRepository $baseline_repository,
        CurrentUserProvider $current_user_provider,
        Clock $clock
    ) {
        $this->field_repository      = $field_repository;
        $this->permissions           = $permissions;
        $this->changeset_repository  = $changeset_repository;
        $this->baseline_repository   = $baseline_repository;
        $this->current_user_provider = $current_user_provider;
        $this->clock                 = $clock;
    }

    /**
     * @throws NotAuthorizedException
     */
    public function create(TransientBaseline $baseline): Baseline
    {
        $this->permissions->checkCreateBaseline($baseline);
        return $this->baseline_repository->add(
            $baseline,
            $this->current_user_provider->getUser(),
            $this->clock->now()
        );
    }

    /**
     * Find simplified baseline on given milestone and given date time.
     *
     * @throws ChangesetNotFoundException when given tracker did not exist on given date
     * @throws NotAuthorizedException
     */
    public function findSimplified(Tracker_Artifact $milestone, DateTime $date): SimplifiedBaseline
    {
        $change_set = $this->changeset_repository->findByArtifactAndDate($milestone, $date);
        if ($change_set === null) {
            throw new ChangesetNotFoundException($date);
        }

        $tracker        = $milestone->getTracker();
        $changeset_date = new DateTime();
        $changeset_date->setTimestamp((int) $change_set->getSubmittedOn());

        $baseline = new SimplifiedBaseline(
            $milestone,
            $this->getTrackerTitle($tracker, $change_set),
            $this->getTrackerDescription($tracker, $change_set),
            $this->getTrackerStatus($tracker, $change_set),
            $changeset_date
        );

        $this->permissions->checkReadSimpleBaseline($baseline);
        return $baseline;
    }

    /**
     * @throws NotAuthorizedException
     */
    public function findById(int $id): ?Baseline
    {
        return $this->baseline_repository->findById($id);
    }

    /**
     * Find baselines on given project, ordered by snapshot date.
     * @param int $page_size       Number of baselines to fetch
     * @param int $baseline_offset Fetch baselines from this index (start with 0), following snapshot date order.
     * @throws NotAuthorizedException
     */
    public function findByProject(Project $project, int $page_size, int $baseline_offset): BaselinesPage
    {
        $this->permissions->checkReadBaselinesOn($project);
        $baselines = $this->baseline_repository->findByProject($project, $page_size, $baseline_offset);
        $count     = $this->baseline_repository->countByProject($project);
        return new BaselinesPage($baselines, $page_size, $baseline_offset, $count);
    }

    private function getTrackerTitle(Tracker $tracker, Tracker_Artifact_Changeset $changeSet): ?string
    {
        $title_field = $this->field_repository->findTitleByTracker($tracker);
        if ($title_field === null) {
            return null;
        }

        return $changeSet->getValue($title_field)->getValue();
    }

    private function getTrackerDescription(Tracker $tracker, Tracker_Artifact_Changeset $changeSet): ?string
    {
        $description_field = $this->field_repository->findDescriptionByTracker($tracker);
        if ($description_field === null) {
            return null;
        }

        return $changeSet->getValue($description_field)->getValue();
    }

    private function getTrackerStatus(Tracker $tracker, Tracker_Artifact_Changeset $changeSet): ?string
    {
        $status_field = $this->field_repository->findStatusByTracker($tracker);
        if ($status_field === null) {
            return null;
        }

        return $status_field->getFirstValueFor($changeSet);
    }
}
