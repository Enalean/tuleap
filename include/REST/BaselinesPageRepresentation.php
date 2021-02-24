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

namespace Tuleap\Baseline\REST;

use Tuleap\Baseline\Domain\Baseline;
use Tuleap\Baseline\Domain\BaselinesPage;
use Tuleap\REST\JsonCast;

class BaselinesPageRepresentation
{
    /** @var BaselineRepresentation[] */
    public $baselines;

    /** @var int */
    public $total_count;

    /**
     * @param $baselines BaselineRepresentation[]
     */
    public function __construct(array $baselines, int $total_count)
    {
        $this->baselines   = $baselines;
        $this->total_count = $total_count;
    }

    public static function build(BaselinesPage $baselines_page)
    {
        $baseline_representations = array_map(
            function (Baseline $baseline) {
                return BaselineRepresentation::fromBaseline($baseline);
            },
            $baselines_page->getBaselines()
        );
        return new self(
            $baseline_representations,
            JsonCast::toInt($baselines_page->getTotalBaselineCount())
        );
    }

    public function getTotalCount(): int
    {
        return $this->total_count;
    }
}
