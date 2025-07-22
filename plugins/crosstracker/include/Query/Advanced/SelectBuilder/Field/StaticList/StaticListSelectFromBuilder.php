<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Field\StaticList;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\EasyStatement;
use Tuleap\CrossTracker\Query\Advanced\DuckTypedField\Select\DuckTypedFieldSelect;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\IProvideParametrizedSelectAndFromSQLFragments;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\ParametrizedSelectFrom;
use Tuleap\CrossTracker\Query\Advanced\SelectResultKey;

final readonly class StaticListSelectFromBuilder
{
    public function __construct(private EasyDB $easy_db)
    {
    }

    public function getSelectFrom(DuckTypedFieldSelect $field): IProvideParametrizedSelectAndFromSQLFragments
    {
        $suffix                                  = SelectResultKey::fromDuckTypedField($field);
        $tracker_field_alias                     = $this->easy_db->escapeIdentifier("TF_$suffix");
        $changeset_value_alias                   = $this->easy_db->escapeIdentifier("CV_$suffix");
        $tracker_field_list_bind_static_alias    = $this->easy_db->escapeIdentifier("TFLBSV_$suffix");
        $changeset_value_openlist_alias          = $this->easy_db->escapeIdentifier("CVO_$suffix");
        $tracker_field_openlist_value_alias      = $this->easy_db->escapeIdentifier("TFOV_$suffix");
        $tracker_changeset_value_list_alias      = $this->easy_db->escapeIdentifier("TCVL_$suffix");
        $tracker_field_list_bind_decorator_alias = $this->easy_db->escapeIdentifier("TFLBD_$suffix");

        $fields_id_statement = EasyStatement::open()->in(
            "$tracker_field_alias.id IN(?*)",
            $field->field_ids
        );

        $select = "$tracker_field_list_bind_static_alias.label as static_list_value_$suffix, $tracker_field_openlist_value_alias.label as static_list_open_$suffix, $tracker_field_list_bind_decorator_alias.tlp_color_name as static_list_color_$suffix ";

        $from = <<<EOSQL
         LEFT JOIN tracker_field AS $tracker_field_alias
            ON (tracker.id = $tracker_field_alias.tracker_id AND $fields_id_statement)
        LEFT JOIN tracker_changeset_value AS $changeset_value_alias
            ON ($tracker_field_alias.id = $changeset_value_alias.field_id AND changeset.id = $changeset_value_alias.changeset_id)
        LEFT JOIN tracker_changeset_value_openlist AS $changeset_value_openlist_alias
            ON $changeset_value_openlist_alias.changeset_value_id = $changeset_value_alias.id
        LEFT JOIN tracker_field_openlist_value as $tracker_field_openlist_value_alias
            ON $tracker_field_openlist_value_alias.id = $changeset_value_openlist_alias.openvalue_id
        LEFT JOIN tracker_changeset_value_list AS $tracker_changeset_value_list_alias
            ON $tracker_changeset_value_list_alias.changeset_value_id = $changeset_value_alias.id
        LEFT JOIN tracker_field_list_bind_static_value AS $tracker_field_list_bind_static_alias
            ON ($changeset_value_openlist_alias.bindvalue_id = $tracker_field_list_bind_static_alias.id
                OR $tracker_changeset_value_list_alias.bindvalue_id = $tracker_field_list_bind_static_alias.id)
        LEFT JOIN tracker_field_list_bind_decorator as $tracker_field_list_bind_decorator_alias
            ON $tracker_field_list_bind_static_alias.id = $tracker_field_list_bind_decorator_alias.value_id
        EOSQL;

        return new ParametrizedSelectFrom(
            $select,
            $from,
            $fields_id_statement->values(),
        );
    }
}
