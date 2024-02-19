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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Field;

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
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InvalidFieldException;

final class FieldUsageChecker
{
    public function __construct(
        private readonly RetrieveUsedFields $retrieve_used_fields,
        private readonly RetrieveFieldType $retrieve_field_type,
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
        foreach ($tracker_ids as $tracker_id) {
            $used_field = $this->retrieve_used_fields->getUsedFieldByName($tracker_id, $field->getName());
            if ($used_field && $used_field->userCanRead($user)) {
                try {
                    $comparison = $collector_parameters->getComparison();
                    $collector_parameters->getCheckerProvider()
                        ->getInvalidFieldChecker($used_field)
                        ->checkFieldIsValidForComparison($comparison, $used_field);
                } catch (InvalidFieldException $e) {
                    return Result::err(Fault::fromThrowable($e));
                }
                $fields_user_can_read[] = $used_field;
            }
        }

        return DuckTypedField::build(
            new FieldTypeRetrieverWrapper($this->retrieve_field_type),
            $field->getName(),
            $fields_user_can_read,
            $tracker_ids,
        )->map(static fn() => null);
    }
}
