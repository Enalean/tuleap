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

interface ComparisonRepository
{
    /**
     * @throws NotAuthorizedException
     */
    public function add(TransientComparison $comparison, PFUser $current_user): Comparison;

    public function findById(PFUser $current_user, int $id): ?Comparison;

    /**
     * Find all comparisons on given project, ordered by creation date (most recent first).
     * @param int $page_size         Number of comparisons to fetch
     * @param int $comparison_offset Fetch comparisons from this index (start with 0), then follow creation date order (in reverse order).
     * @return Comparison[] requested comparison, excluding not authorized ones
     */
    public function findByProject(
        PFUser $current_user,
        Project $project,
        int $page_size,
        int $comparison_offset,
    ): array;

    /**
     * @return int total count of all available comparisons in given project, excluding any security policy
     * (for performances reasons)
     */
    public function countByProject(Project $project): int;

    public function delete(Comparison $comparison, PFUser $current_user): void;

    public function countByBaseline(Baseline $baseline): int;
}
