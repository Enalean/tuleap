<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Semantic;

use Tuleap\CrossTracker\Report\Query\Advanced\AllowedMetadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\FromWhere;

abstract class ComparisonFromWhereBuilder implements FromWhereBuilder
{
    /**
     * @var Title\FromWhereBuilder
     */
    private $title_builder;
    /**
     * @var Description\FromWhereBuilder
     */
    private $description_builder;

    public function __construct(
        Title\FromWhereBuilder $title_builder,
        Description\FromWhereBuilder $description_builder
    ) {
        $this->title_builder       = $title_builder;
        $this->description_builder = $description_builder;
    }

    /**
     * @return FromWhere
     */
    public function getFromWhere(Metadata $metadata, Comparison $comparison)
    {
        if ($metadata->getName() === AllowedMetadata::TITLE) {
            return $this->title_builder->getFromWhere($metadata, $comparison);
        }

        return $this->description_builder->getFromWhere($metadata, $comparison);
    }
}
