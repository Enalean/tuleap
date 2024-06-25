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

namespace Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field;

use PFUser;
use Tracker;
use Tracker_FormElement_Field;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\Select\DuckTypedFieldSelect;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\Select\DuckTypedFieldTypeSelect;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field\Date\DateResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field\Numeric\NumericResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field\StaticList\StaticListResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field\Text\TextResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\SelectedValuesCollection;
use Tuleap\Tracker\FormElement\Field\RetrieveUsedFields;
use Tuleap\Tracker\FormElement\RetrieveFieldType;
use Tuleap\Tracker\Permission\FieldPermissionType;
use Tuleap\Tracker\Permission\RetrieveUserPermissionOnFields;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;

final readonly class FieldResultBuilder
{
    public function __construct(
        private RetrieveUsedFields $retrieve_used_fields,
        private RetrieveFieldType $retrieve_field_type,
        private RetrieveUserPermissionOnFields $permission_on_fields,
        private DateResultBuilder $date_builder,
        private TextResultBuilder $text_builder,
        private NumericResultBuilder $numeric_builder,
        private StaticListResultBuilder $static_list_builder,
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
        $fields      = array_filter(
            array_map(
                fn(int $tracker_id) => $this->retrieve_used_fields->getUsedFieldByName($tracker_id, $field->getName()),
                $tracker_ids,
            ),
            static fn(?Tracker_FormElement_Field $field) => $field !== null,
        );

        $fields_user_can_read = $this->permission_on_fields
            ->retrieveUserPermissionOnFields($user, $fields, FieldPermissionType::PERMISSION_READ)
            ->allowed;
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
            DuckTypedFieldTypeSelect::TEXT        => $this->text_builder->getResult($field, $select_results, $user),
            DuckTypedFieldTypeSelect::NUMERIC     => $this->numeric_builder->getResult($field, $select_results),
            DuckTypedFieldTypeSelect::STATIC_LIST => $this->static_list_builder->getResult($field, $select_results),
            DuckTypedFieldTypeSelect::UGROUP_LIST,
            DuckTypedFieldTypeSelect::USER_LIST   => new SelectedValuesCollection(null, []),
        };
    }
}
