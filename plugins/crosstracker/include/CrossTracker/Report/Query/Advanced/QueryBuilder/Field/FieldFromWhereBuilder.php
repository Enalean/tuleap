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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Field;

use PFUser;
use Tracker;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\DuckTypedField;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\DuckTypedFieldType;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\FieldTypeRetrieverWrapper;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Field\Date\DateFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Field\Datetime\DatetimeFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Field\Numeric\NumericFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Field\StaticList\StaticListFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Field\Text\TextFromWhereBuilder;
use Tuleap\Tracker\FormElement\Field\RetrieveUsedFields;
use Tuleap\Tracker\FormElement\RetrieveFieldType;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;

final readonly class FieldFromWhereBuilder
{
    public function __construct(
        private RetrieveUsedFields $retrieve_used_fields,
        private RetrieveFieldType $retrieve_field_type,
        private NumericFromWhereBuilder $numeric_builder,
        private TextFromWhereBuilder $text_builder,
        private DateFromWhereBuilder $date_builder,
        private DatetimeFromWhereBuilder $datetime_builder,
        private StaticListFromWhereBuilder $static_list_builder,
    ) {
    }

    /**
     * @param Tracker[] $trackers
     */
    public function getFromWhere(
        Field $field,
        Comparison $comparison,
        PFUser $user,
        array $trackers,
    ): IProvideParametrizedFromAndWhereSQLFragments {
        $tracker_ids          = [];
        $fields_user_can_read = [];
        foreach ($trackers as $tracker) {
            $tracker_id    = $tracker->getId();
            $tracker_ids[] = $tracker_id;
            $used_field    = $this->retrieve_used_fields->getUsedFieldByName($tracker_id, $field->getName());
            if ($used_field && $used_field->userCanRead($user)) {
                $fields_user_can_read[] = $used_field;
            }
        }
        return DuckTypedField::build(
            new FieldTypeRetrieverWrapper($this->retrieve_field_type),
            $field->getName(),
            $fields_user_can_read,
            $tracker_ids,
        )->match(
            fn(DuckTypedField $duck_typed_field) => $this->matchTypeToBuilder($duck_typed_field, $comparison),
            static fn() => new ParametrizedFromWhere('', '', [], [])
        );
    }

    private function matchTypeToBuilder(
        DuckTypedField $field,
        Comparison $comparison,
    ): IProvideParametrizedFromAndWhereSQLFragments {
        return match ($field->type) {
            DuckTypedFieldType::NUMERIC     => $this->numeric_builder->getFromWhere($field, $comparison),
            DuckTypedFieldType::TEXT        => $this->text_builder->getFromWhere($field, $comparison),
            DuckTypedFieldType::DATE        => $this->date_builder->getFromWhere($field, $comparison),
            DuckTypedFieldType::DATETIME    => $this->datetime_builder->getFromWhere($field, $comparison),
            DuckTypedFieldType::STATIC_LIST => $this->static_list_builder->getFromWhere($field, $comparison),
        };
    }
}
