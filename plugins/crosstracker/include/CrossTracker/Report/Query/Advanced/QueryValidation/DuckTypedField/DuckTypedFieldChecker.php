<?php
/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\DuckTypedField;

use Tracker;
use Tracker_FormElement_Field;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\FieldTypeRetrieverWrapper;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\OrderBy\DuckTypedFieldOrderBy;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\Select\DuckTypedFieldSelect;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\Where\DuckTypedFieldWhere;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidOrderByBuilderParameters;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSearchableCollectorParameters;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSelectableCollectorParameters;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\FormElement\Field\RetrieveUsedFields;
use Tuleap\Tracker\FormElement\RetrieveFieldType;
use Tuleap\Tracker\Permission\FieldPermissionType;
use Tuleap\Tracker\Permission\RetrieveUserPermissionOnFields;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InvalidFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InvalidFieldException;

final readonly class DuckTypedFieldChecker
{
    public function __construct(
        private RetrieveUsedFields $retrieve_used_fields,
        private RetrieveFieldType $retrieve_field_type,
        private InvalidFieldChecker $field_checker,
        private RetrieveUserPermissionOnFields $user_permission_on_fields,
    ) {
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    public function checkFieldIsValidForSearch(
        Field $field,
        InvalidSearchableCollectorParameters $collector_parameters,
    ): Ok|Err {
        $tracker_ids          = $collector_parameters->invalid_comparison_parameters->getTrackerIds();
        $user                 = $collector_parameters->invalid_comparison_parameters->getUser();
        $fields_user_can_read = [];
        $exception_collector  = [];
        foreach ($tracker_ids as $tracker_id) {
            $used_field = $this->retrieve_used_fields->getUsedFieldByName($tracker_id, $field->getName());
            if ($used_field && $used_field->userCanRead($user)) {
                try {
                    $this->field_checker->checkFieldIsValidForComparison($collector_parameters->comparison, $used_field);
                } catch (InvalidFieldException $e) {
                    $exception_collector[] = $e;
                }
                $fields_user_can_read[] = $used_field;
            }
        }

        if (count($fields_user_can_read) > 0 && count($exception_collector) === count($fields_user_can_read)) {
            return Result::err(Fault::fromThrowable($exception_collector[0]));
        }

        return DuckTypedFieldWhere::build(
            new FieldTypeRetrieverWrapper($this->retrieve_field_type),
            $field->getName(),
            $fields_user_can_read,
            $tracker_ids,
        )->map(static fn() => null);
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    public function checkFieldIsValidForSelect(
        Field $field,
        InvalidSelectableCollectorParameters $collector_parameters,
    ): Ok|Err {
        $tracker_ids = $collector_parameters->getTrackersIds();
        $user        = $collector_parameters->user;

        $fields = array_filter(
            array_map(
                fn(int $tracker_id) => $this->retrieve_used_fields->getUsedFieldByName($tracker_id, $field->getName()),
                $tracker_ids,
            ),
            static fn(?Tracker_FormElement_Field $field) => $field !== null,
        );

        $fields_user_can_read = $this->user_permission_on_fields
            ->retrieveUserPermissionOnFields($user, $fields, FieldPermissionType::PERMISSION_READ)
            ->allowed;

        return DuckTypedFieldSelect::build(
            new FieldTypeRetrieverWrapper($this->retrieve_field_type),
            $field->getName(),
            $fields_user_can_read,
            $tracker_ids,
        )->map(static fn() => null);
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    public function checkFieldIsValidForOrderBy(
        Field $field,
        InvalidOrderByBuilderParameters $parameters,
    ): Ok|Err {
        $tracker_ids = array_map(static fn(Tracker $tracker) => $tracker->getId(), $parameters->trackers);

        $fields = array_filter(
            array_map(
                fn(int $tracker_id) => $this->retrieve_used_fields->getUsedFieldByName($tracker_id, $field->getName()),
                $tracker_ids,
            ),
            static fn(?Tracker_FormElement_Field $field) => $field !== null,
        );

        $fields_user_can_read = $this->user_permission_on_fields
            ->retrieveUserPermissionOnFields($parameters->user, $fields, FieldPermissionType::PERMISSION_READ)
            ->allowed;

        return DuckTypedFieldOrderBy::build(
            new FieldTypeRetrieverWrapper($this->retrieve_field_type),
            $field->getName(),
            $fields_user_can_read,
            $tracker_ids,
        )->map(static fn() => null);
    }
}
