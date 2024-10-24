<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\CrossTracker\Report\Query\Advanced\OrderByBuilder\Field\StaticList;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\CrossTracker\Report\Query\Advanced\OrderByBuilder\ParametrizedFromOrder;

final class StaticListFromOrderBuilder
{
    /**
     * @param list<int> $field_ids
     */
    public function getFromOrder(array $field_ids, string $order): ParametrizedFromOrder
    {
        $suffix                               = md5($order);
        $tracker_field_alias                  = "TF_$suffix";
        $changeset_value_alias                = "CV_$suffix";
        $tracker_changeset_value_list_alias   = "TCVL_$suffix";
        $tracker_field_list_bind_static_alias = "TFLBSV_$suffix";

        $fields_id_statement = EasyStatement::open()->in("$tracker_field_alias.id IN(?*)", $field_ids);
        $from                = <<<EOSQL
        LEFT JOIN tracker_field AS $tracker_field_alias
            ON (tracker.id = $tracker_field_alias.tracker_id AND $fields_id_statement)
        LEFT JOIN tracker_changeset_value AS $changeset_value_alias
            ON ($tracker_field_alias.id = $changeset_value_alias.field_id AND changeset.id = $changeset_value_alias.changeset_id)
        LEFT JOIN tracker_changeset_value_list AS $tracker_changeset_value_list_alias
            ON $tracker_changeset_value_list_alias.changeset_value_id = $changeset_value_alias.id
        LEFT JOIN tracker_field_list_bind_static_value AS $tracker_field_list_bind_static_alias
            ON $tracker_changeset_value_list_alias.bindvalue_id = $tracker_field_list_bind_static_alias.id
        EOSQL;

        return new ParametrizedFromOrder($from, $field_ids, "CAST($tracker_field_list_bind_static_alias.label AS SIGNED) $order, $tracker_field_list_bind_static_alias.label $order");
    }
}
