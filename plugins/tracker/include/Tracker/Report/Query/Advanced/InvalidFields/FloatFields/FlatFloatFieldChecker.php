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

namespace Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FloatFields;

use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ComparisonType;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\CollectionOfAlphaNumericValuesExtractor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EmptyStringAllowed;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EmptyStringForbidden;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FieldIsNotSupportedForComparisonException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InvalidFieldChecker;

final readonly class FlatFloatFieldChecker implements InvalidFieldChecker
{
    /**
     * @throws FloatToStringComparisonException
     * @throws FloatToNowComparisonException
     * @throws FloatToEmptyStringTermException
     * @throws FloatToMySelfComparisonException
     * @throws FloatToStatusOpenComparisonException
     * @throws FieldIsNotSupportedForComparisonException
     */
    public function checkFieldIsValidForComparison(Comparison $comparison, \Tracker_FormElement_Field $field): void
    {
        $values_extractor                    = new CollectionOfAlphaNumericValuesExtractor();
        $checker_with_empty_string_allowed   = new FloatFieldChecker(new EmptyStringAllowed(), $values_extractor);
        $checker_with_empty_string_forbidden = new FloatFieldChecker(new EmptyStringForbidden(), $values_extractor);
        match ($comparison->getType()) {
            ComparisonType::Equal,
            ComparisonType::NotEqual => $checker_with_empty_string_allowed->checkFieldIsValidForComparison($comparison, $field),
            ComparisonType::LesserThan,
            ComparisonType::LesserThanOrEqual,
            ComparisonType::GreaterThan,
            ComparisonType::GreaterThanOrEqual,
            ComparisonType::Between => $checker_with_empty_string_forbidden->checkFieldIsValidForComparison($comparison, $field),
            ComparisonType::In => throw new FieldIsNotSupportedForComparisonException($field, 'in()'),
            ComparisonType::NotIn => throw new FieldIsNotSupportedForComparisonException($field, 'not in()'),
        };
    }
}
