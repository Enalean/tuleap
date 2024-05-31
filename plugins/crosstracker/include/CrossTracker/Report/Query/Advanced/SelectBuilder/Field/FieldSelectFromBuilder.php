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

namespace Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field;

use PFUser;
use Tracker;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\Select\DuckTypedFieldSelect;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\Select\DuckTypedFieldTypeSelect;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\FieldTypeRetrieverWrapper;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\IProvideParametrizedSelectAndFromSQLFragments;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\ParametrizedSelectFrom;
use Tuleap\Tracker\FormElement\Field\RetrieveUsedFields;
use Tuleap\Tracker\FormElement\RetrieveFieldType;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;

final readonly class FieldSelectFromBuilder
{
    public function __construct(
        private RetrieveUsedFields $retrieve_used_fields,
        private RetrieveFieldType $retrieve_field_type,
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
        return DuckTypedFieldSelect::build(
            new FieldTypeRetrieverWrapper($this->retrieve_field_type),
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
            DuckTypedFieldTypeSelect::DATE,
            DuckTypedFieldTypeSelect::TEXT,
            DuckTypedFieldTypeSelect::NUMERIC,
            DuckTypedFieldTypeSelect::STATIC_LIST,
            DuckTypedFieldTypeSelect::UGROUP_LIST,
            DuckTypedFieldTypeSelect::USER_LIST => new ParametrizedSelectFrom('', '', []),
        };
    }
}
