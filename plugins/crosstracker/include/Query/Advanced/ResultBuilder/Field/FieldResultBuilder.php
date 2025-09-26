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

namespace Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Field;

use PFUser;
use Tuleap\CrossTracker\Query\Advanced\DuckTypedField\Select\DuckTypedFieldSelect;
use Tuleap\CrossTracker\Query\Advanced\DuckTypedField\Select\DuckTypedFieldTypeSelect;
use Tuleap\CrossTracker\Query\Advanced\ReadableFieldRetriever;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Field\Date\DateResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Field\Numeric\NumericResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Field\StaticList\StaticListResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Field\Text\TextResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Field\UGroupList\UGroupListResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Field\Unknown\UnknownTypeResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Field\UserList\UserListResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValuesCollection;
use Tuleap\Tracker\FormElement\RetrieveFieldType;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Tracker;

final readonly class FieldResultBuilder
{
    public function __construct(
        private RetrieveFieldType $retrieve_field_type,
        private DateResultBuilder $date_builder,
        private TextResultBuilder $text_builder,
        private NumericResultBuilder $numeric_builder,
        private StaticListResultBuilder $static_list_builder,
        private UGroupListResultBuilder $user_group_list_builder,
        private UserListResultBuilder $user_list_builder,
        private UnknownTypeResultBuilder $unknown_type_result_builder,
        private ReadableFieldRetriever $fields_retriever,
    ) {
    }

    /**
     * @param Tracker[] $trackers
     */
    public function getResult(
        Field $field,
        PFUser $user,
        array $trackers,
        array $select_results,
    ): SelectedValuesCollection {
        $tracker_ids = array_map(static fn(Tracker $tracker) => $tracker->getId(), $trackers);

        $fields_user_can_read = $this->fields_retriever->retrieveFieldsUserCanRead($field, $user, $tracker_ids);

        return DuckTypedFieldSelect::build(
            $this->retrieve_field_type,
            $field->getName(),
            $fields_user_can_read,
            $tracker_ids,
        )->match(
            fn(DuckTypedFieldSelect $duck_typed_field) => $this->matchTypeToBuilder($duck_typed_field, $select_results, $user),
            static fn() => new SelectedValuesCollection(null, []),
        );
    }

    private function matchTypeToBuilder(DuckTypedFieldSelect $field, array $select_results, PFUser $user): SelectedValuesCollection
    {
        return match ($field->type) {
            DuckTypedFieldTypeSelect::DATE        => $this->date_builder->getResult($field, $select_results, $user),
            DuckTypedFieldTypeSelect::TEXT        => $this->text_builder->getResult($field, $select_results),
            DuckTypedFieldTypeSelect::NUMERIC     => $this->numeric_builder->getResult($field, $select_results),
            DuckTypedFieldTypeSelect::STATIC_LIST => $this->static_list_builder->getResult($field, $select_results),
            DuckTypedFieldTypeSelect::UGROUP_LIST => $this->user_group_list_builder->getResult($field, $select_results),
            DuckTypedFieldTypeSelect::USER_LIST   => $this->user_list_builder->getResult($field, $select_results),
            DuckTypedFieldTypeSelect::UNKNOWN     => $this->unknown_type_result_builder->getResult($field, $select_results),
        };
    }
}
