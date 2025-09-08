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

use Tuleap\DB\DBFactory;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Report\Query\Advanced\FieldFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;

final class ForFile implements FieldFromWhereBuilder
{
    #[\Override]
    public function getFromWhere(Comparison $comparison, TrackerField $field): IProvideParametrizedFromAndWhereSQLFragments
    {
        $suffix           = spl_object_hash($comparison);
        $comparison_value = $comparison->getValueWrapper();
        $value            = $comparison_value->getValue();
        $field_id         = (int) $field->getId();

        $changeset_value_file_alias = "CVFile_{$field_id}_{$suffix}";
        $changeset_value_alias      = "CV_{$field_id}_{$suffix}";
        $fileinfo_alias             = "fileinfo_{$field_id}_{$suffix}";

        if ($value === '') {
            return new ParametrizedFromWhere(
                " LEFT JOIN (
                    tracker_changeset_value AS $changeset_value_alias
                    INNER JOIN tracker_changeset_value_file AS $changeset_value_file_alias
                     ON ($changeset_value_file_alias.changeset_value_id = $changeset_value_alias.id
                     )
                 ) ON ($changeset_value_alias.changeset_id = c.id AND $changeset_value_alias.field_id = ?)",
                "$changeset_value_alias.changeset_id IS NULL",
                [
                    $field_id,
                ],
                []
            );
        }

        $value = $this->quoteLikeValueSurround($value);

        return new ParametrizedFromWhere(
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
                                $fileinfo_alias.description LIKE ?
                                OR $fileinfo_alias.filename LIKE ?
                            )
                        )
                 ) ON ($changeset_value_alias.changeset_id = c.id AND $changeset_value_alias.field_id = ?)",
            "$changeset_value_alias.changeset_id IS NOT NULL",
            [
                $value,
                $value,
                $field_id,
            ],
            []
        );
    }

    private function quoteLikeValueSurround($value)
    {
        return '%' . DBFactory::getMainTuleapDBConnection()->getDB()->escapeLikeValue($value) . '%';
    }
}
