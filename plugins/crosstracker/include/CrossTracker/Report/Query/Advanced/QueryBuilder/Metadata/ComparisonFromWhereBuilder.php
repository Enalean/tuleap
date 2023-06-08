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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata;

use Tuleap\CrossTracker\Report\Query\Advanced\AllowedMetadata;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;

abstract class ComparisonFromWhereBuilder implements FromWhereBuilder
{
    /**
     * @var Semantic\Title\FromWhereBuilder
     */
    private $title_builder;
    /**
     * @var Semantic\Description\FromWhereBuilder
     */
    private $description_builder;
    /**
     * @var Semantic\Status\FromWhereBuilder
     */
    private $status_builder;

    /**
     * @var AlwaysThereField\Date\FromWhereBuilder
     */
    private $submitted_on_builder;
    /**
     * @var AlwaysThereField\Date\FromWhereBuilder
     */
    private $last_update_date_builder;
    /**
     * @var AlwaysThereField\Users\FromWhereBuilder
     */
    private $submitted_by_builder;
    /**
     * @var AlwaysThereField\Users\FromWhereBuilder
     */
    private $last_update_by_builder;
    /**
     * @var AlwaysThereField\Users\FromWhereBuilder
     */
    private $assigned_to_builder;

    public function __construct(
        Semantic\Title\FromWhereBuilder $title_builder,
        Semantic\Description\FromWhereBuilder $description_builder,
        Semantic\Status\FromWhereBuilder $status_builder,
        AlwaysThereField\Date\FromWhereBuilder $submitted_on_builder,
        AlwaysThereField\Date\FromWhereBuilder $last_update_date_builder,
        AlwaysThereField\Users\FromWhereBuilder $submitted_by_builder,
        AlwaysThereField\Users\FromWhereBuilder $last_update_by_builder,
        Semantic\AssignedTo\FromWhereBuilder $assigned_to_builder,
    ) {
        $this->title_builder            = $title_builder;
        $this->description_builder      = $description_builder;
        $this->status_builder           = $status_builder;
        $this->submitted_on_builder     = $submitted_on_builder;
        $this->last_update_date_builder = $last_update_date_builder;
        $this->submitted_by_builder     = $submitted_by_builder;
        $this->last_update_by_builder   = $last_update_by_builder;
        $this->assigned_to_builder      = $assigned_to_builder;
    }

    /**
     * @return IProvideParametrizedFromAndWhereSQLFragments
     */
    public function getFromWhere(Metadata $metadata, Comparison $comparison, array $trackers)
    {
        switch ($metadata->getName()) {
            case AllowedMetadata::TITLE:
                return $this->title_builder->getFromWhere($metadata, $comparison, $trackers);
            case AllowedMetadata::DESCRIPTION:
                return $this->description_builder->getFromWhere($metadata, $comparison, $trackers);
            case AllowedMetadata::STATUS:
                return $this->status_builder->getFromWhere($metadata, $comparison, $trackers);
            case AllowedMetadata::SUBMITTED_ON:
                return $this->submitted_on_builder->getFromWhere($metadata, $comparison, $trackers);
            case AllowedMetadata::LAST_UPDATE_DATE:
                return $this->last_update_date_builder->getFromWhere($metadata, $comparison, $trackers);
            case AllowedMetadata::SUBMITTED_BY:
                return $this->submitted_by_builder->getFromWhere($metadata, $comparison, $trackers);
            case AllowedMetadata::LAST_UPDATE_BY:
                return $this->last_update_by_builder->getFromWhere($metadata, $comparison, $trackers);
            case AllowedMetadata::ASSIGNED_TO:
                return $this->assigned_to_builder->getFromWhere($metadata, $comparison, $trackers);
        }
    }
}
