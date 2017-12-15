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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder;

use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\FromWhere;

class SemanticNotEqualComparisonFromWhereBuilder implements SemanticComparisonFromWhereBuilder
{
    public function getFromWhere(Comparison $comparison)
    {
        $value = $comparison->getValueWrapper()->getValue();

        if ($value === '') {
            return new FromWhere(
                "",
                "tracker_changeset_value_title.value IS NOT NULL AND tracker_changeset_value_title.value <> ''"
            );
        } else {
            return new FromWhere(
                "",
                "(tracker_changeset_value_title.value IS NULL
                    OR tracker_changeset_value_title.value NOT LIKE " . $this->quoteLikeValueSurround($value) . ")"
            );
        }
    }

    private function quoteLikeValueSurround($value)
    {
        return \CodendiDataAccess::instance()->quoteLikeValueSurround($value);
    }
}
