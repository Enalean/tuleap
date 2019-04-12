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

use PFUser;

class ComparisonService
{
    /** @var ComparisonRepository */
    private $comparison_repository;

    public function __construct(ComparisonRepository $comparison_repository)
    {
        $this->comparison_repository = $comparison_repository;
    }

    /**
     * @throws InvalidComparisonException
     * @throws NotAuthorizedException
     */
    public function create(TransientComparison $transient_comparison, PFUser $current_user): Comparison
    {
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
}
