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

namespace Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\Field;

use Tracker;
use Tuleap\CrossTracker\Query\Advanced\DuckTypedField\OrderBy\DuckTypedFieldOrderBy;
use Tuleap\CrossTracker\Query\Advanced\DuckTypedField\OrderBy\DuckTypedFieldTypeOrderBy;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\Field\Date\DateFromOrderBuilder;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\Field\Numeric\NumericFromOrderBuilder;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\Field\StaticList\StaticListFromOrderBuilder;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\Field\Text\TextFromOrderBuilder;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\Field\UGroupList\UGroupListFromOrderBuilder;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\Field\UserList\UserListFromOrderBuilder;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\OrderByBuilderParameters;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\ParametrizedFromOrder;
use Tuleap\CrossTracker\Query\Advanced\ReadableFieldRetriever;
use Tuleap\Tracker\FormElement\RetrieveFieldType;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrderByDirection;

final readonly class FieldFromOrderBuilder
{
    public function __construct(
        private ReadableFieldRetriever $fields_retriever,
        private RetrieveFieldType $retrieve_field_type,
        private DateFromOrderBuilder $date_builder,
        private NumericFromOrderBuilder $numeric_builder,
        private TextFromOrderBuilder $text_builder,
        private StaticListFromOrderBuilder $static_list_builder,
        private UGroupListFromOrderBuilder $ugroup_list_builder,
        private UserListFromOrderBuilder $user_list_builder,
    ) {
    }

    public function getFromOrder(Field $field, OrderByBuilderParameters $parameters): ParametrizedFromOrder
    {
        $tracker_ids = array_map(static fn(Tracker $tracker) => $tracker->getId(), $parameters->trackers);

        $fields_user_can_read = $this->fields_retriever->retrieveFieldsUserCanRead($field, $parameters->user, $tracker_ids);
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
        return match ($field->type) {
            DuckTypedFieldTypeOrderBy::DATE,
            DuckTypedFieldTypeOrderBy::DATETIME    => $this->date_builder->getFromOrder($field, $direction),
            DuckTypedFieldTypeOrderBy::NUMERIC     => $this->numeric_builder->getFromOrder($field, $direction),
            DuckTypedFieldTypeOrderBy::TEXT        => $this->text_builder->getFromOrder($field->field_ids, $direction),
            DuckTypedFieldTypeOrderBy::STATIC_LIST => $this->static_list_builder->getFromOrder($field->field_ids, $direction),
            DuckTypedFieldTypeOrderBy::UGROUP_LIST => $this->ugroup_list_builder->getFromOrder($field->field_ids, $direction),
            DuckTypedFieldTypeOrderBy::USER_LIST   => $this->user_list_builder->getFromOrder($field->field_ids, $direction),
        };
    }
}
