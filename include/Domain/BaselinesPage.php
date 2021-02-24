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
 * Lump of all available Baselines, fetch with pagination.
 */
class BaselinesPage
{
    /** @var Baseline[] */
    private $baselines;

    /** @var int */
    private $page_size;

    /** @var int Position of the first baseline in this page (start at 0) */
    private $baseline_offset;

    /** @var int Total count of all available baselines (in all pages) */
    private $total_baseline_count;

    public function __construct(array $baselines, int $page_size, int $baseline_offset, int $total_baseline_count)
    {
        $this->baselines            = $baselines;
        $this->page_size            = $page_size;
        $this->baseline_offset      = $baseline_offset;
        $this->total_baseline_count = $total_baseline_count;
    }

    public function getBaselines(): array
    {
        return $this->baselines;
    }

    public function getPageSize(): int
    {
        return $this->page_size;
    }

    public function getBaselineOffset(): int
    {
        return $this->baseline_offset;
    }

    public function getTotalBaselineCount(): int
    {
        return $this->total_baseline_count;
    }
}
