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

namespace Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Field;

use PFUser;
use Tracker_FormElement_Field;
use Tuleap\CrossTracker\Query\Advanced\DuckTypedField\Select\DuckTypedFieldSelect;
use Tuleap\CrossTracker\Query\Advanced\DuckTypedField\Select\DuckTypedFieldTypeSelect;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Field\Date\DateSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Field\Numeric\NumericSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Field\StaticList\StaticListSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Field\Text\TextSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Field\UGroupList\UGroupListSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Field\UserList\UserListSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\IProvideParametrizedSelectAndFromSQLFragments;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\ParametrizedSelectFrom;
use Tuleap\Tracker\FormElement\Field\RetrieveUsedFields;
use Tuleap\Tracker\FormElement\RetrieveFieldType;
use Tuleap\Tracker\Permission\FieldPermissionType;
use Tuleap\Tracker\Permission\RetrieveUserPermissionOnFields;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Tracker;

final readonly class FieldSelectFromBuilder
{
    public function __construct(
        private RetrieveUsedFields $retrieve_used_fields,
        private RetrieveFieldType $retrieve_field_type,
        private RetrieveUserPermissionOnFields $permission_on_fields,
        private DateSelectFromBuilder $date_builder,
        private TextSelectFromBuilder $text_builder,
        private NumericSelectFromBuilder $numeric_builder,
        private StaticListSelectFromBuilder $static_list_builder,
        private UGroupListSelectFromBuilder $user_group_list_builder,
        private UserListSelectFromBuilder $user_list_builder,
    ) {
    }

    /**
     * @param Tracker[] $trackers
     */
    public function getSelectFrom(
        Field $field,
        PFUser $user,
        array $trackers,
    ): IProvideParametrizedSelectAndFromSQLFragments {
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
            fn(DuckTypedFieldSelect $duck_typed_field) => $this->matchTypeToBuilder($duck_typed_field),
            static fn() => new ParametrizedSelectFrom('', '', []),
        );
    }

    private function matchTypeToBuilder(DuckTypedFieldSelect $field): IProvideParametrizedSelectAndFromSQLFragments
    {
        return match ($field->type) {
            DuckTypedFieldTypeSelect::DATE    => $this->date_builder->getSelectFrom($field),
            DuckTypedFieldTypeSelect::TEXT    => $this->text_builder->getSelectFrom($field),
            DuckTypedFieldTypeSelect::NUMERIC => $this->numeric_builder->getSelectFrom($field),
            DuckTypedFieldTypeSelect::STATIC_LIST => $this->static_list_builder->getSelectFrom($field),
            DuckTypedFieldTypeSelect::UGROUP_LIST => $this->user_group_list_builder->getSelectFrom($field),
            DuckTypedFieldTypeSelect::USER_LIST => $this->user_list_builder->getSelectFrom($field),
            DuckTypedFieldTypeSelect::UNKNOWN => new ParametrizedSelectFrom('', '', []),
        };
    }
}
