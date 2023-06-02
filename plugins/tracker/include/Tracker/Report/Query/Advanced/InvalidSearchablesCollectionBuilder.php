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

namespace Tuleap\Tracker\Report\Query\Advanced;

use Tuleap\Tracker\Report\Query\Advanced\Grammar\Logical;

final class InvalidSearchablesCollectionBuilder implements IBuildInvalidSearchablesCollection
{
    /**
     * @var InvalidComparisonCollectorVisitor
     */
    private $invalid_comparison_collector;

    public function __construct(InvalidComparisonCollectorVisitor $invalid_comparison_collector)
    {
        $this->invalid_comparison_collector = $invalid_comparison_collector;
    }

    public function buildCollectionOfInvalidSearchables(Logical $parsed_expert_query): InvalidSearchablesCollection
    {
        $invalid_searchables_collection = new InvalidSearchablesCollection();
        $this->invalid_comparison_collector->collectErrors($parsed_expert_query, $invalid_searchables_collection);

        return $invalid_searchables_collection;
    }
}
