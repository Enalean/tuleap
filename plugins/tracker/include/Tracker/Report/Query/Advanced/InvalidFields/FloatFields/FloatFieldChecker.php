<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FieldIsNotSupportedForComparisonException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InvalidFieldChecker;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\MySelfIsNotSupportedException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\NowIsNotSupportedException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\StatusOpenIsNotSupportedException;

final readonly class FloatFieldChecker implements InvalidFieldChecker
{
    /**
     * @throws FieldIsNotSupportedForComparisonException
     * @throws FloatToEmptyStringTermException
     * @throws FloatToMySelfComparisonException
     * @throws FloatToNowComparisonException
     * @throws FloatToStatusOpenComparisonException
     * @throws FloatToStringComparisonException
     */
    public function checkFieldIsValidForComparison(Comparison $comparison, \Tracker_FormElement_Field $field): void
    {
        match ($comparison->getType()) {
            ComparisonType::Equal,
            ComparisonType::NotEqual => $this->checkFloatValueIsValid($comparison, $field, false),
            ComparisonType::LesserThan,
            ComparisonType::LesserThanOrEqual,
            ComparisonType::GreaterThan,
            ComparisonType::GreaterThanOrEqual,
            ComparisonType::Between => $this->checkFloatValueIsValid($comparison, $field, true),
            ComparisonType::In => throw new FieldIsNotSupportedForComparisonException($field, 'in()'),
            ComparisonType::NotIn => throw new FieldIsNotSupportedForComparisonException($field, 'not in()'),
        };
    }

    /**
     * @throws FloatToEmptyStringTermException
     * @throws FloatToMySelfComparisonException
     * @throws FloatToNowComparisonException
     * @throws FloatToStatusOpenComparisonException
     * @throws FloatToStringComparisonException
     */
    private function checkFloatValueIsValid(
        Comparison $comparison,
        \Tracker_FormElement_Field $field,
        bool $is_empty_string_a_problem,
    ): void {
        $values_extractor = new CollectionOfAlphaNumericValuesExtractor();
        try {
            $values = $values_extractor->extractCollectionOfValues($comparison->getValueWrapper(), $field);
        } catch (NowIsNotSupportedException) {
            throw new FloatToNowComparisonException($field);
        } catch (MySelfIsNotSupportedException) {
            throw new FloatToMySelfComparisonException($field);
        } catch (StatusOpenIsNotSupportedException) {
            throw new FloatToStatusOpenComparisonException($field);
        }

        foreach ($values as $value) {
            if ($is_empty_string_a_problem && $value === '') {
                throw new FloatToEmptyStringTermException($comparison, $field);
            }

            if (! is_numeric($value) && $value !== '') {
                throw new FloatToStringComparisonException($field, $value);
            }
        }
    }
}
