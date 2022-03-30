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

/**
 * Lump of all available comparisons, fetch with pagination.
 */
class ComparisonsPage
{
    /** @var Comparison[] */
    private $comparisons;

    /** @var int */
    private $page_size;

    /** @var int Position of the first comparison in this page (start at 0) */
    private $comparison_offset;

    /** @var int Total count of all available comparisons (in all pages) */
    private $total_comparisons_count;

    public function __construct(array $comparisons, int $page_size, int $comparison_offset, int $total_comparison_count)
    {
        $this->comparisons             = $comparisons;
        $this->page_size               = $page_size;
        $this->comparison_offset       = $comparison_offset;
        $this->total_comparisons_count = $total_comparison_count;
    }

    public function getComparisons(): array
    {
        return $this->comparisons;
    }

    public function getPageSize(): int
    {
        return $this->page_size;
    }

    public function getComparisonOffset(): int
    {
        return $this->comparison_offset;
    }

    public function getTotalComparisonsCount(): int
    {
        return $this->total_comparisons_count;
    }
}
