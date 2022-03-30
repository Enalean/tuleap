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

namespace Tuleap\Baseline\Domain;

use PFUser;
use Project;

class BaselineService
{
    /** @var BaselineRepository */
    private $baseline_repository;

    /** @var ComparisonRepository */
    private $comparison_repository;

    /** @var Clock */
    private $clock;

    /** @var Authorizations */
    private $authorizations;

    public function __construct(
        BaselineRepository $baseline_repository,
        ComparisonRepository $comparison_repository,
        Clock $clock,
        Authorizations $authorizations,
    ) {
        $this->baseline_repository   = $baseline_repository;
        $this->comparison_repository = $comparison_repository;
        $this->clock                 = $clock;
        $this->authorizations        = $authorizations;
    }

    /**
     * @throws NotAuthorizedException
     */
    public function create(PFUser $current_user, TransientBaseline $baseline): Baseline
    {
        if (! $this->authorizations->canCreateBaseline($current_user, $baseline)) {
            throw new NotAuthorizedException(
                dgettext('tuleap-baseline', "You are not allowed to create this baseline")
            );
        }

        $snapshot_date = $baseline->getSnapshotDate();
        if ($snapshot_date === null) {
            $snapshot_date = $this->clock->now();
        }
        return $this->baseline_repository->add(
            $baseline,
            $current_user,
            $snapshot_date
        );
    }

    public function findById(PFUser $current_user, int $id): ?Baseline
    {
        return $this->baseline_repository->findById($current_user, $id);
    }

    /**
     * @throws NotAuthorizedException
     * @throws BaselineDeletionException
     */
    public function delete(PFUser $current_user, Baseline $baseline)
    {
        if (! $this->authorizations->canDeleteBaseline($current_user, $baseline)) {
            throw new NotAuthorizedException(
                dgettext('tuleap-baseline', "You are not allowed to delete this baseline")
            );
        }

        $comparisons_count = $this->comparison_repository->countByBaseline($baseline);
        if ($comparisons_count > 0) {
            throw new BaselineDeletionException($comparisons_count);
        }
        return $this->baseline_repository->delete($baseline, $current_user);
    }

    /**
     * Find baselines on given project, ordered by snapshot date (most recent first).
     * @param int $page_size       Number of baselines to return
     * @param int $baseline_offset Fetch baselines from this index (start with 0), following snapshot date order (in reverse order).
     * @return BaselinesPage requested baseline page, excluding not authorized baselines. More over, page
     *                             total count is the real total count without any security filtering.
     * @throws NotAuthorizedException
     */
    public function findByProject(
        PFUser $current_user,
        Project $project,
        int $page_size,
        int $baseline_offset,
    ): BaselinesPage {
        if (! $this->authorizations->canReadBaselinesOnProject($current_user, $project)) {
            throw new NotAuthorizedException(
                dgettext('tuleap-baseline', "You are not allowed to read baselines of this project")
            );
        }
        $baselines = $this->baseline_repository->findByProject($current_user, $project, $page_size, $baseline_offset);
        $count     = $this->baseline_repository->countByProject($project);
        return new BaselinesPage($baselines, $page_size, $baseline_offset, $count);
    }
}
