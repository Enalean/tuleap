<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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
 */

namespace Tuleap\CrossTracker\Report\Query\Advanced\InvalidSemantic;

use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;

interface ICheckMetadataForAComparison
{
    /**
     * @param Metadata $metadata
     * @param Comparison $comparison
     * @param int[] $trackers_id
     * @param ComparisonChecker $checker
     */
    public function checkMetadataIsValid(
        Metadata $metadata,
        Comparison $comparison,
        array $trackers_id,
        ComparisonChecker $checker
    );
}
