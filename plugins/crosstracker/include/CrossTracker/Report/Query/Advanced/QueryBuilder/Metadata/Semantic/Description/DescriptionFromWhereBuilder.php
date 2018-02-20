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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\Semantic\Description;

abstract class DescriptionFromWhereBuilder implements FromWhereBuilder
{
    /**
     * @return string
     */
    protected function getFrom()
    {
        $from = "LEFT JOIN (
            tracker_changeset_value AS changeset_value_description
            INNER JOIN tracker_semantic_description
                ON (tracker_semantic_description.field_id = changeset_value_description.field_id)
            INNER JOIN tracker_changeset_value_text AS tracker_changeset_value_description
                ON (tracker_changeset_value_description.changeset_value_id = changeset_value_description.id)
        ) ON (
            tracker_semantic_description.tracker_id = tracker_artifact.tracker_id
            AND changeset_value_description.changeset_id = tracker_artifact.last_changeset_id
        )";

        return $from;
    }
}
