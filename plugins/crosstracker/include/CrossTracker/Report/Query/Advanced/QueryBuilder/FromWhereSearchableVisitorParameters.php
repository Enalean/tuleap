<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder;

use Tracker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\FromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\VisitorParameters;

final class FromWhereSearchableVisitorParameters implements VisitorParameters
{
    public function __construct(
        private readonly Comparison $comparison,
        private readonly FromWhereBuilder $from_where_builder,
        private readonly array $trackers,
    ) {
    }

    public function getFromWhereBuilder(): FromWhereBuilder
    {
        return $this->from_where_builder;
    }

    public function getComparison(): Comparison
    {
        return $this->comparison;
    }

    /** @return Tracker[] */
    public function getTrackers(): array
    {
        return $this->trackers;
    }
}
