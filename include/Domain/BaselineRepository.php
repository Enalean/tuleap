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

use DateTimeInterface;
use PFUser;
use Project;

interface BaselineRepository
{
    public function add(
        TransientBaseline $baseline,
        PFUser $current_user,
        DateTimeInterface $snapshot_date
    ): Baseline;

    public function findById(PFUser $current_user, int $id): ?Baseline;

    public function delete(Baseline $baseline, PFUser $current_user);

    /**
     * Find all baselines on given project, ordered by snapshot date.
     * @param int $page_size       Number of baselines to fetch
     * @param int $baseline_offset Fetch baselines from this index (start with 0), then follow snapshot date order.
     * @return Baseline[] requested baseline, excluding not authorized ones
     */
    public function findByProject(PFUser $current_user, Project $project, int $page_size, int $baseline_offset): array;

    /**
     * @return int total count of all available baseline in given project, excluding any security policy
     * (for performances reasons)
     */
    public function countByProject(Project $project): int;
}
