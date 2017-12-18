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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Semantic\Description;

use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\FromWhere;

class NotEqualComparisonFromWhereBuilder extends DescriptionFromWhereBuilder
{
    public function getFromWhere(Metadata $metadata, Comparison $comparison)
    {
        $value = $comparison->getValueWrapper()->getValue();

        if ($value === '') {
            return new FromWhere(
                $this->getFrom(),
                "tracker_changeset_value_description.value IS NOT NULL AND tracker_changeset_value_description.value <> ''"
            );
        } else {
            return new FromWhere(
                $this->getFrom(),
                "(tracker_changeset_value_description.value IS NULL
                    OR tracker_changeset_value_description.value NOT LIKE " . $this->quoteLikeValueSurround($value) . ")"
            );
        }
    }

    private function quoteLikeValueSurround($value)
    {
        return \CodendiDataAccess::instance()->quoteLikeValueSurround($value);
    }
}
