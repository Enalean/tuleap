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

namespace Tuleap\CrossTracker\Report\Query\Advanced\OrderByBuilder\Field;

use Tracker;
use Tracker_FormElement_Field;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\OrderBy\DuckTypedFieldOrderBy;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\OrderBy\DuckTypedFieldTypeOrderBy;
use Tuleap\CrossTracker\Report\Query\Advanced\OrderByBuilder\Field\Date\DateFromOrderBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\OrderByBuilder\OrderByBuilderParameters;
use Tuleap\CrossTracker\Report\Query\Advanced\OrderByBuilder\ParametrizedFromOrder;
use Tuleap\Tracker\FormElement\Field\RetrieveUsedFields;
use Tuleap\Tracker\FormElement\RetrieveFieldType;
use Tuleap\Tracker\Permission\FieldPermissionType;
use Tuleap\Tracker\Permission\RetrieveUserPermissionOnFields;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrderByDirection;

final readonly class FieldFromOrderBuilder
{
    public function __construct(
        private RetrieveUsedFields $retrieve_used_fields,
        private RetrieveFieldType $retrieve_field_type,
        private RetrieveUserPermissionOnFields $permission_on_fields,
        private DateFromOrderBuilder $date_builder,
    ) {
    }

    public function getFromOrder(Field $field, OrderByBuilderParameters $parameters): ParametrizedFromOrder
    {
        $tracker_ids = array_map(static fn(Tracker $tracker) => $tracker->getId(), $parameters->trackers);
        $fields      = array_filter(
            array_map(
                fn(int $tracker_id) => $this->retrieve_used_fields->getUsedFieldByName($tracker_id, $field->getName()),
                $tracker_ids,
            ),
            static fn(?Tracker_FormElement_Field $field) => $field !== null,
        );

        $fields_user_can_read = $this->permission_on_fields
            ->retrieveUserPermissionOnFields($parameters->user, $fields, FieldPermissionType::PERMISSION_READ)
            ->allowed;
        return DuckTypedFieldOrderBy::build(
            $this->retrieve_field_type,
            $field->getName(),
            $fields_user_can_read,
            $tracker_ids,
        )->match(
            fn(DuckTypedFieldOrderBy $field) => $this->matchTypeToBuilder($field, $parameters->direction),
            static fn() => new ParametrizedFromOrder('', [], ''),
        );
    }

    private function matchTypeToBuilder(DuckTypedFieldOrderBy $field, OrderByDirection $direction): ParametrizedFromOrder
    {
        $order = match ($direction) {
            OrderByDirection::ASCENDING  => 'ASC',
            OrderByDirection::DESCENDING => 'DESC',
        };

        return match ($field->type) {
            DuckTypedFieldTypeOrderBy::DATE,
            DuckTypedFieldTypeOrderBy::DATETIME  => $this->date_builder->getFromOrder($field, $order),
            DuckTypedFieldTypeOrderBy::NUMERIC,
            DuckTypedFieldTypeOrderBy::TEXT,
            DuckTypedFieldTypeOrderBy::STATIC_LIST,
            DuckTypedFieldTypeOrderBy::UGROUP_LIST,
            DuckTypedFieldTypeOrderBy::USER_LIST => new ParametrizedFromOrder('', [], ''),
        };
    }
}
