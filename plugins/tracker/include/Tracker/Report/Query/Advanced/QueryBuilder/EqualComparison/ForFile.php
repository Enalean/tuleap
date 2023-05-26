<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\EqualComparison;

use CodendiDataAccess;
use Tracker_FormElement_Field;
use Tuleap\Tracker\Report\Query\Advanced\FieldFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\FromWhere;
use Tuleap\Tracker\Report\Query\IProvideFromAndWhereSQLFragments;

final class ForFile implements FieldFromWhereBuilder
{
    public function getFromWhere(Comparison $comparison, Tracker_FormElement_Field $field): IProvideFromAndWhereSQLFragments
    {
        $suffix           = spl_object_hash($comparison);
        $comparison_value = $comparison->getValueWrapper();
        $value            = $comparison_value->getValue();
        $field_id         = (int) $field->getId();

        $changeset_value_file_alias = "CVFile_{$field_id}_{$suffix}";
        $changeset_value_alias      = "CV_{$field_id}_{$suffix}";
        $fileinfo_alias             = "fileinfo_{$field_id}_{$suffix}";

        if ($value === '') {
            return new FromWhere(
                " LEFT JOIN (
                    tracker_changeset_value AS $changeset_value_alias
                    INNER JOIN tracker_changeset_value_file AS $changeset_value_file_alias
                     ON ($changeset_value_file_alias.changeset_value_id = $changeset_value_alias.id
                     )
                 ) ON ($changeset_value_alias.changeset_id = c.id AND $changeset_value_alias.field_id = $field_id)",
                "$changeset_value_alias.changeset_id IS NULL"
            );
        }

        $value = $this->quoteLikeValueSurround($value);

        return new FromWhere(
            " LEFT JOIN (
                    tracker_changeset_value AS $changeset_value_alias
                    INNER JOIN tracker_changeset_value_file AS $changeset_value_file_alias
                        ON (
                            $changeset_value_file_alias.changeset_value_id = $changeset_value_alias.id
                            AND $changeset_value_file_alias.fileinfo_id IS NOT NULL
                        )
                    INNER JOIN tracker_fileinfo AS $fileinfo_alias
                        ON (
                            $changeset_value_file_alias.fileinfo_id = $fileinfo_alias.id
                            AND (
                                $fileinfo_alias.description LIKE $value
                                OR $fileinfo_alias.filename LIKE $value
                            )
                        )
                 ) ON ($changeset_value_alias.changeset_id = c.id AND $changeset_value_alias.field_id = $field_id)",
            "$changeset_value_alias.changeset_id IS NOT NULL"
        );
    }

    private function quoteLikeValueSurround($value)
    {
        return CodendiDataAccess::instance()->quoteLikeValueSurround($value);
    }
}
