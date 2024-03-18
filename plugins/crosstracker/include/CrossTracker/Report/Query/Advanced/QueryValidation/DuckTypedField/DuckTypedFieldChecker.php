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

use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\DuckTypedField;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\FieldTypeRetrieverWrapper;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSearchableCollectorParameters;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\FormElement\Field\RetrieveUsedFields;
use Tuleap\Tracker\FormElement\RetrieveFieldType;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FlatInvalidFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InvalidFieldException;

final readonly class DuckTypedFieldChecker
{
    public function __construct(
        private RetrieveUsedFields $retrieve_used_fields,
        private RetrieveFieldType $retrieve_field_type,
        private FlatInvalidFieldChecker $field_checker,
    ) {
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    public function checkFieldIsValid(
        Field $field,
        InvalidSearchableCollectorParameters $collector_parameters,
    ): Ok|Err {
        $tracker_ids          = $collector_parameters->getInvalidSearchablesCollectorParameters()->getTrackerIds();
        $user                 = $collector_parameters->getInvalidSearchablesCollectorParameters()->getUser();
        $fields_user_can_read = [];
        $exception_collector  = [];
        foreach ($tracker_ids as $tracker_id) {
            $used_field = $this->retrieve_used_fields->getUsedFieldByName($tracker_id, $field->getName());
            if ($used_field && $used_field->userCanRead($user)) {
                try {
                    $comparison = $collector_parameters->getComparison();
                    $this->field_checker->checkFieldIsValidForComparison($comparison, $used_field);
                } catch (InvalidFieldException $e) {
                    $exception_collector[] = $e;
                }
                $fields_user_can_read[] = $used_field;
            }
        }

        if (count($fields_user_can_read) > 0 && count($exception_collector) === count($fields_user_can_read)) {
            return Result::err(Fault::fromThrowable($exception_collector[0]));
        }

        return DuckTypedField::build(
            new FieldTypeRetrieverWrapper($this->retrieve_field_type),
            $field->getName(),
            $fields_user_can_read,
            $tracker_ids,
        )->map(static fn() => null);
    }
}
