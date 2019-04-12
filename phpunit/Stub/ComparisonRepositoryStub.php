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

namespace Tuleap\Baseline\Stub;

use PFUser;
use Tuleap\Baseline\Baseline;
use Tuleap\Baseline\Comparison;
use Tuleap\Baseline\ComparisonRepository;
use Tuleap\Baseline\TransientComparison;

class ComparisonRepositoryStub implements ComparisonRepository
{
    /** @var Baseline[] */
    private $comparisons_by_id = [];

    /** @var int */
    private $id_sequence = 1;

    public function add(TransientComparison $transient_comparison, PFUser $current_user): Comparison
    {
        $comparison = new Comparison(
            $this->id_sequence++,
            $transient_comparison->getName(),
            $transient_comparison->getComment(),
            $transient_comparison->getBaseBaseline(),
            $transient_comparison->getComparedToBaseline()
        );

        $this->comparisons_by_id[$comparison->getId()] = $comparison;
        return $comparison;
    }

    public function count(): int
    {
        return count($this->comparisons_by_id);
    }

    public function findAny(): Comparison
    {
        if (count($this->comparisons_by_id) === 0) {
            return null;
        }
        return array_values($this->comparisons_by_id)[0];
    }
}
