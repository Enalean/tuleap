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

use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSearchableCollectorParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;

final class FieldUsageChecker
{
    public function __construct(private readonly SearchFieldTypes $field_dao)
    {
    }

    /**
     * @throws FieldTypeIsNotSupportedException
     * @throws FieldTypesAreIncompatibleException
     */
    public function checkFieldIsValid(
        Field $field,
        InvalidSearchableCollectorParameters $collector_parameters,
    ): void {
        $tracker_ids         = $collector_parameters->getInvalidSearchablesCollectorParameters()->getTrackerIds();
        $field_name          = $field->getName();
        $field_list_to_check = $this->field_dao->searchTypeByFieldNameAndTrackerList($field_name, $tracker_ids);

        if (count($field_list_to_check) === 0) {
            return;
        }

        $first_type = $field_list_to_check[0]["type"];
        if ($first_type !== "float" && $first_type !== "int") {
            throw new FieldTypeIsNotSupportedException($field_name);
        }

        foreach ($field_list_to_check as $field_to_check) {
            if ($field_to_check["type"] !== "float" && $field_to_check["type"] !== "int") {
                throw new FieldTypesAreIncompatibleException($field_name, $tracker_ids);
            }
        }
    }
}
