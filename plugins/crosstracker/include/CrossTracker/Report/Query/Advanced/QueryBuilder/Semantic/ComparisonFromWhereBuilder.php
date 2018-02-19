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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Semantic;

use Tuleap\CrossTracker\Report\Query\Advanced\AllowedMetadata;
use Tuleap\CrossTracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;

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
    /**
     * @var Status\FromWhereBuilder
     */
    private $status_builder;

    public function __construct(
        Title\FromWhereBuilder $title_builder,
        Description\FromWhereBuilder $description_builder,
        Status\FromWhereBuilder $status_builder
    ) {
        $this->title_builder       = $title_builder;
        $this->description_builder = $description_builder;
        $this->status_builder      = $status_builder;
    }

    /**
     * @return IProvideParametrizedFromAndWhereSQLFragments
     */
    public function getFromWhere(Metadata $metadata, Comparison $comparison, array $trackers)
    {
        switch ($metadata->getName()) {
            case AllowedMetadata::TITLE:
                return $this->title_builder->getFromWhere($metadata, $comparison, $trackers);
                break;
            case AllowedMetadata::DESCRIPTION:
                return $this->description_builder->getFromWhere($metadata, $comparison, $trackers);
                break;
            case AllowedMetadata::STATUS:
                return $this->status_builder->getFromWhere($metadata, $comparison, $trackers);
                break;
        }
    }
}
