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

class ComparisonService
{
    /** @var ComparisonRepository */
    private $comparison_repository;

    /** @var Authorizations */
    private $authorizations;

    public function __construct(ComparisonRepository $comparison_repository, Authorizations $authorizations)
    {
        $this->comparison_repository = $comparison_repository;
        $this->authorizations        = $authorizations;
    }

    /**
     * @throws InvalidComparisonException
     * @throws NotAuthorizedException
     */
    public function create(TransientComparison $transient_comparison, PFUser $current_user): Comparison
    {
        if (! $this->authorizations->canCreateComparison($current_user, $transient_comparison)) {
            throw new NotAuthorizedException(
                dgettext('tuleap-baseline', "You are not allowed to create this comparison")
            );
        }

        $base_baseline        = $transient_comparison->getBaseBaseline();
        $compared_to_baseline = $transient_comparison->getComparedToBaseline();
        if (! $base_baseline->getArtifact()->equals($compared_to_baseline->getArtifact())) {
            throw new InvalidComparisonException(
                sprintf(
                    dgettext(
                        'tuleap-baseline',
                        'Base baseline is not on same artifact (id %u) than compared to baseline (id %u)'
                    ),
                    $base_baseline->getArtifact()->getId(),
                    $compared_to_baseline->getArtifact()->getId()
                )
            );
        }

        return $this->comparison_repository->add($transient_comparison, $current_user);
    }

    public function findById(PFUser $current_user, int $id): ?Comparison
    {
        return $this->comparison_repository->findById($current_user, $id);
    }

    /**
     * Find comparisons on given project, ordered by creation date (most recent first).
     * @param int $page_size         Number of comparisons to return
     * @param int $comparison_offset Fetch comparisons from this index (start with 0), following creation date order (in reverse order).
     * @return ComparisonsPage requested comparison page, excluding not authorized comparisons. More over, page
     *                               total count is the real total count without any security filtering.
     * @throws NotAuthorizedException
     */
    public function findByProject(
        PFUser $current_user,
        Project $project,
        int $page_size,
        int $comparison_offset,
    ): ComparisonsPage {
        if (! $this->authorizations->canReadComparisonsOnProject($current_user, $project)) {
            throw new NotAuthorizedException(
                dgettext('tuleap-baseline', "You are not allowed to read comparisons of this project")
            );
        }
        $comparisons = $this->comparison_repository->findByProject(
            $current_user,
            $project,
            $page_size,
            $comparison_offset
        );
        $count       = $this->comparison_repository->countByProject($project);
        return new ComparisonsPage($comparisons, $page_size, $comparison_offset, $count);
    }

    /**
     * @throws NotAuthorizedException
     */
    public function delete(PFUser $current_user, Comparison $comparison): void
    {
        if (! $this->authorizations->canDeleteComparison($current_user, $comparison)) {
            throw new NotAuthorizedException(
                dgettext('tuleap-baseline', "You are not allowed to delete this comparison")
            );
        }
        $this->comparison_repository->delete($comparison, $current_user);
    }
}
